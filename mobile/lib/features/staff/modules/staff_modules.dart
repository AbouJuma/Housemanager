import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/api/api_client.dart';
import '../../../core/api/json_helpers.dart';
import '../../../core/api/paginated_list.dart';
import '../../../core/l10n/l10n.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../../../core/widgets/toast.dart';
import '../../auth/providers/auth_provider.dart';
import 'company_form_screen.dart';
import 'vendor_form_screen.dart';

typedef Json = Map<String, dynamic>;

/// One labelled value on a record's detail card; rows with no value are
/// skipped.
class ModuleField {
  const ModuleField(this.label, this.value);

  final String label;
  final String? Function(Json record) value;
}

/// Something a role may do to a record from its detail screen (update a
/// job's status, mark an expense paid, check a visitor out...).
class ModuleAction {
  const ModuleAction({
    required this.label,
    required this.icon,
    required this.permission,
    required this.run,
    this.visible = _always,
  });

  final String label;
  final IconData icon;

  /// Staff permission needed - the button is hidden without it.
  final String permission;
  final bool Function(Json record) visible;

  /// Returns true when the record changed and should be reloaded.
  final Future<bool> Function(BuildContext context, ApiClient api, Json record) run;

  static bool _always(Json _) => true;
}

/// A back-office module the app can show to any staff role allowed to
/// see it. Each one is the module's existing `/api/v1/...` endpoint
/// plus how to present its records - so a new role (accountant,
/// caretaker, security, storekeeper...) needs no new screens, only
/// permissions.
class StaffModule {
  const StaffModule({
    required this.id,
    required this.title,
    required this.recordTitle,
    required this.endpoint,
    required this.emptyLabel,
    required this.heading,
    required this.subtitle,
    this.status,
    this.trailing,
    this.statusParam = 'status',
    this.statusOptions,
    this.fields = const [],
    this.body,
    this.actions = const [],
    this.form,
    this.deletable = false,
  });

  final String id;
  final String title;
  final String recordTitle;
  final String endpoint;
  final String emptyLabel;
  final String Function(Json) heading;
  final String? Function(Json) subtitle;
  final String? Function(Json)? status;
  final String? Function(Json)? trailing;

  /// Query parameter the list endpoint filters on (expenses use
  /// `payment_status`).
  final String statusParam;
  final List<String>? statusOptions;
  final List<ModuleField> fields;
  final String? Function(Json)? body;
  final List<ModuleAction> actions;

  /// Add / edit form (null record = add), shown with the module's
  /// `.create` / `.update` permission. Pops `true` when saved.
  final Widget Function(Json? record)? form;

  /// Records can be deleted (with the `.delete` permission).
  final bool deletable;

  Future<PageResult<Json>> page(ApiClient api, int page, ListFilters filters) async {
    final response = await api.dio.get(endpoint, queryParameters: {
      if (filters.status != null) statusParam: filters.status,
      'page': page,
    });
    final body = response.data as Map<String, dynamic>;
    final items = (body['data'] as List<dynamic>).cast<Json>();
    final meta = body['meta'] as Map<String, dynamic>?;
    final current = asIntOrNull(meta?['current_page']) ?? page;
    final last = asIntOrNull(meta?['last_page']) ?? page;
    return PageResult(items, current < last);
  }

  Future<Json> find(ApiClient api, String uuid) async {
    final response = await api.dio.get('$endpoint/$uuid');
    return unwrapObject(response.data);
  }
}

// ---------------------------------------------------------------------
// Value helpers
// ---------------------------------------------------------------------

String? _str(dynamic value) {
  if (value == null) return null;
  final text = '$value'.trim();
  return text.isEmpty ? null : text;
}

String? _name(dynamic relation, [String key = 'name']) => relation is Map ? _str(relation[key]) : null;

/// `utility_provider` -> `Utility provider`.
String? _humanize(dynamic value) {
  final text = _str(value)?.replaceAll('_', ' ');
  if (text == null) return null;
  return text[0].toUpperCase() + text.substring(1);
}

String? _date(dynamic value) => _str(value) == null ? null : formatDate(value as String);

String? _dateTime(dynamic value) => _str(value) == null ? null : formatDateTime(value as String);

String? _money(dynamic value) => asDoubleOrNull(value) == null ? null : formatMoney(asDouble(value));

String? _number(dynamic value) {
  final n = asDoubleOrNull(value);
  if (n == null) return null;
  return n == n.roundToDouble() ? n.toInt().toString() : n.toStringAsFixed(2);
}

String? _join(List<String?> parts) {
  final kept = parts.whereType<String>().where((p) => p.isNotEmpty).toList();
  return kept.isEmpty ? null : kept.join(' · ');
}

String _today() {
  final now = DateTime.now();
  return '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
}

// ---------------------------------------------------------------------
// The modules
// ---------------------------------------------------------------------

const maintenanceStatuses = ['open', 'in_progress', 'completed', 'cancelled'];
const expensePaymentMethods = ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'];

final staffModules = <String, StaffModule>{
  // Super admin (platform owner) only.
  'companies': StaffModule(
    id: 'companies',
    title: 'Companies',
    recordTitle: 'Company',
    endpoint: '/companies',
    emptyLabel: 'No companies yet.',
    heading: (r) => _str(r['name']) ?? '—',
    subtitle: (r) => _join([_str(r['email']), _str(r['phone'])]),
    status: (r) => _str(r['status']),
    fields: [
      ModuleField('Email', (r) => _str(r['email'])),
      ModuleField('Phone', (r) => _str(r['phone'])),
      ModuleField('Website', (r) => _str(r['website'])),
      ModuleField('City', (r) => r['address'] is Map ? _str(r['address']['city']) : null),
      ModuleField('Currency', (r) => _str(r['currency_code'])),
      ModuleField('Timezone', (r) => _str(r['timezone'])),
      ModuleField('Trial ends', (r) => _date(r['trial_ends_at'])),
      ModuleField('Created', (r) => _date(r['created_at'])),
    ],
    form: (record) => CompanyFormScreen(company: record),
  ),
  'maintenance': StaffModule(
    id: 'maintenance',
    title: 'Maintenance',
    recordTitle: 'Maintenance Request',
    endpoint: '/maintenance-requests',
    emptyLabel: 'No maintenance requests.',
    heading: (r) => _str(r['title']) ?? '—',
    subtitle: (r) => _join([_name(r['property']), _name(r['unit'], 'unit_number'), _humanize(r['priority'])]),
    status: (r) => _str(r['status']),
    statusOptions: maintenanceStatuses,
    body: (r) => _str(r['description']),
    fields: [
      ModuleField('Category', (r) => _humanize(r['category'])),
      ModuleField('Priority', (r) => _humanize(r['priority'])),
      ModuleField('Property', (r) => _name(r['property'])),
      ModuleField('Unit', (r) => _name(r['unit'], 'unit_number')),
      ModuleField('Tenant', (r) => _name(r['tenant'])),
      ModuleField('Vendor', (r) => _name(r['vendor'])),
      ModuleField('Reported', (r) => _date(r['reported_date'])),
      ModuleField('Scheduled', (r) => _date(r['scheduled_date'])),
      ModuleField('Completed', (r) => _date(r['completed_date'])),
      ModuleField('Estimated cost', (r) => _money(r['estimated_cost'])),
      ModuleField('Actual cost', (r) => _money(r['actual_cost'])),
      ModuleField('Notes', (r) => _str(r['notes'])),
    ],
    actions: [
      ModuleAction(
        label: 'Update status',
        icon: Icons.published_with_changes,
        permission: 'maintenance.update',
        visible: (r) => r['status'] != 'cancelled',
        run: _updateMaintenanceStatus,
      ),
    ],
  ),
  'expenses': StaffModule(
    id: 'expenses',
    title: 'Expenses',
    recordTitle: 'Expense',
    endpoint: '/expenses',
    emptyLabel: 'No expenses yet.',
    heading: (r) => _str(r['description']) ?? '—',
    subtitle: (r) => _join([_str(r['vendor_display_name']), _date(r['expense_date'])]),
    status: (r) => r['status'] == 'voided' ? 'voided' : _str(r['payment_status']),
    trailing: (r) => _money(r['amount']),
    statusParam: 'payment_status',
    statusOptions: const ['unpaid', 'paid'],
    fields: [
      ModuleField('Amount', (r) => _money(r['amount'])),
      ModuleField('Date', (r) => _date(r['expense_date'])),
      ModuleField('Paid to', (r) => _str(r['vendor_display_name'])),
      ModuleField('Property', (r) => _name(r['property'])),
      ModuleField('Account', (r) => r['account'] is Map ? _join([_str(r['account']['code']), _str(r['account']['name'])]) : null),
      ModuleField('Payment method', (r) => _humanize(r['payment_method'])),
      ModuleField('Paid on', (r) => _date(r['paid_on'])),
      ModuleField('Reference', (r) => _str(r['reference'])),
      ModuleField('Notes', (r) => _str(r['notes'])),
    ],
    actions: [
      ModuleAction(
        label: 'Mark as paid',
        icon: Icons.price_check,
        permission: 'expenses.update',
        visible: (r) => r['payment_status'] != 'paid' && r['status'] != 'voided',
        run: _markExpensePaid,
      ),
    ],
  ),
  'visitors': StaffModule(
    id: 'visitors',
    title: 'Visitors',
    recordTitle: 'Visitor',
    endpoint: '/visitors',
    emptyLabel: 'No visitors recorded.',
    heading: (r) => _str(r['visitor_name']) ?? '—',
    subtitle: (r) => _join([_humanize(r['purpose']), _name(r['property']), _dateTime(r['check_in_at'])]),
    status: (r) => r['is_checked_in'] == true ? 'checked_in' : 'checked_out',
    statusOptions: const ['checked_in', 'checked_out'],
    fields: [
      ModuleField('Phone', (r) => _str(r['visitor_phone'])),
      ModuleField('Purpose', (r) => _humanize(r['purpose'])),
      ModuleField('Visiting', (r) => _str(r['host_display_name']) ?? _name(r['tenant'])),
      ModuleField('Property', (r) => _name(r['property'])),
      ModuleField('Unit', (r) => _name(r['unit'], 'unit_number')),
      ModuleField('Vehicle', (r) => _str(r['vehicle_plate'])),
      ModuleField('Badge', (r) => _str(r['badge_number'])),
      ModuleField('Checked in', (r) => _dateTime(r['check_in_at'])),
      ModuleField('Checked out', (r) => _dateTime(r['check_out_at'])),
      ModuleField('Notes', (r) => _str(r['notes'])),
    ],
    actions: [
      ModuleAction(
        label: 'Check out',
        icon: Icons.logout,
        permission: 'visitors.update',
        visible: (r) => r['is_checked_in'] == true,
        run: _checkOutVisitor,
      ),
    ],
  ),
  'inventory': StaffModule(
    id: 'inventory',
    title: 'Inventory',
    recordTitle: 'Inventory item',
    endpoint: '/inventory-items',
    emptyLabel: 'No inventory items.',
    heading: (r) => _str(r['name']) ?? '—',
    subtitle: (r) => _join([_humanize(r['category']), _name(r['property'])]),
    status: (r) => r['is_low_stock'] == true ? 'low_stock' : _str(r['status']),
    trailing: (r) => _join([_number(r['quantity_on_hand']), _str(r['unit_of_measure'])])?.replaceAll(' · ', ' '),
    statusOptions: const ['active', 'inactive'],
    fields: [
      ModuleField('On hand', (r) => _join([_number(r['quantity_on_hand']), _str(r['unit_of_measure'])])?.replaceAll(' · ', ' ')),
      ModuleField('Reorder level', (r) => _number(r['reorder_level'])),
      ModuleField('Unit cost', (r) => _money(r['unit_cost'])),
      ModuleField('SKU', (r) => _str(r['sku'])),
      ModuleField('Category', (r) => _humanize(r['category'])),
      ModuleField('Property', (r) => _name(r['property'])),
      ModuleField('Supplier', (r) => _name(r['vendor'])),
      ModuleField('Notes', (r) => _str(r['notes'])),
    ],
  ),
  'properties': StaffModule(
    id: 'properties',
    title: 'Properties',
    recordTitle: 'Property',
    endpoint: '/properties',
    emptyLabel: 'No properties yet.',
    heading: (r) => _str(r['name']) ?? '—',
    subtitle: (r) => _join([_humanize(r['type']), r['address'] is Map ? _join([_str(r['address']['area']), _str(r['address']['city'])])?.replaceAll(' · ', ', ') : null]),
    status: (r) => _str(r['status']),
    fields: [
      ModuleField('Type', (r) => _humanize(r['type'])),
      ModuleField('Landlord', (r) => _name(r['landlord'])),
      ModuleField('Region', (r) => r['address'] is Map ? _str(r['address']['state']) : null),
      ModuleField('District', (r) => r['address'] is Map ? _str(r['address']['city']) : null),
      ModuleField('Area', (r) => r['address'] is Map ? _str(r['address']['area']) : null),
      ModuleField('Address', (r) => r['address'] is Map ? _str(r['address']['line1']) : null),
      ModuleField('Buildings', (r) => _number(r['buildings_count'])),
      ModuleField('Units', (r) => _number(r['units_count'])),
    ],
    body: (r) => _str(r['description']),
  ),
  'vendors': StaffModule(
    id: 'vendors',
    title: 'Vendors',
    recordTitle: 'Vendor',
    endpoint: '/vendors',
    emptyLabel: 'No vendors yet.',
    heading: (r) => _str(r['name']) ?? '—',
    subtitle: (r) => _join([_humanize(r['category']), _str(r['phone'])]),
    status: (r) => _str(r['status']),
    statusOptions: const ['active', 'inactive'],
    fields: [
      ModuleField('Type', (r) => _humanize(r['type'])),
      ModuleField('Category', (r) => _humanize(r['category'])),
      ModuleField('Phone', (r) => _str(r['phone'])),
      ModuleField('Other phone', (r) => _str(r['secondary_phone'])),
      ModuleField('Email', (r) => _str(r['email'])),
      ModuleField('City', (r) => r['address'] is Map ? _str(r['address']['city']) : null),
      ModuleField('TIN', (r) => _str(r['tax_id'])),
      ModuleField('Expenses', (r) => _number(r['expenses_count'])),
      ModuleField('Notes', (r) => _str(r['notes'])),
    ],
    form: (record) => VendorFormScreen(vendor: record),
    deletable: true,
  ),
  'documents': StaffModule(
    id: 'documents',
    title: 'Documents',
    recordTitle: 'Document',
    endpoint: '/documents',
    emptyLabel: 'No documents yet.',
    heading: (r) => _str(r['title']) ?? '—',
    subtitle: (r) => _join([_humanize(r['category']), _name(r['documentable'])]),
    status: (r) => r['is_expired'] == true ? 'expired' : (r['is_expiring_soon'] == true ? 'expiring_soon' : null),
    fields: [
      ModuleField('Category', (r) => _humanize(r['category'])),
      ModuleField('For', (r) => _join([_humanize(r['documentable_type']), _name(r['documentable'])])),
      ModuleField('Expires', (r) => _date(r['expiry_date'])),
      ModuleField('File', (r) => _str(r['file_name'])),
    ],
    body: (r) => _str(r['description']),
    actions: [
      ModuleAction(
        label: 'Open file',
        icon: Icons.open_in_new,
        permission: 'documents.view',
        visible: (r) => _str(r['file_url']) != null,
        run: _openDocument,
      ),
    ],
  ),
};

// ---------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------

Future<bool> _updateMaintenanceStatus(BuildContext context, ApiClient api, Json record) async {
  var status = record['status'] as String? ?? 'open';
  final notes = TextEditingController();

  final confirmed = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (sheetContext) => StatefulBuilder(
      builder: (sheetContext, setSheetState) => Padding(
        padding: EdgeInsets.fromLTRB(16, 0, 16, 16 + MediaQuery.of(sheetContext).viewInsets.bottom),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(sheetContext.tr('Update status'), style: Theme.of(sheetContext).textTheme.titleMedium),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final option in maintenanceStatuses)
                  ChoiceChip(
                    label: Text(sheetContext.tr(_humanize(option)!)),
                    selected: status == option,
                    onSelected: (_) => setSheetState(() => status = option),
                  ),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: notes,
              minLines: 2,
              maxLines: 4,
              decoration: InputDecoration(labelText: sheetContext.tr('Notes (optional)')),
            ),
            const SizedBox(height: 16),
            FilledButton(onPressed: () => Navigator.of(sheetContext).pop(true), child: Text(sheetContext.tr('Save'))),
          ],
        ),
      ),
    ),
  );
  if (confirmed != true) return false;

  await api.dio.patch('/maintenance-requests/${record['uuid']}/status', data: {
    'status': status,
    if (notes.text.trim().isNotEmpty) 'notes': notes.text.trim(),
  });
  return true;
}

Future<bool> _markExpensePaid(BuildContext context, ApiClient api, Json record) async {
  var method = expensePaymentMethods.first;

  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => StatefulBuilder(
      builder: (dialogContext, setDialogState) => AlertDialog(
        title: Text(dialogContext.tr('Mark as paid')),
        content: DropdownButtonFormField<String>(
          initialValue: method,
          decoration: InputDecoration(labelText: dialogContext.tr('Payment method')),
          items: [
            for (final option in expensePaymentMethods)
              DropdownMenuItem(value: option, child: Text(dialogContext.tr(_humanize(option)!))),
          ],
          onChanged: (value) => setDialogState(() => method = value ?? method),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: Text(dialogContext.tr('Cancel'))),
          FilledButton(onPressed: () => Navigator.of(dialogContext).pop(true), child: Text(dialogContext.tr('Mark as paid'))),
        ],
      ),
    ),
  );
  if (confirmed != true) return false;

  await api.dio.post('/expenses/${record['uuid']}/mark-paid', data: {'payment_method': method, 'paid_on': _today()});
  return true;
}

Future<bool> _checkOutVisitor(BuildContext context, ApiClient api, Json record) async {
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: Text(dialogContext.tr('Check out')),
      content: Text('${dialogContext.tr('Check this visitor out now?')}\n${record['visitor_name'] ?? ''}'),
      actions: [
        TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: Text(dialogContext.tr('Cancel'))),
        FilledButton(onPressed: () => Navigator.of(dialogContext).pop(true), child: Text(dialogContext.tr('Check out'))),
      ],
    ),
  );
  if (confirmed != true) return false;

  await api.dio.post('/visitors/${record['uuid']}/check-out');
  return true;
}

Future<bool> _openDocument(BuildContext context, ApiClient api, Json record) async {
  final url = Uri.tryParse(record['file_url'] as String);
  if (url == null || !await launchUrl(url, mode: LaunchMode.externalApplication)) {
    if (context.mounted) showToast(context, context.tr("Couldn't open the file."), type: ToastType.error);
  }
  return false;
}

// ---------------------------------------------------------------------
// Screens
// ---------------------------------------------------------------------

/// Bumped when a record changes from its detail screen, so the list
/// behind it reloads.
final moduleRecordsChanged = ValueNotifier<int>(0);

class ModuleListScreen extends ConsumerStatefulWidget {
  const ModuleListScreen({super.key, required this.module, required this.basePath});

  final StaffModule module;

  /// Where this module lives for the signed-in role (a tab of its own,
  /// or under More) - detail pages open beneath it.
  final String basePath;

  @override
  ConsumerState<ModuleListScreen> createState() => _ModuleListScreenState();
}

class _ModuleListScreenState extends ConsumerState<ModuleListScreen> {
  @override
  void initState() {
    super.initState();
    moduleRecordsChanged.addListener(_reload);
  }

  @override
  void dispose() {
    moduleRecordsChanged.removeListener(_reload);
    super.dispose();
  }

  void _reload() {
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final module = widget.module;
    final api = ref.watch(apiClientProvider);

    final form = module.form;

    return AppScaffold(
      title: module.title,
      floatingActionButton: form == null || !hasPermission(ref, '${module.id}.create')
          ? null
          : FloatingActionButton(
              tooltip: context.tr('Add'),
              onPressed: () async {
                final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => form(null)));
                if (saved == true) moduleRecordsChanged.value++;
              },
              child: const Icon(Icons.add),
            ),
      body: PaginatedListView<Json>(
        key: ValueKey(moduleRecordsChanged.value),
        fetchPage: (page, filters) => module.page(api, page, filters),
        statusOptions: module.statusOptions,
        showDateRange: false,
        emptyLabel: module.emptyLabel,
        itemBuilder: (context, record) => ModuleRecordTile(
          module: module,
          record: record,
          onTap: () => context.push('${widget.basePath}/${record['uuid']}'),
        ),
      ),
    );
  }
}

class ModuleRecordTile extends StatelessWidget {
  const ModuleRecordTile({super.key, required this.module, required this.record, required this.onTap});

  final StaffModule module;
  final Json record;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final status = module.status?.call(record);
    final subtitle = module.subtitle(record);
    final trailing = module.trailing?.call(record);
    final accent = status == null ? Theme.of(context).colorScheme.outlineVariant : StatusChip.colorFor(context, status);

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(border: Border(left: BorderSide(color: accent, width: 4))),
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(module.heading(record), style: const TextStyle(fontWeight: FontWeight.w600), maxLines: 2, overflow: TextOverflow.ellipsis),
                  ),
                  if (status != null) ...[const SizedBox(width: 8), StatusChip(status)],
                ],
              ),
              if (subtitle != null || trailing != null) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    Expanded(
                      child: Text(subtitle ?? '', style: TextStyle(color: context.mutedText, fontSize: 13), maxLines: 2, overflow: TextOverflow.ellipsis),
                    ),
                    if (trailing != null) ...[
                      const SizedBox(width: 8),
                      Text(trailing, style: const TextStyle(fontWeight: FontWeight.w700)),
                    ],
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

final _moduleRecordProvider = FutureProvider.autoDispose.family<Json, (String, String)>((ref, key) {
  final (moduleId, uuid) = key;
  return staffModules[moduleId]!.find(ref.watch(apiClientProvider), uuid);
});

class ModuleDetailScreen extends ConsumerStatefulWidget {
  const ModuleDetailScreen({super.key, required this.module, required this.uuid});

  final StaffModule module;
  final String uuid;

  @override
  ConsumerState<ModuleDetailScreen> createState() => _ModuleDetailScreenState();
}

class _ModuleDetailScreenState extends ConsumerState<ModuleDetailScreen> {
  bool _busy = false;

  Future<void> _run(ModuleAction action, Json record) async {
    setState(() => _busy = true);
    try {
      final changed = await action.run(context, ref.read(apiClientProvider), record);
      if (changed) {
        ref.invalidate(_moduleRecordProvider((widget.module.id, widget.uuid)));
        moduleRecordsChanged.value++;
        if (mounted) showToast(context, context.tr('Saved.'), type: ToastType.success);
      }
    } catch (e) {
      if (mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _delete(Json record) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text(dialogContext.tr('Delete')),
        content: Text('${dialogContext.tr('Delete this record?')}\n${widget.module.heading(record)}'),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: Text(dialogContext.tr('Cancel'))),
          FilledButton(onPressed: () => Navigator.of(dialogContext).pop(true), child: Text(dialogContext.tr('Delete'))),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    setState(() => _busy = true);
    try {
      await ref.read(apiClientProvider).dio.delete('${widget.module.endpoint}/${widget.uuid}');
      moduleRecordsChanged.value++;
      if (!mounted) return;
      showToast(context, context.tr('Deleted.'), type: ToastType.success);
      Navigator.of(context).pop();
    } catch (e) {
      if (mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final module = widget.module;
    final key = (module.id, widget.uuid);
    final record = ref.watch(_moduleRecordProvider(key));

    final loaded = record.value;

    return AppScaffold(
      title: module.recordTitle,
      actions: [
        if (loaded != null && module.form != null && hasPermission(ref, '${module.id}.update'))
          IconButton(
            icon: const Icon(Icons.edit_outlined),
            tooltip: context.tr('Edit'),
            onPressed: () async {
              final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => module.form!(loaded)));
              if (saved == true) {
                ref.invalidate(_moduleRecordProvider(key));
                moduleRecordsChanged.value++;
              }
            },
          ),
        if (loaded != null && module.deletable && hasPermission(ref, '${module.id}.delete'))
          IconButton(
            icon: const Icon(Icons.delete_outline),
            tooltip: context.tr('Delete'),
            onPressed: _busy ? null : () => _delete(loaded),
          ),
      ],
      body: record.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(_moduleRecordProvider(key))),
        data: (data) {
          final status = module.status?.call(data);
          final body = module.body?.call(data);
          final rows = [
            for (final field in module.fields)
              if (field.value(data) case final String value) (field.label, value),
          ];
          final actions = module.actions.where((a) => a.visible(data) && hasPermission(ref, a.permission)).toList();

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(child: Text(module.heading(data), style: Theme.of(context).textTheme.titleMedium)),
                          if (status != null) ...[const SizedBox(width: 8), StatusChip(status)],
                        ],
                      ),
                      if (body != null) ...[const SizedBox(height: 8), Text(body)],
                      if (rows.isNotEmpty) const Divider(height: 24),
                      for (final (label, value) in rows)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 4),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(context.tr(label), style: TextStyle(color: context.mutedText)),
                              const SizedBox(width: 12),
                              Expanded(child: Text(value, textAlign: TextAlign.end)),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
              ),
              for (final action in actions) ...[
                const SizedBox(height: 12),
                FilledButton.icon(
                  onPressed: _busy ? null : () => _run(action, data),
                  icon: Icon(action.icon),
                  label: Text(context.tr(action.label)),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}
