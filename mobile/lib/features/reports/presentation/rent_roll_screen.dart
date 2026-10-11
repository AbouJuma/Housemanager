import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../../../core/widgets/status_pills.dart';
import '../data/reports_providers.dart';
import '../data/reports_repository.dart';

class RentRollScreen extends ConsumerStatefulWidget {
  const RentRollScreen({super.key});

  @override
  ConsumerState<RentRollScreen> createState() => _RentRollScreenState();
}

class _RentRollScreenState extends ConsumerState<RentRollScreen> {
  final _searchController = TextEditingController();
  String _search = '';
  String? _status;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rows = ref.watch(rentRollProvider(_status));

    return AppScaffold(
      title: 'Rent Roll',
      body: rows.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(rentRollProvider(_status))),
        data: (rows) {
          final filtered = _search.isEmpty
              ? rows
              : rows
                  .where((r) =>
                      r.unit.toLowerCase().contains(_search) ||
                      r.property.toLowerCase().contains(_search) ||
                      (r.tenant?.toLowerCase().contains(_search) ?? false))
                  .toList();

          final totalRent = rows.fold<double>(0, (sum, r) => sum + r.rentAmount);
          final occupiedCount = rows.where((r) => r.tenant != null).length;

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(rentRollProvider(_status)),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Row(
                  children: [
                    Expanded(child: _SummaryTile(label: 'Units', value: '${rows.length}')),
                    const SizedBox(width: 12),
                    Expanded(child: _SummaryTile(label: 'Occupied', value: '$occupiedCount')),
                    const SizedBox(width: 12),
                    Expanded(child: _SummaryTile(label: 'Total Rent', value: formatMoney(totalRent))),
                  ],
                ),
                const SizedBox(height: 16),
                StatusPills(
                  options: rentRollStatuses,
                  selected: _status,
                  onChanged: (status) => setState(() => _status = status),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    prefixIcon: Icon(Icons.search),
                    hintText: context.tr('Search unit, property or tenant'),
                    border: OutlineInputBorder(),
                    isDense: true,
                  ),
                  onChanged: (value) => setState(() => _search = value.trim().toLowerCase()),
                ),
                const SizedBox(height: 16),
                if (filtered.isEmpty)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 32),
                    child: Center(child: Text('No units match.', style: TextStyle(color: context.mutedText))),
                  )
                else
                  ...filtered.map((row) => _RentRollTile(row: row)),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _SummaryTile extends StatelessWidget {
  const _SummaryTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
          child: Column(
            children: [
              Text(value, style: Theme.of(context).textTheme.titleMedium, textAlign: TextAlign.center),
              const SizedBox(height: 2),
              Text(label, style: TextStyle(color: context.mutedText, fontSize: 12), textAlign: TextAlign.center),
            ],
          ),
        ),
      );
}

class _RentRollTile extends StatelessWidget {
  const _RentRollTile({required this.row});

  final RentRollRow row;

  @override
  Widget build(BuildContext context) {
    final hasLeaseDates = row.leaseStart != null && row.leaseEnd != null;

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
                Expanded(
                  child: Text('${row.unit} · ${row.building}', style: const TextStyle(fontWeight: FontWeight.w600)),
                ),
                StatusChip(row.status),
              ],
            ),
            Text(row.property, style: TextStyle(color: context.mutedText, fontSize: 13)),
            const SizedBox(height: 8),
            Text(row.tenant ?? 'Vacant', style: TextStyle(color: row.tenant != null ? null : context.mutedText)),
            if (hasLeaseDates) Text('${formatDate(row.leaseStart)} - ${formatDate(row.leaseEnd)}', style: TextStyle(color: context.mutedText, fontSize: 12)),
            const SizedBox(height: 4),
            Text(formatMoney(row.rentAmount), style: const TextStyle(fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}
