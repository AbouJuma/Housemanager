import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/l10n/l10n.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/landlord_providers.dart';

class LandlordPropertiesScreen extends ConsumerStatefulWidget {
  const LandlordPropertiesScreen({super.key});

  @override
  ConsumerState<LandlordPropertiesScreen> createState() => _LandlordPropertiesScreenState();
}

class _LandlordPropertiesScreenState extends ConsumerState<LandlordPropertiesScreen> {
  int _refreshToken = 0;

  Future<void> _open(String route) async {
    final changed = await context.push<bool>(route);
    if (changed == true && mounted) setState(() => _refreshToken++);
  }

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(landlordRepositoryProvider);

    return AppScaffold(
      title: 'My Properties',
      floatingActionButton: !hasPermission(ref, 'properties.create')
          ? null
          : FloatingActionButton(
              tooltip: context.tr('Add property'),
              onPressed: () => _open('/landlord/properties/new'),
              child: const Icon(Icons.add),
            ),
      body: PaginatedListView(
        key: ValueKey(_refreshToken),
        fetchPage: repository.propertiesPage,
        statusOptions: const ['active', 'inactive', 'under_maintenance', 'archived'],
        showDateRange: false,
        emptyLabel: 'No properties yet.',
        itemBuilder: (context, property) {
          final location = [property.area, property.city, property.region].whereType<String>().where((p) => p.isNotEmpty).join(', ');

          return Card(
            child: InkWell(
              borderRadius: BorderRadius.circular(16),
              onTap: () => _open('/landlord/properties/${property.uuid}'),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(child: Text(property.name, style: const TextStyle(fontWeight: FontWeight.w700))),
                        StatusChip(property.status),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      [context.tr(_humanize(property.type)), if (location.isNotEmpty) location].join(' · '),
                      style: TextStyle(color: context.mutedText, fontSize: 13),
                    ),
                    if (property.unitsCount != null) ...[
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 8,
                        runSpacing: 6,
                        children: [
                          _CountPill('${property.unitsCount} ${context.tr('Units')}', context.mutedText),
                          _CountPill('${property.occupiedUnits ?? 0} ${context.tr('Rented')}', StatusChip.colorFor(context, 'paid')),
                          _CountPill('${property.vacantUnits ?? 0} ${context.tr('Free')}', StatusChip.colorFor(context, 'pending')),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

String _humanize(String value) {
  final text = value.replaceAll('_', ' ');
  return text.isEmpty ? text : text[0].toUpperCase() + text.substring(1);
}

class _CountPill extends StatelessWidget {
  const _CountPill(this.label, this.color);

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)),
        child: Text(label, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.w600)),
      );
}
