import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/l10n/l10n.dart';
import '../../../core/providers.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/toast.dart';

const companyStatuses = ['trial', 'active', 'suspended', 'cancelled'];

/// Super admin: add a company together with its admin (who gets the Admin
/// role and their login details), or edit / suspend one.
class CompanyFormScreen extends ConsumerStatefulWidget {
  const CompanyFormScreen({super.key, this.company});

  final Map<String, dynamic>? company;

  @override
  ConsumerState<CompanyFormScreen> createState() => _CompanyFormScreenState();
}

class _CompanyFormScreenState extends ConsumerState<CompanyFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _name = TextEditingController(text: widget.company?['name'] as String?);
  late final _email = TextEditingController(text: widget.company?['email'] as String?);
  late final _phone = TextEditingController(text: widget.company?['phone'] as String?);
  final _adminName = TextEditingController();
  final _adminEmail = TextEditingController();
  final _adminPhone = TextEditingController();
  late String _status = (widget.company?['status'] as String?) ?? 'trial';
  bool _submitting = false;
  Map<String, String> _errors = const {};

  bool get _editing => widget.company != null;

  @override
  void dispose() {
    for (final c in [_name, _email, _phone, _adminName, _adminEmail, _adminPhone]) {
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
    final dio = ref.read(apiClientProvider).dio;

    try {
      if (_editing) {
        await dio.put('/companies/${widget.company!['uuid']}', data: {
          'name': _name.text.trim(),
          'email': text(_email),
          'phone': text(_phone),
          'status': _status,
        });
        if (!mounted) return;
        showToast(context, context.tr('Saved.'), type: ToastType.success);
        Navigator.of(context).pop(true);
        return;
      }

      final response = await dio.post('/companies', data: {
        'name': _name.text.trim(),
        'email': text(_email),
        'phone': text(_phone),
        'admin_name': _adminName.text.trim(),
        'admin_email': _adminEmail.text.trim(),
        'admin_phone': text(_adminPhone),
      });
      if (!mounted) return;
      final admin = ((response.data as Map<String, dynamic>)['admin'] as Map<String, dynamic>?) ?? const {};
      setState(() => _submitting = false);
      await _showAdminLogin(admin['username'] as String?, admin['password'] as String?, admin['email'] as String?);
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _errors = validationErrors(e));
      showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _showAdminLogin(String? username, String? password, String? email) => showDialog<void>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: Text(dialogContext.tr('Company created')),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('${dialogContext.tr('The admin\'s login details were sent to')} ${email ?? ''}.'),
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
    String? required(String? v) => (v == null || v.trim().isEmpty) ? context.tr('This field is required.') : null;
    String label(String v) => context.tr(v[0].toUpperCase() + v.substring(1));

    return AppScaffold(
      title: _editing ? 'Edit company' : 'Add company',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextFormField(controller: _name, decoration: InputDecoration(labelText: context.tr('Company name'), errorText: _errors['name']), validator: required),
            const SizedBox(height: 16),
            TextFormField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: InputDecoration(labelText: context.tr('Company email (optional)'), errorText: _errors['email'])),
            const SizedBox(height: 16),
            TextFormField(controller: _phone, keyboardType: TextInputType.phone, decoration: InputDecoration(labelText: context.tr('Company phone (optional)'), errorText: _errors['phone'])),
            if (_editing) ...[
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                initialValue: companyStatuses.contains(_status) ? _status : 'trial',
                decoration: InputDecoration(labelText: context.tr('Status')),
                items: [for (final s in companyStatuses) DropdownMenuItem(value: s, child: Text(label(s)))],
                onChanged: (v) => setState(() => _status = v ?? _status),
              ),
            ] else ...[
              const SizedBox(height: 24),
              Text(context.tr('Company admin'), style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 4),
              Text(context.tr('Gets the Admin role and their login details by email (and SMS if a phone is given).'),
                  style: Theme.of(context).textTheme.bodySmall),
              const SizedBox(height: 12),
              TextFormField(controller: _adminName, decoration: InputDecoration(labelText: context.tr('Full name'), errorText: _errors['admin_name']), validator: required),
              const SizedBox(height: 16),
              TextFormField(
                controller: _adminEmail,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(labelText: context.tr('Email'), errorText: _errors['admin_email']),
                validator: required,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _adminPhone,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(labelText: context.tr('Phone (optional)'), helperText: context.tr('Also their first password.'), errorText: _errors['admin_phone']),
              ),
            ],
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: _submitting
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : Text(context.tr(_editing ? 'Save changes' : 'Add company')),
            ),
          ],
        ),
      ),
    );
  }
}
