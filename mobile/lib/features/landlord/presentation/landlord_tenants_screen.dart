import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/api/json_helpers.dart';
import '../../../core/api/paginated_list.dart';
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
import 'landlord_people_screens.dart';

/// The landlord's tenants: anyone renting one of their units, plus
/// tenants they added themselves.
class LandlordTenantsScreen extends ConsumerStatefulWidget {
  const LandlordTenantsScreen({super.key});

  @override
  ConsumerState<LandlordTenantsScreen> createState() => _LandlordTenantsScreenState();
}

class _LandlordTenantsScreenState extends ConsumerState<LandlordTenantsScreen> {
  int _refreshToken = 0;
  String _search = '';

  Future<void> _add() async {
    final added = await context.push<bool>('/landlord/tenants/new');
    if (added == true && mounted) setState(() => _refreshToken++);
  }

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(landlordRepositoryProvider);

    return AppScaffold(
      title: 'My Tenants',
      floatingActionButton: !hasPermission(ref, 'tenants.create')
          ? null
          : FloatingActionButton(tooltip: context.tr('Add tenant'), onPressed: _add, child: const Icon(Icons.person_add_alt_1)),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: TextField(
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.search),
                hintText: context.tr('Search by name or phone'),
                isDense: true,
              ),
              textInputAction: TextInputAction.search,
              onSubmitted: (value) => setState(() {
                _search = value.trim();
                _refreshToken++;
              }),
            ),
          ),
          Expanded(
            child: PaginatedListView<Map<String, dynamic>>(
              key: ValueKey('$_refreshToken|$_search'),
              fetchPage: (page, ListFilters filters) => repository.tenantsPage(page, filters, search: _search),
              statusOptions: const ['with_lease', 'no_lease'],
              showDateRange: false,
              emptyLabel: 'No tenants yet.',
              itemBuilder: (context, tenant) => _TenantTile(
                tenant: tenant,
                onTap: () => context.push('/landlord/tenants/${tenant['uuid']}'),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TenantTile extends StatelessWidget {
  const _TenantTile({required this.tenant, required this.onTap});

  final Map<String, dynamic> tenant;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final lease = tenant['current_lease'] as Map<String, dynamic>?;
    final property = lease?['property'] as Map<String, dynamic>?;
    final name = tenant['name'] as String? ?? '—';

    return Card(
      child: ListTile(
        onTap: onTap,
        leading: CircleAvatar(child: Text(name.isEmpty ? '?' : name[0].toUpperCase())),
        title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Text(
          lease == null
              ? [tenant['phone'] as String?, context.tr('No lease yet')].whereType<String>().join(' · ')
              : '${property?['name'] ?? ''} · ${context.tr('Unit')} ${lease['unit_number'] ?? ''}',
          style: TextStyle(color: context.mutedText),
        ),
        trailing: lease == null ? null : StatusChip(lease['status'] as String? ?? ''),
      ),
    );
  }
}

class LandlordTenantDetailScreen extends ConsumerWidget {
  const LandlordTenantDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tenant = ref.watch(landlordTenantProvider(uuid));

    return AppScaffold(
      title: 'Tenant',
      actions: [
        if (hasPermission(ref, 'tenants.update') && tenant.hasValue)
          IconButton(
            icon: const Icon(Icons.edit_outlined),
            tooltip: context.tr('Edit'),
            onPressed: () async {
              final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(
                builder: (_) => LandlordContactFormScreen(kind: 'tenant', person: tenant.value!),
              ));
              if (saved == true) ref.invalidate(landlordTenantProvider(uuid));
            },
          ),
      ],
      body: tenant.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordTenantProvider(uuid))),
        data: (t) {
          final leases = ((t['leases'] as List<dynamic>?) ?? const []).cast<Map<String, dynamic>>();
          Widget row(String label, String? value) => value == null || value.isEmpty
              ? const SizedBox.shrink()
              : Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    children: [
                      Text(context.tr(label), style: TextStyle(color: context.mutedText)),
                      const SizedBox(width: 12),
                      Expanded(child: Text(value, textAlign: TextAlign.end)),
                    ],
                  ),
                );

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
                        children: [
                          Expanded(child: Text(t['name'] as String? ?? '—', style: Theme.of(context).textTheme.titleMedium)),
                          StatusChip(t['status'] as String? ?? 'active'),
                        ],
                      ),
                      const Divider(height: 24),
                      row('Username', t['username'] == null ? null : '@${t['username']}'),
                      row('Phone', t['phone'] as String?),
                      row('Email', t['email'] as String?),
                      row('Emergency contact', [t['emergency_contact_name'], t['emergency_contact_phone']].whereType<String>().join(' · ')),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Text(context.tr('Leases'), style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 8),
              if (leases.isEmpty)
                Card(child: ListTile(leading: const Icon(Icons.info_outline), title: Text(context.tr('No lease yet')))),
              for (final lease in leases)
                Card(
                  child: ListTile(
                    title: Text('${(lease['property'] as Map?)?['name'] ?? ''} · ${context.tr('Unit')} ${lease['unit_number'] ?? ''}'),
                    subtitle: Text(
                      '${formatMoney(asDouble(lease['rent_amount']))} · ${formatDate(lease['start_date'] as String?)} – ${formatDate(lease['end_date'] as String?)}',
                      style: TextStyle(color: context.mutedText),
                    ),
                    trailing: StatusChip(lease['status'] as String? ?? ''),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

/// Add a tenant: they get a username and their phone number as first
/// password, shown once here to pass on (they're also sent it).
class LandlordTenantFormScreen extends ConsumerStatefulWidget {
  const LandlordTenantFormScreen({super.key});

  @override
  ConsumerState<LandlordTenantFormScreen> createState() => _LandlordTenantFormScreenState();
}

class _LandlordTenantFormScreenState extends ConsumerState<LandlordTenantFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _username = TextEditingController();
  final _idNumber = TextEditingController();
  String _type = 'individual';
  String? _idType;
  bool _submitting = false;
  Map<String, String> _fieldErrors = const {};

  static const _idTypes = ['national_id', 'passport', 'driving_license', 'voter_id', 'other'];

  @override
  void dispose() {
    for (final c in [_name, _phone, _email, _username, _idNumber]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _submitting = true;
      _fieldErrors = const {};
    });

    String? text(TextEditingController c) => c.text.trim().isEmpty ? null : c.text.trim();
    try {
      final result = await ref.read(landlordRepositoryProvider).createTenant({
        'name': _name.text.trim(),
        'type': _type,
        'phone': _phone.text.trim(),
        'email': text(_email),
        'username': text(_username),
        'identification_type': _idType,
        'identification_number': text(_idNumber),
      });
      if (!mounted) return;
      setState(() => _submitting = false);
      final login = (result['login'] as Map<String, dynamic>?) ?? const {};
      await _showLogin(login['username'] as String?, login['password'] as String?);
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _fieldErrors = validationErrors(e));
      showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _showLogin(String? username, String? password) => showDialog<void>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: Text(dialogContext.tr('Tenant added')),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(dialogContext.tr('They can sign in to the app and portal with:')),
              const SizedBox(height: 12),
              SelectableText('${dialogContext.tr('Username')}: ${username ?? '—'}\n${dialogContext.tr('Password')}: ${password ?? '—'}',
                  style: const TextStyle(fontWeight: FontWeight.w600)),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                Clipboard.setData(ClipboardData(text: '${username ?? ''} / ${password ?? ''}'));
                showToast(dialogContext, dialogContext.tr('Copied.'), type: ToastType.success);
              },
              child: Text(dialogContext.tr('Copy')),
            ),
            FilledButton(onPressed: () => Navigator.of(dialogContext).pop(), child: Text(dialogContext.tr('Done'))),
          ],
        ),
      );

  @override
  Widget build(BuildContext context) {
    String label(String value) => context.tr(value.replaceAll('_', ' ')[0].toUpperCase() + value.replaceAll('_', ' ').substring(1));
    String? required(String? v) => (v == null || v.trim().isEmpty) ? context.tr('This field is required.') : null;

    return AppScaffold(
      title: 'Add tenant',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(
              controller: _name,
              decoration: InputDecoration(labelText: context.tr('Full name'), errorText: _fieldErrors['name']),
              validator: required,
            ),
            const SizedBox(height: 16),
            SegmentedButton<String>(
              showSelectedIcon: false,
              segments: [
                ButtonSegment(value: 'individual', label: Text(context.tr('Individual'))),
                ButtonSegment(value: 'company', label: Text(context.tr('Company'))),
              ],
              selected: {_type},
              onSelectionChanged: (s) => setState(() => _type = s.first),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: context.tr('Phone'),
                helperText: context.tr('The tenant signs in with this number, and it is their first password.'),
                helperMaxLines: 2,
                errorText: _fieldErrors['phone'],
              ),
              validator: required,
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(labelText: context.tr('Email (optional)'), errorText: _fieldErrors['email']),
            ),
            const SizedBox(height: 16),
            TextFormField(
              controller: _username,
              decoration: InputDecoration(
                labelText: context.tr('Username (optional)'),
                helperText: context.tr('Leave blank to create one from the name.'),
                errorText: _fieldErrors['username'],
              ),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: _idType,
              isExpanded: true,
              decoration: InputDecoration(labelText: context.tr('ID type')),
              items: [for (final t in _idTypes) DropdownMenuItem(value: t, child: Text(label(t)))],
              onChanged: (v) => setState(() => _idType = v),
            ),
            const SizedBox(height: 16),
            TextFormField(controller: _idNumber, decoration: InputDecoration(labelText: context.tr('ID number'))),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: _submitting
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : Text(context.tr('Add tenant')),
            ),
          ],
        ),
      ),
    );
  }
}
