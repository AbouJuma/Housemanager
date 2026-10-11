import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/tenant_providers.dart';

class TenantLeasesScreen extends ConsumerWidget {
  const TenantLeasesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final leases = ref.watch(tenantLeasesProvider);

    return AppScaffold(
      title: 'My Leases',
      body: AsyncListView(
        value: leases,
        emptyLabel: 'No leases yet.',
        onRetry: () => ref.invalidate(tenantLeasesProvider),
        itemBuilder: (context, lease) => Card(
          child: ListTile(
            title: Text(lease.unit?.label ?? 'Unit'),
            subtitle: Text('${formatDate(lease.startDate)} – ${formatDate(lease.endDate)}\nRent: ${formatMoney(lease.rentAmount)}'),
            isThreeLine: true,
            trailing: StatusChip(lease.status),
            onTap: () => context.push('/tenant/leases/${lease.uuid}'),
          ),
        ),
      ),
    );
  }
}
