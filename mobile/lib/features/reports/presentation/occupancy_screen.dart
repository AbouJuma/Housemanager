import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/status_pills.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../data/reports_providers.dart';
import '../data/reports_repository.dart';

class OccupancyScreen extends ConsumerStatefulWidget {
  const OccupancyScreen({super.key});

  @override
  ConsumerState<OccupancyScreen> createState() => _OccupancyScreenState();
}

class _OccupancyScreenState extends ConsumerState<OccupancyScreen> {
  String? _status;

  @override
  Widget build(BuildContext context) {
    final rows = ref.watch(occupancyProvider(_status));

    return AppScaffold(
      title: 'Occupancy',
      body: rows.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(occupancyProvider(_status))),
        data: (rows) {
          final totalUnits = rows.fold<int>(0, (sum, r) => sum + r.totalUnits);
          final totalOccupied = rows.fold<int>(0, (sum, r) => sum + r.occupied);
          final overallRate = totalUnits > 0 ? (totalOccupied / totalUnits * 100) : 0.0;

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(occupancyProvider(_status)),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                StatusPills(
                  options: occupancyStatuses,
                  selected: _status,
                  onChanged: (status) => setState(() => _status = status),
                ),
                const SizedBox(height: 12),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Overall occupancy', style: Theme.of(context).textTheme.titleSmall),
                        const SizedBox(height: 8),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(4),
                          child: LinearProgressIndicator(
                            value: totalUnits > 0 ? totalOccupied / totalUnits : 0,
                            minHeight: 8,
                            backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text('$totalOccupied of $totalUnits units occupied (${overallRate.toStringAsFixed(1)}%)'),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                if (rows.isEmpty)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 32),
                    child: Center(child: Text('No properties yet.', style: TextStyle(color: context.mutedText))),
                  )
                else
                  ...rows.map((row) => _OccupancyTile(row: row)),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _OccupancyTile extends StatelessWidget {
  const _OccupancyTile({required this.row});

  final OccupancyRow row;

  @override
  Widget build(BuildContext context) {
    final ratio = row.totalUnits > 0 ? row.occupied / row.totalUnits : 0.0;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(child: Text(row.property, style: const TextStyle(fontWeight: FontWeight.w600))),
                Text('${row.occupancyRate.toStringAsFixed(1)}%'),
              ],
            ),
            const SizedBox(height: 8),
            ClipRRect(
              borderRadius: BorderRadius.circular(3),
              child: LinearProgressIndicator(
                value: ratio,
                minHeight: 6,
                backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
              ),
            ),
            const SizedBox(height: 4),
            Text('${row.occupied} occupied · ${row.vacant} vacant · ${row.totalUnits} total', style: TextStyle(color: context.mutedText, fontSize: 12)),
          ],
        ),
      ),
    );
  }
}
