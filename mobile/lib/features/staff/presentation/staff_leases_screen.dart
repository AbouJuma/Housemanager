import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../auth/providers/auth_provider.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/staff_providers.dart';

class StaffLeasesScreen extends ConsumerStatefulWidget {
  const StaffLeasesScreen({super.key});

  @override
  ConsumerState<StaffLeasesScreen> createState() => _StaffLeasesScreenState();
}

class _StaffLeasesScreenState extends ConsumerState<StaffLeasesScreen> {
  int _refreshToken = 0;

  Future<void> _openNewLease() async {
    final created = await context.push<bool>('/staff/leases/new');
    if (created == true && mounted) setState(() => _refreshToken++);
  }

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(staffRepositoryProvider);

    return AppScaffold(
      title: 'Leases',
      floatingActionButton: !hasPermission(ref, 'leases.create') ? null : FloatingActionButton(onPressed: _openNewLease, child: const Icon(Icons.add)),
      body: PaginatedListView(
        key: ValueKey(_refreshToken),
        fetchPage: repository.leasesPage,
        statusOptions: const ['active', 'pending', 'terminated', 'expired'],
        emptyLabel: 'No leases yet. Tap + to create one.',
        itemBuilder: (context, lease) => Card(
          child: ListTile(
            title: Text(lease.unit?.label ?? 'Unit'),
            subtitle: Text('${lease.tenantName ?? 'Unassigned'}\n${formatMoney(lease.rentAmount)} / ${lease.billingCycle}'),
            isThreeLine: true,
            trailing: StatusChip(lease.status),
            onTap: () => context.push('/staff/leases/${lease.uuid}'),
          ),
        ),
      ),
    );
  }
}
