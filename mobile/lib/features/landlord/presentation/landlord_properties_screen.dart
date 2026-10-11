import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/landlord_providers.dart';

class LandlordPropertiesScreen extends ConsumerWidget {
  const LandlordPropertiesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final repository = ref.watch(landlordRepositoryProvider);

    return AppScaffold(
      title: 'My Properties',
      body: PaginatedListView(
        fetchPage: repository.propertiesPage,
        statusOptions: const ['active', 'inactive', 'under_maintenance', 'archived'],
        emptyLabel: 'No properties yet.',
        itemBuilder: (context, property) => Card(
          child: ListTile(
            title: Text(property.name),
            subtitle: Text('${property.type} · ${property.buildingsCount ?? 0} building(s)'),
            trailing: StatusChip(property.status),
            onTap: () => context.push('/landlord/properties/${property.uuid}'),
          ),
        ),
      ),
    );
  }
}
