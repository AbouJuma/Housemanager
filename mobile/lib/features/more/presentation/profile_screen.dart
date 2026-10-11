import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/l10n/l10n.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/toast.dart';
import '../../auth/providers/auth_provider.dart';

/// More -> My profile: the signed-in person's own details (name,
/// username, email, phone) and password - for every role. Backed by
/// App\Http\Controllers\Api\AccountController.
class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _detailsKey = GlobalKey<FormState>();
  final _passwordKey = GlobalKey<FormState>();

  late final _name = TextEditingController();
  late final _username = TextEditingController();
  late final _email = TextEditingController();
  late final _phone = TextEditingController();
  final _current = TextEditingController();
  final _new = TextEditingController();
  final _confirm = TextEditingController();

  bool _savingDetails = false;
  bool _savingPassword = false;
  bool _showPasswords = false;

  @override
  void initState() {
    super.initState();
    final profile = ref.read(authProvider).profile;
    _name.text = profile?.name ?? '';
    _username.text = profile?.username ?? '';
    _email.text = profile?.email ?? '';
    _phone.text = profile?.phone ?? '';
  }

  @override
  void dispose() {
    for (final controller in [_name, _username, _email, _phone, _current, _new, _confirm]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _saveDetails() async {
    if (!_detailsKey.currentState!.validate()) return;
    setState(() => _savingDetails = true);
    try {
      await ref.read(authProvider.notifier).updateAccount({
        'name': _name.text.trim(),
        'username': _username.text.trim(),
        'email': _email.text.trim().isEmpty ? null : _email.text.trim(),
        'phone': _phone.text.trim().isEmpty ? null : _phone.text.trim(),
      });
      if (mounted) showToast(context, 'Your profile has been updated.', type: ToastType.success);
    } catch (e) {
      if (mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _savingDetails = false);
    }
  }

  Future<void> _savePassword() async {
    if (!_passwordKey.currentState!.validate()) return;
    setState(() => _savingPassword = true);
    try {
      final message = await ref.read(authProvider.notifier).changePassword(
            current: _current.text,
            password: _new.text,
            confirmation: _confirm.text,
          );
      _current.clear();
      _new.clear();
      _confirm.clear();
      if (mounted) showToast(context, message, type: ToastType.success);
    } catch (e) {
      if (mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _savingPassword = false);
    }
  }

  String? _required(String? value) => (value == null || value.trim().isEmpty) ? context.tr('This field is required.') : null;

  @override
  Widget build(BuildContext context) {
    final obscure = !_showPasswords;

    return AppScaffold(
      title: 'My profile',
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 32),
        children: [
          _Section(
            icon: Icons.person_outline,
            title: context.tr('Account details'),
            child: Form(
              key: _detailsKey,
              child: Column(
                children: [
                  TextFormField(
                    controller: _name,
                    decoration: InputDecoration(labelText: context.tr('Name'), prefixIcon: const Icon(Icons.badge_outlined)),
                    textCapitalization: TextCapitalization.words,
                    validator: _required,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _username,
                    decoration: InputDecoration(
                      labelText: context.tr('Username'),
                      prefixIcon: const Icon(Icons.alternate_email),
                      helperText: context.tr('Used to sign in, along with your phone or email.'),
                    ),
                    autocorrect: false,
                    validator: (value) {
                      final v = value?.trim() ?? '';
                      if (v.length < 3) return context.tr('A username needs at least 3 characters.');
                      if (!RegExp(r'^[A-Za-z0-9._-]+$').hasMatch(v)) {
                        return context.tr('A username can only use letters, numbers, dots, dashes and underscores (no spaces).');
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _email,
                    decoration: InputDecoration(labelText: context.tr('Email'), prefixIcon: const Icon(Icons.email_outlined)),
                    keyboardType: TextInputType.emailAddress,
                    autocorrect: false,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _phone,
                    decoration: InputDecoration(labelText: context.tr('Phone'), prefixIcon: const Icon(Icons.phone_outlined)),
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: _savingDetails ? null : _saveDetails,
                    child: _savingDetails
                        ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2))
                        : Text(context.tr('Save changes')),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _Section(
            icon: Icons.lock_outline,
            title: context.tr('Change password'),
            child: Form(
              key: _passwordKey,
              child: Column(
                children: [
                  TextFormField(
                    controller: _current,
                    obscureText: obscure,
                    decoration: InputDecoration(labelText: context.tr('Current password'), prefixIcon: const Icon(Icons.key_outlined)),
                    validator: _required,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _new,
                    obscureText: obscure,
                    decoration: InputDecoration(labelText: context.tr('New password'), prefixIcon: const Icon(Icons.lock_reset)),
                    validator: (value) =>
                        (value == null || value.length < 8) ? context.tr('Use at least 8 characters.') : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _confirm,
                    obscureText: obscure,
                    decoration: InputDecoration(labelText: context.tr('Confirm new password'), prefixIcon: const Icon(Icons.lock_outline)),
                    validator: (value) => value != _new.text ? context.tr('The passwords do not match.') : null,
                  ),
                  CheckboxListTile(
                    value: _showPasswords,
                    onChanged: (value) => setState(() => _showPasswords = value ?? false),
                    title: Text(context.tr('Show passwords')),
                    controlAffinity: ListTileControlAffinity.leading,
                    contentPadding: EdgeInsets.zero,
                  ),
                  FilledButton.tonal(
                    style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
                    onPressed: _savingPassword ? null : _savePassword,
                    child: _savingPassword
                        ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2))
                        : Text(context.tr('Change password')),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    context.tr('Other devices signed in to your account will be signed out.'),
                    style: TextStyle(color: context.mutedText, fontSize: 12),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.icon, required this.title, required this.child});

  final IconData icon;
  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Icon(icon, color: Theme.of(context).colorScheme.primary),
                  const SizedBox(width: 10),
                  Text(title, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                ],
              ),
              const SizedBox(height: 16),
              child,
            ],
          ),
        ),
      );
}
