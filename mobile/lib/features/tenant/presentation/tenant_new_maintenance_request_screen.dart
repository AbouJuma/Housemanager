import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/widgets/toast.dart';

import '../../../core/providers.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../data/tenant_providers.dart';

const _categories = [
  'plumbing',
  'electrical',
  'hvac',
  'cleaning',
  'landscaping',
  'pest_control',
  'general_contractor',
  'utility_provider',
  'security',
  'other',
];
const _priorities = ['low', 'medium', 'high', 'urgent'];

class TenantNewMaintenanceRequestScreen extends ConsumerStatefulWidget {
  const TenantNewMaintenanceRequestScreen({super.key});

  @override
  ConsumerState<TenantNewMaintenanceRequestScreen> createState() => _TenantNewMaintenanceRequestScreenState();
}

class _TenantNewMaintenanceRequestScreenState extends ConsumerState<TenantNewMaintenanceRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  String _category = _categories.first;
  String _priority = 'medium';
  String? _selectedLeaseUuid;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _submit(String leaseId) async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      await apiClient.dio.post('/portal/tenant/maintenance-requests', data: {
        'lease_id': leaseId,
        'title': _titleController.text.trim(),
        'description': _descriptionController.text.trim(),
        'category': _category,
        'priority': _priority,
      });

      ref.invalidate(tenantMaintenanceRequestsProvider);

      if (mounted) Navigator.of(context).pop();
    } catch (e) {
      // Any failure - validation (422), connection, server - as a
      // readable message, never a raw exception.
      final message = friendlyErrorMessage(e);
      setState(() => _error = message);
      if (mounted) showToast(context, message, type: ToastType.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final leases = ref.watch(tenantLeasesProvider);

    return AppScaffold(
      title: 'New Maintenance Request',
      body: leases.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(tenantLeasesProvider)),
        data: (leases) {
          if (leases.isEmpty) {
            return Center(child: Text(context.tr('You need an active lease to file a maintenance request.')));
          }

          // Defaults to the tenant's only/most recent lease; the picker
          // below lets a multi-lease tenant choose a different one. The
          // backend resolves this uuid directly (see
          // StoreMaintenanceRequestRequest on the server) - it never
          // needs the internal numeric id, which LeaseResource doesn't
          // even expose to the app.
          _selectedLeaseUuid ??= leases.first.uuid;

          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (leases.length > 1)
                  DropdownButtonFormField<String>(
                    initialValue: _selectedLeaseUuid,
                    decoration: InputDecoration(labelText: context.tr('Lease')),
                    items: leases
                        .map((lease) => DropdownMenuItem(value: lease.uuid, child: Text(lease.unit?.label ?? 'Lease')))
                        .toList(),
                    onChanged: (value) => setState(() => _selectedLeaseUuid = value),
                  ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _titleController,
                  decoration: InputDecoration(labelText: context.tr('Title')),
                  validator: (value) => (value == null || value.isEmpty) ? 'Enter a title' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionController,
                  decoration: InputDecoration(labelText: context.tr('Description')),
                  maxLines: 4,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: _category,
                  decoration: InputDecoration(labelText: context.tr('Category')),
                  items: _categories
                      .map((c) => DropdownMenuItem(value: c, child: Text(c.replaceAll('_', ' '))))
                      .toList(),
                  onChanged: (value) => setState(() => _category = value!),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: _priority,
                  decoration: InputDecoration(labelText: context.tr('Priority')),
                  items: _priorities.map((p) => DropdownMenuItem(value: p, child: Text(p))).toList(),
                  onChanged: (value) => setState(() => _priority = value!),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 16),
                  Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
                ],
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: _submitting ? null : () => _submit(_selectedLeaseUuid!),
                  child: _submitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                      : Text(context.tr('Submit request')),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
