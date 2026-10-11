import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../l10n/l10n.dart';

/// Bottom-nav shell wrapping every role's tab set - one Navigator per
/// tab (via StatefulShellRoute.indexedStack), so switching tabs
/// preserves each tab's own scroll position/push stack instead of
/// resetting it.
class AppShell extends StatelessWidget {
  const AppShell({super.key, required this.navigationShell, required this.destinations});

  final StatefulNavigationShell navigationShell;
  final List<NavigationDestination> destinations;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: navigationShell,
      // Floating-sheet look: rounded top corners and a soft shadow
      // instead of a flat bar.
      bottomNavigationBar: DecoratedBox(
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.4 : 0.08),
              blurRadius: 24,
              offset: const Offset(0, -6),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          child: NavigationBar(
            selectedIndex: navigationShell.currentIndex,
            // Labels are English in the router; shown in the app's language.
            destinations: [
              for (final d in destinations)
                NavigationDestination(icon: d.icon, selectedIcon: d.selectedIcon, label: context.tr(d.label)),
            ],
            labelBehavior: NavigationDestinationLabelBehavior.onlyShowSelected,
            onDestinationSelected: (index) => navigationShell.goBranch(
              index,
              initialLocation: index == navigationShell.currentIndex,
            ),
          ),
        ),
      ),
    );
  }
}
