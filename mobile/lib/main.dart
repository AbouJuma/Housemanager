import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/branding/branding.dart';
import 'core/l10n/l10n.dart';
import 'core/router/app_router.dart';
import 'core/theme/app_theme.dart';
import 'core/widgets/brand_widgets.dart';
import 'features/auth/providers/auth_provider.dart';

void main() {
  runApp(const ProviderScope(child: HouseManagersApp()));
}

class HouseManagersApp extends ConsumerWidget {
  const HouseManagersApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branding = ref.watch(brandingProvider);
    final themeMode = ref.watch(themeModeProvider);
    // null = follow the phone; English or Kiswahili otherwise.
    final locale = ref.watch(localeProvider);
    final light = AppTheme.build(branding, Brightness.light);
    final dark = AppTheme.build(branding, Brightness.dark);

    // While the saved session is being restored, keep showing the same
    // look as the native launch screen (flutter_native_splash) instead
    // of flashing the login form for a moment.
    if (ref.watch(authProvider.select((s) => s.status)) == AuthStatus.checking) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: light,
        darkTheme: dark,
        themeMode: themeMode,
        locale: locale,
        supportedLocales: supportedLocales,
        localizationsDelegates: GlobalMaterialLocalizations.delegates,
        home: const _BrandSplash(),
      );
    }

    return MaterialApp.router(
      title: 'House Managers',
      debugShowCheckedModeBanner: false,
      theme: light,
      darkTheme: dark,
      themeMode: themeMode,
      locale: locale,
      supportedLocales: supportedLocales,
      localizationsDelegates: GlobalMaterialLocalizations.delegates,
      routerConfig: ref.watch(routerProvider),
    );
  }
}

class _BrandSplash extends StatelessWidget {
  const _BrandSplash();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const BrandLogo(size: 96),
            const SizedBox(height: 28),
            SizedBox(width: 28, height: 28, child: CircularProgressIndicator(strokeWidth: 2.5, color: Theme.of(context).colorScheme.primary)),
          ],
        ),
      ),
    );
  }
}
