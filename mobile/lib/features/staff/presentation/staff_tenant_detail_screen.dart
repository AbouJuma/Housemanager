import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/widgets/error_view.dart';
import '../../auth/providers/auth_provider.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/staff_providers.dart';

final staffTenantProvider = FutureProvider.autoDispose.family((ref, String uuid) {
  return ref.watch(staffRepositoryProvider).tenant(uuid);
});

class StaffTenantDetailScreen extends ConsumerWidget {
  const StaffTenantDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tenant = ref.watch(staffTenantProvider(uuid));

    return AppScaffold(
      title: 'Tenant',
      actions: [
        if (hasPermission(ref, 'tenants.update'))
          IconButton(icon: const Icon(Icons.edit), onPressed: () => context.push('/staff/tenants/$uuid/edit')),
      ],
      body: tenant.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(staffTenantProvider(uuid))),
        data: (tenant) => ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(child: Text(tenant.name, style: Theme.of(context).textTheme.titleMedium)),
                        StatusChip(tenant.status),
                      ],
                    ),
                    const SizedBox(height: 8),
                    _row('Type', tenant.type),
                    _row('Email', tenant.email ?? '—'),
                    _row('Phone', tenant.phone ?? '—'),
                    if (tenant.notes != null && tenant.notes!.isNotEmpty) ...[
                      const Divider(height: 24),
                      Text(tenant.notes!),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Builder(builder: (context) => Text(label, style: TextStyle(color: context.mutedText))),
            const SizedBox(width: 12),
            // Long values wrap instead of overflowing on narrow phones.
            Flexible(child: Text(value, textAlign: TextAlign.end)),
          ],
        ),
      );
}
