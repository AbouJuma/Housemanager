import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/l10n/l10n.dart';
import '../../../core/api/token_storage.dart';

import '../../../core/branding/branding.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/brand_widgets.dart';
import '../../auth/providers/auth_provider.dart';

/// Tab-root "More" screen - profile summary, role-specific links that
/// don't earn their own bottom-nav slot (Documents for tenant/landlord),
/// and sign out. Kept generic (role passed in) rather than one class
/// per role, since the only thing that actually varies is which extra
/// links appear.
class MoreScreenLink {
  const MoreScreenLink({required this.icon, required this.label, required this.route, this.permission});

  final IconData icon;
  final String label;
  final String route;

  /// Staff permission needed to see this link (null = everyone).
  final String? permission;
}

class MoreScreen extends ConsumerWidget {
  const MoreScreen({super.key, this.documentsRoute, this.extraLinks});

  final String? documentsRoute;
  final List<MoreScreenLink>? extraLinks;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);
    final profile = auth.profile;
    final branding = ref.watch(brandingProvider);
    final themeMode = ref.watch(themeModeProvider);

    return Scaffold(
      appBar: AppBar(title: Text(context.tr('More'))),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: Theme.of(context).colorScheme.primary,
                foregroundColor: Theme.of(context).colorScheme.onPrimary,
                child: Text(_initials(profile?.name)),
              ),
              title: Text(profile?.name.isNotEmpty == true ? profile!.name : context.tr('Account')),
              subtitle: Text(
                [if (profile?.username != null) '@${profile!.username}', if (profile?.email != null) profile!.email!].join(' · ').isEmpty
                    ? context.tr('My profile')
                    : [if (profile?.username != null) '@${profile!.username}', if (profile?.email != null) profile!.email!].join(' · '),
              ),
              trailing: const Icon(Icons.chevron_right),
              // Profile & password - see ProfileScreen.
              onTap: () => context.push('${_moreRoot(auth.role)}/profile'),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            child: ListTile(
              leading: const BrandLogo(size: 40),
              title: Text(branding.companyName ?? 'House Managers', style: const TextStyle(fontWeight: FontWeight.w600)),
              subtitle: Text('Property management', style: TextStyle(color: context.mutedText)),
            ),
          ),
          SizedBox(height: 20),
          Padding(
            padding: EdgeInsets.only(left: 4, bottom: 8),
            child: Text(context.tr('APPEARANCE'), style: TextStyle(color: context.mutedText, fontSize: 12, fontWeight: FontWeight.w700, letterSpacing: 1)),
          ),
          SegmentedButton<ThemeMode>(
            showSelectedIcon: false,
            segments: [
              ButtonSegment(value: ThemeMode.system, label: Text(context.tr('System')), icon: const Icon(Icons.brightness_auto_outlined)),
              ButtonSegment(value: ThemeMode.light, label: Text(context.tr('Light')), icon: const Icon(Icons.light_mode_outlined)),
              ButtonSegment(value: ThemeMode.dark, label: Text(context.tr('Dark')), icon: const Icon(Icons.dark_mode_outlined)),
            ],
            selected: {themeMode},
            onSelectionChanged: (selection) => ref.read(themeModeProvider.notifier).set(selection.first),
          ),
          const SizedBox(height: 20),
          Padding(
            padding: const EdgeInsets.only(left: 4, bottom: 8),
            child: Text(context.tr('LANGUAGE'), style: TextStyle(color: context.mutedText, fontSize: 12, fontWeight: FontWeight.w700, letterSpacing: 1)),
          ),
          // 'system' = follow the phone's language.
          SegmentedButton<String>(
            showSelectedIcon: false,
            segments: [
              ButtonSegment(value: 'system', label: Text(context.tr('System')), icon: const Icon(Icons.phone_android_outlined)),
              const ButtonSegment(value: 'en', label: Text('English')),
              const ButtonSegment(value: 'sw', label: Text('Kiswahili')),
            ],
            selected: {ref.watch(localeProvider)?.languageCode ?? 'system'},
            onSelectionChanged: (selection) => ref
                .read(localeProvider.notifier)
                .set(selection.first == 'system' ? null : Locale(selection.first)),
          ),
          const SizedBox(height: 20),
          if (documentsRoute != null)
            Card(
              margin: const EdgeInsets.only(bottom: 10),
              child: ListTile(
                leading: const IconTile(Icons.description_outlined, paletteIndex: 5, size: 40),
                title: Text(context.tr('Documents')),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push(documentsRoute!),
              ),
            ),
          for (final link in (extraLinks ?? const <MoreScreenLink>[]).where((l) => l.permission == null || (profile?.can(l.permission!) ?? false)))
            Card(
              margin: const EdgeInsets.only(bottom: 10),
              child: ListTile(
                leading: IconTile(link.icon, paletteIndex: 1, size: 40),
                title: Text(context.tr(link.label)),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push(link.route),
              ),
            ),
          const SizedBox(height: 16),
          Card(
            child: ListTile(
              leading: const IconTile(Icons.logout, paletteIndex: 4, size: 40),
              title: const Text('Sign out', style: TextStyle(color: Colors.red)),
              onTap: () => ref.read(authProvider.notifier).logout(),
            ),
          ),
        ],
      ),
    );
  }

  static String _moreRoot(UserRole? role) => switch (role) {
        UserRole.tenant => '/tenant/more',
        UserRole.landlord => '/landlord/more',
        UserRole.buyer => '/buyer/more',
        _ => '/staff/more',
      };

  String _initials(String? name) {
    if (name == null || name.trim().isEmpty) return '?';
    final parts = name.trim().split(RegExp(r'\s+'));
    final first = parts.first.substring(0, 1);
    final last = parts.length > 1 ? parts.last.substring(0, 1) : '';
    return (first + last).toUpperCase();
  }
}
