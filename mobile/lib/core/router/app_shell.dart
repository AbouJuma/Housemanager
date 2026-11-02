import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../l10n/l10n.dart';
import '../quick_actions.dart';
import '../theme/app_theme.dart';
import '../widgets/motion.dart';

/// Bottom navigation for every role: a floating dark capsule with the
/// tabs, and - when the person has something to start - a raised gold
/// centre button that opens their quick actions. One Navigator per tab
/// (StatefulShellRoute.indexedStack) keeps each tab's scroll and stack.
class AppShell extends ConsumerWidget {
  const AppShell({super.key, required this.navigationShell, required this.destinations});

  final StatefulNavigationShell navigationShell;
  final List<NavigationDestination> destinations;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final actions = ref.watch(quickActionsProvider);
    final items = [
      for (var i = 0; i < destinations.length; i++)
        NavItem(
          icon: destinations[i].icon,
          selectedIcon: destinations[i].selectedIcon ?? destinations[i].icon,
          label: destinations[i].label,
          selected: navigationShell.currentIndex == i,
          onTap: () => navigationShell.goBranch(i, initialLocation: i == navigationShell.currentIndex),
        ),
    ];
    // The gold button sits in the middle of the tabs.
    if (actions.isNotEmpty) {
      items.insert(items.length ~/ 2, NavItem.action(onTap: () => showQuickActions(context, actions)));
    }

    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 6, 14, 10),
          child: Container(
            height: 70,
            padding: const EdgeInsets.symmetric(horizontal: 6),
            decoration: BoxDecoration(
              color: AppColors.capsule,
              borderRadius: BorderRadius.circular(36),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.25), blurRadius: 24, offset: const Offset(0, 10))],
            ),
            child: Row(children: [for (final item in items) Expanded(child: item)]),
          ),
        ),
      ).motion((a) => a.slideY(begin: 1, end: 0, duration: 450.ms, curve: Curves.easeOutCubic)),
    );
  }
}

/// One tab of the capsule (or, with [NavItem.action], the gold button).
class NavItem extends StatelessWidget {
  const NavItem({
    super.key,
    required this.icon,
    required this.selectedIcon,
    required this.label,
    required this.selected,
    required this.onTap,
  }) : isAction = false;

  const NavItem.action({super.key, required this.onTap})
      : icon = const Icon(LucideIcons.plus),
        selectedIcon = const Icon(LucideIcons.plus),
        label = '',
        selected = false,
        isAction = true;

  final Widget icon;
  final Widget selectedIcon;
  final String label;
  final bool selected;
  final bool isAction;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    if (isAction) {
      return Center(
        child: Tooltip(
          message: context.tr('Quick actions'),
          child: GestureDetector(
            onTap: onTap,
            child: Container(
              width: 54,
              height: 54,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFFF2C14E), AppColors.goldDeep]),
                boxShadow: [BoxShadow(color: AppColors.gold.withValues(alpha: 0.45), blurRadius: 18, offset: const Offset(0, 6))],
              ),
              child: const Icon(LucideIcons.plus, color: Color(0xFF1A1405), size: 26),
            ),
          ),
        ),
      );
    }

    final color = selected ? Colors.white : Colors.white.withValues(alpha: 0.5);

    return Semantics(
      selected: selected,
      button: true,
      label: context.tr(label),
      child: InkResponse(
        onTap: onTap,
        radius: 34,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              curve: Curves.easeOut,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 5),
              decoration: BoxDecoration(
                color: selected ? Colors.white.withValues(alpha: 0.12) : Colors.transparent,
                borderRadius: BorderRadius.circular(999),
              ),
              child: IconTheme(
                data: IconThemeData(color: color, size: 21),
                child: selected ? selectedIcon : icon,
              ),
            ),
            const SizedBox(height: 3),
            Text(
              context.tr(label),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(color: color, fontSize: 11, fontWeight: selected ? FontWeight.w700 : FontWeight.w500),
            ),
          ],
        ),
      ),
    );
  }
}

/// The quick-actions sheet: round icon buttons in a grid.
void showQuickActions(BuildContext context, List<QuickAction> actions) {
  showModalBottomSheet<void>(
    context: context,
    builder: (sheet) => SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(sheet.tr('Quick actions'), style: Theme.of(sheet).textTheme.titleLarge),
            const SizedBox(height: 18),
            Wrap(
              spacing: 12,
              runSpacing: 16,
              children: [
                for (final (i, action) in actions.indexed)
                  SizedBox(
                    width: 76,
                    child: RoundAction(
                      action: action,
                      onTap: () {
                        Navigator.of(sheet).pop();
                        context.push(action.route);
                      },
                    ),
                  ).motion((a) => a.fadeIn(delay: (60 * i).ms, duration: 250.ms).scale(begin: const Offset(0.85, 0.85))),
              ],
            ),
          ],
        ),
      ),
    ),
  );
}

/// A round icon button with a label under it - the quick-action style.
class RoundAction extends StatelessWidget {
  const RoundAction({super.key, required this.action, required this.onTap, this.onDark = false});

  final QuickAction action;
  final VoidCallback onTap;

  /// On the coloured home header (white glass) rather than a card.
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: onDark ? Colors.white.withValues(alpha: 0.16) : scheme.primary.withValues(alpha: 0.12),
              border: onDark ? Border.all(color: Colors.white.withValues(alpha: 0.22)) : null,
            ),
            child: Icon(action.icon, color: onDark ? Colors.white : scheme.primary, size: 23),
          ),
          const SizedBox(height: 8),
          Text(
            context.tr(action.label),
            textAlign: TextAlign.center,
            maxLines: 2,
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: onDark ? Colors.white : null),
          ),
        ],
      ),
    );
  }
}
