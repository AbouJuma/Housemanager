import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../auth/providers/auth_provider.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/staff_providers.dart';

class StaffTenantsScreen extends ConsumerStatefulWidget {
  const StaffTenantsScreen({super.key});

  @override
  ConsumerState<StaffTenantsScreen> createState() => _StaffTenantsScreenState();
}

class _StaffTenantsScreenState extends ConsumerState<StaffTenantsScreen> {
  // PaginatedListView keeps its own fetch/paging state; bumping this key
  // after the create form pops (with `true`) forces a fresh page-1 fetch.
  int _refreshToken = 0;

  Future<void> _openNewTenant() async {
    final created = await context.push<bool>('/staff/tenants/new');
    if (created == true && mounted) setState(() => _refreshToken++);
  }

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(staffRepositoryProvider);

    return AppScaffold(
      title: 'Tenants',
      floatingActionButton: !hasPermission(ref, 'tenants.create')
          ? null
          : FloatingActionButton(
        onPressed: _openNewTenant,
        child: const Icon(Icons.add),
      ),
      body: PaginatedListView(
        key: ValueKey(_refreshToken),
        fetchPage: repository.tenantsPage,
        statusOptions: const ['active', 'inactive', 'blacklisted'],
        showDateRange: false,
        emptyLabel: 'No tenants yet. Tap + to add one.',
        itemBuilder: (context, tenant) => Card(
          child: ListTile(
            title: Text(tenant.name),
            subtitle: Text([if (tenant.phone != null) tenant.phone!, if (tenant.email != null) tenant.email!].join(' · ')),
            trailing: StatusChip(tenant.status),
            onTap: () => context.push('/staff/tenants/${tenant.uuid}'),
          ),
        ),
      ),
    );
  }
}
