import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/widgets/toast.dart';

import '../../../core/api/token_storage.dart';
import '../../../core/branding/branding.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/brand_widgets.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  UserRole _role = UserRole.tenant;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await ref.read(authProvider.notifier).login(
            role: _role,
            email: _emailController.text.trim(),
            password: _passwordController.text,
          );
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
    final branding = ref.watch(brandingProvider);
    final scheme = Theme.of(context).colorScheme;
    final heroHeight = MediaQuery.sizeOf(context).height * 0.36;

    return Scaffold(
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Photo hero - the same villa shot as the web sign-in page.
            SizedBox(
              height: heroHeight.clamp(240, 340),
              width: double.infinity,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  Image.asset('assets/images/villa.jpg', fit: BoxFit.cover),
                  DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          AppColors.ink.withValues(alpha: 0.55),
                          AppColors.ink.withValues(alpha: 0.15),
                          AppColors.ink.withValues(alpha: 0.85),
                        ],
                      ),
                    ),
                  ),
                  SafeArea(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(24, 16, 24, 44),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const BrandLogo(size: 44),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  branding.companyName ?? 'House Managers',
                                  style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const _LanguageToggle(),
                            ],
                          ),
                          const Spacer(),
                          Text(
                            context.tr('Every property, lease\nand payment - in one place.'),
                            style: const TextStyle(color: Colors.white, fontSize: 26, height: 1.15, fontWeight: FontWeight.w800, letterSpacing: -0.6),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Transform.translate(
              offset: const Offset(0, -24),
              child: Container(
                decoration: BoxDecoration(
                  color: Theme.of(context).scaffoldBackgroundColor,
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
                ),
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
                child: Center(
                  child: ConstrainedBox(
                    constraints: BoxConstraints(maxWidth: 440),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text('Welcome back', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
                          SizedBox(height: 4),
                          Text('Choose how you use the app, then sign in.', style: TextStyle(color: context.mutedText)),
                          SizedBox(height: 20),
                          SegmentedButton<UserRole>(
                            showSelectedIcon: false,
                            segments: [
                              ButtonSegment(value: UserRole.tenant, label: Text(context.tr('Tenant'))),
                              ButtonSegment(value: UserRole.landlord, label: Text(context.tr('Landlord'))),
                              ButtonSegment(value: UserRole.buyer, label: Text(context.tr('Buyer'))),
                              ButtonSegment(value: UserRole.staff, label: Text(context.tr('Staff'))),
                            ],
                            selected: {_role},
                            onSelectionChanged: (selection) => setState(() => _role = selection.first),
                            style: const ButtonStyle(visualDensity: VisualDensity.compact),
                          ),
                          const SizedBox(height: 20),
                          TextFormField(
                            controller: _emailController,
                            decoration: InputDecoration(
                              labelText: context.tr('Email, username or phone'),
                              prefixIcon: Icon(Icons.person_outline),
                            ),
                            keyboardType: TextInputType.text,
                            autocorrect: false,
                            textInputAction: TextInputAction.next,
                            autofillHints: const [AutofillHints.username],
                            validator: (value) => (value == null || value.trim().isEmpty) ? 'Enter your email, username or phone number' : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _passwordController,
                            decoration: InputDecoration(labelText: context.tr('Password'), prefixIcon: Icon(Icons.lock_outline)),
                            obscureText: true,
                            autofillHints: const [AutofillHints.password],
                            validator: (value) => (value == null || value.isEmpty) ? 'Enter your password' : null,
                            onFieldSubmitted: (_) => _submit(),
                          ),
                          if (_error != null) ...[
                            const SizedBox(height: 14),
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(12)),
                              child: Text(_error!, style: TextStyle(color: scheme.onErrorContainer)),
                            ),
                          ],
                          const SizedBox(height: 24),
                          FilledButton(
                            onPressed: _submitting ? null : _submit,
                            child: _submitting
                                ? SizedBox(height: 22, width: 22, child: CircularProgressIndicator(strokeWidth: 2, color: scheme.onPrimary))
                                : Text(context.tr('Sign in')),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// EN | SW pill on the login photo - so people can pick their language
/// before signing in (also on the More screen afterwards).
class _LanguageToggle extends ConsumerWidget {
  const _LanguageToggle();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final current = context.languageCode;

    Widget option(String code, String label) {
      final selected = current == code;
      return GestureDetector(
        onTap: () => ref.read(localeProvider.notifier).set(Locale(code)),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: selected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(999),
          ),
          child: Text(
            label,
            style: TextStyle(
              color: selected ? AppColors.ink : Colors.white,
              fontWeight: FontWeight.w700,
              fontSize: 12,
            ),
          ),
        ),
      );
    }

    return Semantics(
      label: 'Language',
      child: Container(
        padding: const EdgeInsets.all(3),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.16),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: Colors.white.withValues(alpha: 0.25)),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [option('en', 'EN'), option('sw', 'SW')]),
      ),
    );
  }
}
