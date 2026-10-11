import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/tenant_providers.dart';

class TenantMaintenanceScreen extends ConsumerStatefulWidget {
  const TenantMaintenanceScreen({super.key});

  @override
  ConsumerState<TenantMaintenanceScreen> createState() => _TenantMaintenanceScreenState();
}

class _TenantMaintenanceScreenState extends ConsumerState<TenantMaintenanceScreen> {
  // PaginatedListView owns its own fetch/paging state internally (it's
  // a plain StatefulWidget, not a Riverpod provider - see its own
  // docblock for why) - bumping this key after returning from the "new
  // request" form forces Flutter to dispose and recreate that state,
  // which is what actually re-fetches page 1. Without this, a newly
  // filed request wouldn't show up until a manual pull-to-refresh.
  int _refreshToken = 0;

  Future<void> _openNewRequest() async {
    await context.push('/tenant/maintenance/new');
    if (mounted) setState(() => _refreshToken++);
  }

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(tenantRepositoryProvider);

    return AppScaffold(
      title: 'Maintenance Requests',
      floatingActionButton: FloatingActionButton(onPressed: _openNewRequest, child: const Icon(Icons.add)),
      body: PaginatedListView(
        key: ValueKey(_refreshToken),
        fetchPage: repository.maintenanceRequestsPage,
        statusOptions: const ['open', 'in_progress', 'completed', 'cancelled'],
        emptyLabel: 'No maintenance requests yet. Tap + to report an issue.',
        itemBuilder: (context, request) => Card(
          child: ListTile(
            title: Text(request.title),
            subtitle: Text('${request.propertyName ?? ''}\nReported ${formatDate(request.reportedDate)}'),
            isThreeLine: true,
            trailing: StatusChip(request.status),
            onTap: () => context.push('/tenant/maintenance/${request.uuid}'),
          ),
        ),
      ),
    );
  }
}
