import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/json_helpers.dart';
import '../../../core/l10n/l10n.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../../../core/widgets/toast.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/landlord_providers.dart';

/// People buying units in the landlord's properties.
class LandlordBuyersScreen extends ConsumerStatefulWidget {
  const LandlordBuyersScreen({super.key});

  @override
  ConsumerState<LandlordBuyersScreen> createState() => _LandlordBuyersScreenState();
}

class _LandlordBuyersScreenState extends ConsumerState<LandlordBuyersScreen> {
  String _search = '';

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(landlordRepositoryProvider);

    return AppScaffold(
      title: 'My Buyers',
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: TextField(
              decoration: InputDecoration(prefixIcon: const Icon(Icons.search), hintText: context.tr('Search by name or phone'), isDense: true),
              textInputAction: TextInputAction.search,
              onSubmitted: (value) => setState(() => _search = value.trim()),
            ),
          ),
          Expanded(
            child: PaginatedListView<Map<String, dynamic>>(
              key: ValueKey(_search),
              fetchPage: (page, filters) => repository.buyersPage(page, filters, search: _search),
              statusOptions: const ['active', 'inactive'],
              showDateRange: false,
              emptyLabel: 'No buyers yet.',
              itemBuilder: (context, buyer) {
                final purchases = ((buyer['purchases'] as List?) ?? const []).cast<Map<String, dynamic>>();
                final first = purchases.isEmpty ? null : purchases.first;
                return Card(
                  child: ListTile(
                    onTap: () => context.push('/landlord/more/buyers/${buyer['uuid']}'),
                    leading: CircleAvatar(child: Text(((buyer['name'] as String?) ?? '?').substring(0, 1).toUpperCase())),
                    title: Text(buyer['name'] as String? ?? '—', style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text(
                      first == null ? (buyer['phone'] as String? ?? '') : '${(first['property'] as Map?)?['name'] ?? ''} · ${context.tr('Unit')} ${first['unit_number'] ?? ''}',
                      style: TextStyle(color: context.mutedText),
                    ),
                    trailing: first == null ? null : Text('${first['completion_percentage'] ?? 0}%', style: const TextStyle(fontWeight: FontWeight.w700)),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class LandlordBuyerDetailScreen extends ConsumerWidget {
  const LandlordBuyerDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final buyer = ref.watch(landlordBuyerProvider(uuid));

    return AppScaffold(
      title: 'Buyer',
      actions: [
        if (hasPermission(ref, 'buyers.update') && buyer.hasValue)
          IconButton(
            icon: const Icon(Icons.edit_outlined),
            tooltip: context.tr('Edit'),
            onPressed: () async {
              final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(
                builder: (_) => LandlordContactFormScreen(kind: 'buyer', person: buyer.value!),
              ));
              if (saved == true) ref.invalidate(landlordBuyerProvider(uuid));
            },
          ),
      ],
      body: buyer.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordBuyerProvider(uuid))),
        data: (b) {
          final purchases = ((b['purchases'] as List?) ?? const []).cast<Map<String, dynamic>>();
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                child: ListTile(
                  title: Text(b['name'] as String? ?? '—', style: Theme.of(context).textTheme.titleMedium),
                  subtitle: Text(
                    [if (b['username'] != null) '@${b['username']}', b['phone'], b['email']].whereType<String>().join(' · '),
                    style: TextStyle(color: context.mutedText),
                  ),
                  trailing: StatusChip(b['status'] as String? ?? 'active'),
                ),
              ),
              const SizedBox(height: 16),
              Text(context.tr('Purchases'), style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 8),
              for (final p in purchases)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(child: Text('${(p['property'] as Map?)?['name'] ?? ''} · ${context.tr('Unit')} ${p['unit_number'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w600))),
                            StatusChip(p['status'] as String? ?? ''),
                          ],
                        ),
                        const SizedBox(height: 8),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(3),
                          child: LinearProgressIndicator(value: asDouble(p['completion_percentage']) / 100, minHeight: 6),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${context.tr('Paid')} ${formatMoney(asDouble(p['total_paid']))} / ${formatMoney(asDouble(p['total_price']))} · ${context.tr('Remaining')} ${formatMoney(asDouble(p['remaining_balance']))}',
                          style: TextStyle(color: context.mutedText, fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

/// Edit a tenant's or buyer's details ([kind] = 'tenant' | 'buyer').
class LandlordContactFormScreen extends ConsumerStatefulWidget {
  const LandlordContactFormScreen({super.key, required this.kind, required this.person});

  final String kind;
  final Map<String, dynamic> person;

  @override
  ConsumerState<LandlordContactFormScreen> createState() => _LandlordContactFormScreenState();
}

class _LandlordContactFormScreenState extends ConsumerState<LandlordContactFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _name = TextEditingController(text: widget.person['name'] as String?);
  late final _username = TextEditingController(text: widget.person['username'] as String?);
  late final _phone = TextEditingController(text: widget.person['phone'] as String?);
  late final _email = TextEditingController(text: widget.person['email'] as String?);
  late final _extra1 = TextEditingController(
      text: (widget.kind == 'tenant' ? widget.person['emergency_contact_name'] : widget.person['address']) as String?);
  late final _extra2 = TextEditingController(
      text: (widget.kind == 'tenant' ? widget.person['emergency_contact_phone'] : widget.person['id_number']) as String?);
  late String _status = (widget.person['status'] as String?) == 'inactive' ? 'inactive' : 'active';
  bool _submitting = false;
  Map<String, String> _errors = const {};

  bool get _isTenant => widget.kind == 'tenant';

  @override
  void dispose() {
    for (final c in [_name, _username, _phone, _email, _extra1, _extra2]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _submitting = true;
      _errors = const {};
    });
    String? text(TextEditingController c) => c.text.trim().isEmpty ? null : c.text.trim();
    final data = {
      'name': _name.text.trim(),
      'username': text(_username),
      'phone': _phone.text.trim(),
      'email': text(_email),
      'status': _status,
      if (_isTenant) ...{'emergency_contact_name': text(_extra1), 'emergency_contact_phone': text(_extra2)},
      if (!_isTenant) ...{'address': text(_extra1), 'id_number': text(_extra2)},
    };
    final repository = ref.read(landlordRepositoryProvider);
    final uuid = widget.person['uuid'] as String;

    try {
      if (_isTenant) {
        await repository.updateTenant(uuid, data);
      } else {
        await repository.updateBuyer(uuid, data);
      }
      if (!mounted) return;
      showToast(context, context.tr('Saved.'), type: ToastType.success);
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _errors = validationErrors(e));
      showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    String? required(String? v) => (v == null || v.trim().isEmpty) ? context.tr('This field is required.') : null;

    return AppScaffold(
      title: _isTenant ? 'Edit tenant' : 'Edit buyer',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(controller: _name, decoration: InputDecoration(labelText: context.tr('Full name'), errorText: _errors['name']), validator: required),
            const SizedBox(height: 16),
            TextFormField(controller: _username, decoration: InputDecoration(labelText: context.tr('Username'), errorText: _errors['username'])),
            const SizedBox(height: 16),
            TextFormField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(labelText: context.tr('Phone'), errorText: _errors['phone']),
              validator: required,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(labelText: context.tr('Email (optional)'), errorText: _errors['email']),
            ),
            const SizedBox(height: 16),
            TextFormField(controller: _extra1, decoration: InputDecoration(labelText: context.tr(_isTenant ? 'Emergency contact' : 'Address'))),
            const SizedBox(height: 16),
            TextFormField(
              controller: _extra2,
              keyboardType: _isTenant ? TextInputType.phone : TextInputType.text,
              decoration: InputDecoration(labelText: context.tr(_isTenant ? 'Emergency contact phone' : 'ID number')),
            ),
            const SizedBox(height: 16),
            SegmentedButton<String>(
              showSelectedIcon: false,
              segments: [
                ButtonSegment(value: 'active', label: Text(context.tr('Active'))),
                ButtonSegment(value: 'inactive', label: Text(context.tr('Inactive'))),
              ],
              selected: {_status},
              onSelectionChanged: (s) => setState(() => _status = s.first),
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: _submitting
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : Text(context.tr('Save changes')),
            ),
          ],
        ),
      ),
    );
  }
}

/// Invoices on the landlord's units - pending by default - with what is
/// still owed for the current filter.
class LandlordInvoicesScreen extends ConsumerWidget {
  const LandlordInvoicesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final repository = ref.watch(landlordRepositoryProvider);

    return AppScaffold(
      title: 'Invoices',
      body: Column(
        children: [
          ValueListenableBuilder<Map<String, dynamic>?>(
            valueListenable: repository.invoiceSummary,
            builder: (context, summary, _) => summary == null
                ? const SizedBox.shrink()
                : Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                    child: Card(
                      margin: EdgeInsets.zero,
                      child: ListTile(
                        leading: const Icon(Icons.account_balance_wallet_outlined),
                        title: Text(formatMoney(asDouble(summary['balance'])),
                            style: TextStyle(fontWeight: FontWeight.w800, color: asDouble(summary['balance']) > 0 ? StatusChip.colorFor(context, 'overdue') : null)),
                        subtitle: Text('${context.tr('Still owed')} · ${summary['count']} ${context.tr('Invoices').toLowerCase()}'),
                      ),
                    ),
                  ),
          ),
          Expanded(
            child: PaginatedListView<Map<String, dynamic>>(
              fetchPage: repository.invoicesPage,
              statusOptions: const ['overdue', 'partially_paid', 'paid', 'all'],
              allLabel: 'Pending',
              emptyLabel: 'No invoices match.',
              itemBuilder: (context, invoice) {
                final person = invoice['person'] as Map<String, dynamic>?;
                final overdue = invoice['is_overdue'] == true;
                return Card(
                  child: ListTile(
                    title: Text(person?['name'] as String? ?? (invoice['invoice_number'] as String? ?? '—'), style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text(
                      '${(invoice['property'] as Map?)?['name'] ?? ''} · ${context.tr('Unit')} ${invoice['unit_number'] ?? ''}\n'
                      '${invoice['invoice_number']} · ${context.tr('Due')} ${formatDate(invoice['due_date'] as String?)}',
                      style: TextStyle(color: overdue ? StatusChip.colorFor(context, 'overdue') : context.mutedText),
                    ),
                    isThreeLine: true,
                    trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(formatMoney(asDouble(invoice['balance_due'])), style: const TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 4),
                        StatusChip(invoice['status'] as String? ?? ''),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
