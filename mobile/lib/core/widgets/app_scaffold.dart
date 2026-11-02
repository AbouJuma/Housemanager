import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'error_view.dart';
import 'motion.dart';
import '../l10n/l10n.dart';
import '../../features/notifications/notifications.dart';

import '../theme/app_theme.dart';

import '../../features/auth/providers/auth_provider.dart';

/// Shared page chrome for every signed-in screen - a title, a back
/// button when not the dashboard, and a logout action. Keeps every
/// feature screen from repeating the same AppBar/logout wiring.
class AppScaffold extends ConsumerWidget {
  const AppScaffold({super.key, required this.title, required this.body, this.actions, this.floatingActionButton});

  final String title;
  final Widget body;
  final List<Widget>? actions;
  final Widget? floatingActionButton;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final canPop = context.canPop();

    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 64,
        titleSpacing: canPop ? 4 : 20,
        title: Text(context.tr(title)),
        // Round back button, like the rest of the app's controls.
        leading: canPop
            ? Padding(
                padding: const EdgeInsets.only(left: 12),
                child: _RoundButton(icon: LucideIcons.arrowLeft, tooltip: context.tr('Back'), onPressed: () => context.pop()),
              )
            : null,
        leadingWidth: canPop ? 60 : null,
        actions: [
          ...?actions,
          const NotificationBell(),
          _RoundButton(
            icon: LucideIcons.logOut,
            tooltip: context.tr('Sign out'),
            onPressed: () => ref.read(authProvider.notifier).logout(),
          ),
          const SizedBox(width: 12),
        ],
      ),
      // Each page eases in.
      body: body.motion((a) => a.fadeIn(duration: 280.ms).moveY(begin: 10, end: 0, curve: Curves.easeOutCubic)),
      floatingActionButton: floatingActionButton,
    );
  }
}

/// Standard async list body: loading spinner, retry-able error, empty
/// state, or the built list - every list screen in this app uses this
/// same shape so it's centralized once rather than repeated per screen.
class AsyncListView<T> extends StatelessWidget {
  const AsyncListView({
    super.key,
    required this.value,
    required this.itemBuilder,
    required this.emptyLabel,
    this.onRetry,
  });

  final AsyncValue<List<T>> value;
  final Widget Function(BuildContext, T) itemBuilder;
  final String emptyLabel;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return value.when(
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (error, _) => ErrorView(error: error, onRetry: onRetry),

      data: (items) {
        if (items.isEmpty) {
          return Center(child: Text(context.tr(emptyLabel), style: TextStyle(color: context.mutedText)));
        }
        return ListView.separated(
          padding: const EdgeInsets.all(16),
          itemCount: items.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, index) => itemBuilder(context, items[index]),
        );
      },
    );
  }
}

/// A small round icon button on a soft surface.
class _RoundButton extends StatelessWidget {
  const _RoundButton({required this.icon, required this.onPressed, this.tooltip});

  final IconData icon;
  final VoidCallback onPressed;
  final String? tooltip;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 3),
        child: Material(
          color: Theme.of(context).colorScheme.surfaceContainerHighest,
          shape: const CircleBorder(),
          child: IconButton(icon: Icon(icon, size: 20), tooltip: tooltip, onPressed: onPressed),
        ),
      );
}
