import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'error_view.dart';
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
    return Scaffold(
      appBar: AppBar(
        title: Text(context.tr(title)),
        leading: context.canPop()
            ? IconButton(icon: const Icon(Icons.arrow_back), onPressed: () => context.pop())
            : null,
        actions: [
          ...?actions,
          const NotificationBell(),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: context.tr('Sign out'),
            onPressed: () => ref.read(authProvider.notifier).logout(),
          ),
        ],
      ),
      body: body,
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
