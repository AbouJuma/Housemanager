import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/widgets/toast.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../data/staff_providers.dart';
import 'staff_tenant_detail_screen.dart';

const _tenantTypes = ['individual', 'company'];
const _tenantStatuses = ['active', 'inactive', 'blacklisted'];

/// One screen for both create (uuid == null) and edit (uuid != null) -
/// the same fields either way, just pre-filled and PUT instead of POST.
class StaffTenantFormScreen extends ConsumerStatefulWidget {
  const StaffTenantFormScreen({super.key, this.uuid});

  final String? uuid;

  @override
  ConsumerState<StaffTenantFormScreen> createState() => _StaffTenantFormScreenState();
}

class _StaffTenantFormScreenState extends ConsumerState<StaffTenantFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _notesController = TextEditingController();
  String _type = 'individual';
  String _status = 'active';
  bool _loading = false;
  bool _submitting = false;
  String? _error;

  bool get _isEditing => widget.uuid != null;

  @override
  void initState() {
    super.initState();
    if (_isEditing) _loadExisting();
  }

  Future<void> _loadExisting() async {
    setState(() => _loading = true);
    try {
      final tenant = await ref.read(staffRepositoryProvider).tenant(widget.uuid!);
      _nameController.text = tenant.name;
      _emailController.text = tenant.email ?? '';
      _phoneController.text = tenant.phone ?? '';
      _notesController.text = tenant.notes ?? '';
      _type = tenant.type;
      _status = tenant.status;
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    final data = {
      'name': _nameController.text.trim(),
      'type': _type,
      'status': _status,
      if (_emailController.text.trim().isNotEmpty) 'email': _emailController.text.trim(),
      if (_phoneController.text.trim().isNotEmpty) 'phone': _phoneController.text.trim(),
      if (_notesController.text.trim().isNotEmpty) 'notes': _notesController.text.trim(),
    };

    try {
      final repository = ref.read(staffRepositoryProvider);
      if (_isEditing) {
        await repository.updateTenant(widget.uuid!, data);
        ref.invalidate(staffTenantProvider(widget.uuid!));
      } else {
        await repository.createTenant(data);
      }
      // StaffTenantsScreen's list is a plain PaginatedListView (no shared
      // Riverpod cache to invalidate) - it re-fetches on next visit/pull-to-
      // refresh, so returning `true` here lets the caller force a rebuild.
      if (mounted) Navigator.of(context).pop(true);
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
    return AppScaffold(
      title: _isEditing ? 'Edit Tenant' : 'New Tenant',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  TextFormField(
                    controller: _nameController,
                    decoration: InputDecoration(labelText: context.tr('Name')),
                    validator: (value) => (value == null || value.isEmpty) ? 'Enter a name' : null,
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: _type,
                    decoration: InputDecoration(labelText: context.tr('Type')),
                    items: _tenantTypes.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                    onChanged: (value) => setState(() => _type = value!),
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: _status,
                    decoration: InputDecoration(labelText: context.tr('Status')),
                    items: _tenantStatuses.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
                    onChanged: (value) => setState(() => _status = value!),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _emailController,
                    decoration: InputDecoration(labelText: context.tr('Email')),
                    keyboardType: TextInputType.emailAddress,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _phoneController,
                    decoration: InputDecoration(labelText: context.tr('Phone')),
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _notesController,
                    decoration: InputDecoration(labelText: context.tr('Notes')),
                    maxLines: 3,
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 16),
                    Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
                  ],
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _submitting ? null : _submit,
                    child: _submitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                        : Text(_isEditing ? 'Save changes' : 'Create tenant'),
                  ),
                ],
              ),
            ),
    );
  }
}
