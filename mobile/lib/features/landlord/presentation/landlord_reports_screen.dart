import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/api/json_helpers.dart';
import '../../../core/l10n/l10n.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/brand_widgets.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../../../core/widgets/status_pills.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/landlord_providers.dart';

/// The landlord's reports: profit & loss and units (rented / free).
class LandlordReportsScreen extends ConsumerWidget {
  const LandlordReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reports = [
      if (hasPermission(ref, 'reports.view'))
        (Icons.trending_up, 'Profit & Loss', 'Income, expenses and net, by month and property', '/landlord/reports/profit-loss', 2),
      if (hasPermission(ref, 'properties.viewAny'))
        (Icons.meeting_room_outlined, 'Units', 'Which units are rented and which are free', '/landlord/reports/units', 4),
    ];

    return AppScaffold(
      title: 'Reports',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          for (final (icon, title, subtitle, route, color) in reports)
            Card(
              child: ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                leading: IconTile(icon, paletteIndex: color, size: 44),
                title: Text(context.tr(title), style: const TextStyle(fontWeight: FontWeight.w700)),
                subtitle: Text(context.tr(subtitle), style: TextStyle(color: context.mutedText)),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => context.push(route),
              ),
            ),
        ],
      ),
    );
  }
}

String _iso(DateTime d) => DateFormat('yyyy-MM-dd').format(d);

/// Quick periods - (label, from, to); null dates = this year so far.
List<(String, String?, String?)> _periods() {
  final now = DateTime.now();
  return [
    ('This month', _iso(DateTime(now.year, now.month)), _iso(now)),
    ('3 months', _iso(DateTime(now.year, now.month - 2)), _iso(now)),
    ('This year', null, null),
    ('Last year', _iso(DateTime(now.year - 1)), _iso(DateTime(now.year - 1, 12, 31))),
  ];
}

class LandlordProfitLossScreen extends ConsumerStatefulWidget {
  const LandlordProfitLossScreen({super.key});

  @override
  ConsumerState<LandlordProfitLossScreen> createState() => _LandlordProfitLossScreenState();
}

class _LandlordProfitLossScreenState extends ConsumerState<LandlordProfitLossScreen> {
  int _period = 2;

  @override
  Widget build(BuildContext context) {
    final periods = _periods();
    final (_, from, to) = periods[_period];
    final report = ref.watch(landlordProfitLossProvider((from, to)));

    return AppScaffold(
      title: 'Profit & Loss',
      body: Column(
        children: [
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: Row(
              children: [
                for (var i = 0; i < periods.length; i++)
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(context.tr(periods[i].$1)),
                      selected: _period == i,
                      onSelected: (_) => setState(() => _period = i),
                    ),
                  ),
              ],
            ),
          ),
          Expanded(
            child: report.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordProfitLossProvider((from, to)))),
              data: (data) => _ProfitLossBody(data: data),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProfitLossBody extends StatelessWidget {
  const _ProfitLossBody({required this.data});

  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    final income = data['income'] as Map<String, dynamic>;
    final expenses = data['expenses'] as Map<String, dynamic>;
    final net = asDouble(data['net']);
    final monthly = (data['monthly'] as List<dynamic>).cast<Map<String, dynamic>>();
    final byProperty = (data['by_property'] as List<dynamic>).cast<Map<String, dynamic>>();
    final byCategory = (expenses['by_category'] as List<dynamic>).cast<Map<String, dynamic>>();
    final good = StatusChip.colorFor(context, 'paid');
    final bad = StatusChip.colorFor(context, 'overdue');
    final peak = monthly.fold<double>(1, (m, r) => [m, asDouble(r['income']), asDouble(r['expenses'])].reduce((a, b) => a > b ? a : b));

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Text('${formatDate(data['from'] as String?)} – ${formatDate(data['to'] as String?)}', style: TextStyle(color: context.mutedText)),
        const SizedBox(height: 12),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(context.tr(net >= 0 ? 'Net profit' : 'Net loss'), style: TextStyle(color: context.mutedText)),
                const SizedBox(height: 4),
                Text(formatMoney(net), style: Theme.of(context).textTheme.headlineSmall?.copyWith(color: net >= 0 ? good : bad, fontWeight: FontWeight.w800)),
                if (data['margin'] != null)
                  Text('${context.tr('Margin')} ${data['margin']}%', style: TextStyle(color: context.mutedText)),
                const Divider(height: 24),
                Row(
                  children: [
                    Expanded(child: _Figure(label: 'Income received', value: asDouble(income['total']), color: good)),
                    Expanded(child: _Figure(label: 'Expenses', value: asDouble(expenses['total']), color: bad)),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  '${context.tr('Rent')} ${formatMoney(asDouble(income['rent']))} · ${context.tr('Sales')} ${formatMoney(asDouble(income['sales']))}',
                  style: TextStyle(color: context.mutedText, fontSize: 13),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        _Section(
          title: 'By month',
          children: [
            for (final row in monthly)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(child: Text(DateFormat.yMMM().format(DateTime.parse('${row['month']}-01')))),
                        Text(formatMoney(asDouble(row['net'])), style: TextStyle(fontWeight: FontWeight.w700, color: asDouble(row['net']) >= 0 ? good : bad)),
                      ],
                    ),
                    const SizedBox(height: 4),
                    _Bar(value: asDouble(row['income']) / peak, color: good),
                    const SizedBox(height: 2),
                    _Bar(value: asDouble(row['expenses']) / peak, color: bad),
                  ],
                ),
              ),
          ],
        ),
        _Section(
          title: 'By property',
          children: [
            if (byProperty.isEmpty) Text(context.tr('No properties yet.'), style: TextStyle(color: context.mutedText)),
            for (final row in byProperty)
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(row['name'] as String? ?? '—'),
                subtitle: Text('${context.tr('Income')} ${formatMoney(asDouble(row['income']))} · ${context.tr('Expenses')} ${formatMoney(asDouble(row['expenses']))}',
                    style: TextStyle(color: context.mutedText, fontSize: 12)),
                trailing: Text(formatMoney(asDouble(row['net'])),
                    style: TextStyle(fontWeight: FontWeight.w700, color: asDouble(row['net']) >= 0 ? good : bad)),
              ),
          ],
        ),
        _Section(
          title: 'Expenses by category',
          children: [
            if (byCategory.isEmpty) Text(context.tr('No expenses in this period.'), style: TextStyle(color: context.mutedText)),
            for (final row in byCategory)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Expanded(child: Text(row['category'] as String? ?? '—')),
                    Text(formatMoney(asDouble(row['amount']))),
                  ],
                ),
              ),
          ],
        ),
      ],
    );
  }
}

class _Figure extends StatelessWidget {
  const _Figure({required this.label, required this.value, required this.color});

  final String label;
  final double value;
  final Color color;

  @override
  Widget build(BuildContext context) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(context.tr(label), style: TextStyle(color: context.mutedText, fontSize: 13)),
          const SizedBox(height: 2),
          Text(formatMoney(value), style: TextStyle(color: color, fontWeight: FontWeight.w700, fontSize: 16)),
        ],
      );
}

class _Bar extends StatelessWidget {
  const _Bar({required this.value, required this.color});

  final double value;
  final Color color;

  @override
  Widget build(BuildContext context) => ClipRRect(
        borderRadius: BorderRadius.circular(3),
        child: LinearProgressIndicator(
          value: value.clamp(0, 1).toDouble(),
          minHeight: 6,
          color: color,
          backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
        ),
      );
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(context.tr(title), style: Theme.of(context).textTheme.titleSmall),
                const SizedBox(height: 8),
                ...children,
              ],
            ),
          ),
        ),
      );
}

/// Every unit of the landlord's, rented or free, with who rents it.
class LandlordUnitsScreen extends ConsumerStatefulWidget {
  const LandlordUnitsScreen({super.key});

  @override
  ConsumerState<LandlordUnitsScreen> createState() => _LandlordUnitsScreenState();
}

class _LandlordUnitsScreenState extends ConsumerState<LandlordUnitsScreen> {
  String? _status;

  @override
  Widget build(BuildContext context) {
    final report = ref.watch(landlordUnitsProvider(_status));
    final labels = {'occupied': 'Rented', 'vacant': 'Free', 'reserved': 'Reserved', 'under_maintenance': 'Under maintenance'};

    return AppScaffold(
      title: 'Units',
      body: Column(
        children: [
          StatusPills(
            options: labels.keys.toList(),
            labels: labels,
            selected: _status,
            onChanged: (value) => setState(() => _status = value),
          ),
          Expanded(
            child: report.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordUnitsProvider(_status))),
              data: (data) {
                final summary = data['summary'] as Map<String, dynamic>;
                final units = (data['units'] as List<dynamic>).cast<Map<String, dynamic>>();

                return ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Row(
                      children: [
                        Expanded(child: _SummaryTile(label: 'Rented', value: '${summary['occupied']}', color: StatusChip.colorFor(context, 'paid'))),
                        const SizedBox(width: 10),
                        Expanded(child: _SummaryTile(label: 'Free', value: '${summary['vacant']}', color: StatusChip.colorFor(context, 'pending'))),
                        const SizedBox(width: 10),
                        Expanded(child: _SummaryTile(label: 'Occupancy', value: '${summary['occupancy_rate']}%', color: Theme.of(context).colorScheme.primary)),
                      ],
                    ),
                    const SizedBox(height: 16),
                    if (units.isEmpty)
                      Padding(
                        padding: const EdgeInsets.only(top: 40),
                        child: Center(child: Text(context.tr('No units to show.'), style: TextStyle(color: context.mutedText))),
                      ),
                    for (final unit in units) _UnitRow(unit: unit, label: labels[unit['status']] ?? (unit['status'] as String? ?? '')),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryTile extends StatelessWidget {
  const _SummaryTile({required this.label, required this.value, required this.color});

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) => Card(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
          child: Column(
            children: [
              Text(value, style: TextStyle(color: color, fontSize: 20, fontWeight: FontWeight.w800)),
              const SizedBox(height: 2),
              Text(context.tr(label), style: TextStyle(color: context.mutedText, fontSize: 12), textAlign: TextAlign.center),
            ],
          ),
        ),
      );
}

class _UnitRow extends StatelessWidget {
  const _UnitRow({required this.unit, required this.label});

  final Map<String, dynamic> unit;
  final String label;

  @override
  Widget build(BuildContext context) {
    final status = unit['status'] as String? ?? '';
    final color = switch (status) {
      'occupied' => StatusChip.colorFor(context, 'paid'),
      'vacant' => StatusChip.colorFor(context, 'pending'),
      _ => context.mutedText,
    };
    final tenant = unit['tenant'] as Map<String, dynamic>?;
    final property = unit['property'] as Map<String, dynamic>?;

    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: color.withValues(alpha: 0.15),
          foregroundColor: color,
          child: Icon(status == 'occupied' ? Icons.person : Icons.meeting_room_outlined, size: 20),
        ),
        title: Text('${context.tr('Unit')} ${unit['unit_number']} · ${property?['name'] ?? ''}', maxLines: 1, overflow: TextOverflow.ellipsis),
        subtitle: Text(
          tenant == null
              ? '${formatMoney(asDouble(unit['rent_amount']))} / ${context.tr('month')}'
              : '${tenant['name']} · ${formatMoney(asDouble(unit['rent_amount']))}',
          style: TextStyle(color: context.mutedText),
        ),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(999)),
          child: Text(context.tr(label), style: TextStyle(color: color, fontWeight: FontWeight.w600, fontSize: 12)),
        ),
      ),
    );
  }
}
