import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/landlord_providers.dart';

class LandlordMaintenanceScreen extends ConsumerWidget {
  const LandlordMaintenanceScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final repository = ref.watch(landlordRepositoryProvider);

    return AppScaffold(
      title: 'Maintenance Requests',
      body: PaginatedListView(
        fetchPage: repository.maintenanceRequestsPage,
        statusOptions: const ['open', 'in_progress', 'completed', 'cancelled'],
        emptyLabel: 'No maintenance requests yet.',
        itemBuilder: (context, request) => Card(
          child: ListTile(
            title: Text(request.title),
            subtitle: Text('${request.propertyName ?? ''}\nReported ${formatDate(request.reportedDate)}'),
            isThreeLine: true,
            trailing: StatusChip(request.status),
          ),
        ),
      ),
    );
  }
}
