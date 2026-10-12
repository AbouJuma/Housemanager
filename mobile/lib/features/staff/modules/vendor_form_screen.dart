import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/l10n/l10n.dart';
import '../../../core/providers.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/toast.dart';

const vendorCategories = [
  'plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping',
  'pest_control', 'general_contractor', 'utility_provider', 'security', 'other',
];

/// Add (vendor == null) or edit a supplier / contractor.
class VendorFormScreen extends ConsumerStatefulWidget {
  const VendorFormScreen({super.key, this.vendor});

  final Map<String, dynamic>? vendor;

  @override
  ConsumerState<VendorFormScreen> createState() => _VendorFormScreenState();
}

class _VendorFormScreenState extends ConsumerState<VendorFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _name = TextEditingController(text: widget.vendor?['name'] as String?);
  late final _phone = TextEditingController(text: widget.vendor?['phone'] as String?);
  late final _email = TextEditingController(text: widget.vendor?['email'] as String?);
  late final _city = TextEditingController(text: (widget.vendor?['address'] as Map?)?['city'] as String?);
  late final _taxId = TextEditingController(text: widget.vendor?['tax_id'] as String?);
  late final _notes = TextEditingController(text: widget.vendor?['notes'] as String?);
  late String _type = (widget.vendor?['type'] as String?) ?? 'company';
  late String? _category = widget.vendor?['category'] as String?;
  late String _status = (widget.vendor?['status'] as String?) ?? 'active';
  bool _submitting = false;
  Map<String, String> _errors = const {};

  bool get _editing => widget.vendor != null;

  @override
  void dispose() {
    for (final c in [_name, _phone, _email, _city, _taxId, _notes]) {
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
      'type': _type,
      'category': _category,
      'status': _status,
      'phone': text(_phone),
      'email': text(_email),
      'city': text(_city),
      'tax_id': text(_taxId),
      'notes': text(_notes),
    };
    final dio = ref.read(apiClientProvider).dio;

    try {
      if (_editing) {
        await dio.put('/vendors/${widget.vendor!['uuid']}', data: data);
      } else {
        await dio.post('/vendors', data: data);
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
    String label(String v) => context.tr(v.replaceAll('_', ' ')[0].toUpperCase() + v.replaceAll('_', ' ').substring(1));
    String? required(Object? v) => (v == null || (v is String && v.trim().isEmpty)) ? context.tr('This field is required.') : null;

    return AppScaffold(
      title: _editing ? 'Edit vendor' : 'Add vendor',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(controller: _name, decoration: InputDecoration(labelText: context.tr('Name'), errorText: _errors['name']), validator: required),
            const SizedBox(height: 16),
            SegmentedButton<String>(
              showSelectedIcon: false,
              segments: [
                ButtonSegment(value: 'company', label: Text(context.tr('Company'))),
                ButtonSegment(value: 'individual', label: Text(context.tr('Individual'))),
              ],
              selected: {_type},
              onSelectionChanged: (s) => setState(() => _type = s.first),
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: vendorCategories.contains(_category) ? _category : null,
              isExpanded: true,
              decoration: InputDecoration(labelText: context.tr('Category'), errorText: _errors['category']),
              items: [for (final c in vendorCategories) DropdownMenuItem(value: c, child: Text(label(c)))],
              onChanged: (v) => setState(() => _category = v),
              validator: required,
            ),
            const SizedBox(height: 16),
            TextFormField(controller: _phone, keyboardType: TextInputType.phone, decoration: InputDecoration(labelText: context.tr('Phone'), errorText: _errors['phone'])),
            const SizedBox(height: 16),
            TextFormField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: InputDecoration(labelText: context.tr('Email (optional)'), errorText: _errors['email'])),
            const SizedBox(height: 16),
            TextFormField(controller: _city, decoration: InputDecoration(labelText: context.tr('City'))),
            const SizedBox(height: 16),
            TextFormField(controller: _taxId, decoration: InputDecoration(labelText: context.tr('TIN'))),
            const SizedBox(height: 16),
            TextFormField(controller: _notes, maxLines: 2, decoration: InputDecoration(labelText: context.tr('Notes'))),
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
                  : Text(context.tr(_editing ? 'Save changes' : 'Add vendor')),
            ),
          ],
        ),
      ),
    );
  }
}
