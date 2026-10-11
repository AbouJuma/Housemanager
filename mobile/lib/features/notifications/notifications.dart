import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/api_client.dart';
import '../../core/api/json_helpers.dart';
import '../../core/api/token_storage.dart';
import '../../core/l10n/l10n.dart';
import '../../core/providers.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_scaffold.dart';
import '../../core/widgets/brand_widgets.dart';
import '../../core/widgets/error_view.dart';
import '../../core/widgets/toast.dart';
import '../auth/providers/auth_provider.dart';

/// One entry in the signed-in person's inbox - the same notifications
/// the web bell shows (App\Http\Controllers\Api\NotificationsController).
class AppNotification {
  final String id;
  final String kind;
  final String title;
  final String message;
  final bool read;
  final String? createdAgo;

  AppNotification({required this.id, required this.kind, required this.title, required this.message, required this.read, this.createdAgo});

  factory AppNotification.fromJson(Map<String, dynamic> json) => AppNotification(
        id: json['id'] as String,
        kind: (json['kind'] as String?) ?? 'general',
        title: (json['title'] as String?) ?? '',
        message: (json['message'] as String?) ?? '',
        read: json['read'] == true,
        createdAgo: json['created_ago'] as String?,
      );
}

class NotificationsRepository {
  NotificationsRepository(this._apiClient, this._role);

  final ApiClient _apiClient;
  final UserRole _role;

  String get _base => _role == UserRole.staff ? '/notifications' : '/portal/${_role.apiSegment}/notifications';

  Future<(List<AppNotification>, int)> fetch() async {
    final response = await _apiClient.dio.get(_base);
    final body = response.data as Map<String, dynamic>;
    final items = unwrapList(body).map((e) => AppNotification.fromJson(e as Map<String, dynamic>)).toList();
    return (items, asInt(body['unread_count']));
  }

  Future<void> markRead(String id) => _apiClient.dio.post('$_base/$id/read');

  Future<void> markAllRead() => _apiClient.dio.post('$_base/read-all');
}

final notificationsRepositoryProvider = Provider<NotificationsRepository>(
  (ref) => NotificationsRepository(ref.watch(apiClientProvider), ref.watch(authProvider.select((s) => s.role)) ?? UserRole.staff),
);

/// The inbox (first page) and its unread count - the bell's badge reads this.
final notificationsProvider = FutureProvider.autoDispose((ref) => ref.watch(notificationsRepositoryProvider).fetch());

/// Bell with an unread badge; opens the inbox.
class NotificationBell extends ConsumerWidget {
  const NotificationBell({super.key, this.color});

  final Color? color;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final unread = ref.watch(notificationsProvider).value?.$2 ?? 0;

    return IconButton(
      tooltip: context.tr('Notifications'),
      onPressed: () async {
        await context.push('/notifications');
        ref.invalidate(notificationsProvider);
      },
      icon: Badge(
        isLabelVisible: unread > 0,
        label: Text(unread > 9 ? '9+' : '$unread'),
        child: Icon(Icons.notifications_outlined, color: color),
      ),
    );
  }
}

class NotificationsScreen extends ConsumerWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final inbox = ref.watch(notificationsProvider);
    final hasUnread = (inbox.value?.$2 ?? 0) > 0;

    return AppScaffold(
      title: 'Notifications',
      actions: [
        if (hasUnread)
          TextButton(
            onPressed: () async {
              try {
                await ref.read(notificationsRepositoryProvider).markAllRead();
                ref.invalidate(notificationsProvider);
              } catch (e) {
                if (context.mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
              }
            },
            child: Text(context.tr('Mark all read')),
          ),
      ],
      body: inbox.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(notificationsProvider)),
        data: (data) {
          final (items, _) = data;
          if (items.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.notifications_none_rounded, size: 56, color: context.mutedText),
                    const SizedBox(height: 12),
                    Text(context.tr('No notifications yet.'), style: TextStyle(color: context.mutedText)),
                  ],
                ),
              ),
            );
          }
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(notificationsProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, _) => const SizedBox(height: 10),
              itemBuilder: (context, index) => _NotificationTile(item: items[index]),
            ),
          );
        },
      ),
    );
  }
}

class _NotificationTile extends ConsumerWidget {
  const _NotificationTile({required this.item});

  final AppNotification item;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final scheme = Theme.of(context).colorScheme;
    final (icon, palette) = switch (item.kind) {
      'access' => (Icons.admin_panel_settings_outlined, 1),
      _ when item.title.toLowerCase().contains('payment') => (Icons.payments_outlined, 2),
      _ => (Icons.notifications_outlined, 5),
    };

    return Card(
      color: item.read ? null : scheme.primary.withValues(alpha: context.isDark ? 0.14 : 0.06),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        leading: IconTile(icon, paletteIndex: palette, size: 42),
        title: Text(item.title, style: TextStyle(fontWeight: item.read ? FontWeight.w500 : FontWeight.w700)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (item.message.isNotEmpty) ...[const SizedBox(height: 4), Text(item.message)],
            if (item.createdAgo != null) ...[
              const SizedBox(height: 6),
              Text(item.createdAgo!, style: TextStyle(color: context.mutedText, fontSize: 12)),
            ],
          ],
        ),
        trailing: item.read
            ? null
            : Container(width: 10, height: 10, decoration: BoxDecoration(color: scheme.primary, shape: BoxShape.circle)),
        onTap: item.read
            ? null
            : () async {
                try {
                  await ref.read(notificationsRepositoryProvider).markRead(item.id);
                  ref.invalidate(notificationsProvider);
                } catch (_) {
                  // Stays unread; nothing else to do.
                }
              },
      ),
    );
  }
}
