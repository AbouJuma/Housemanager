import 'package:flutter/material.dart';
import '../l10n/l10n.dart';

import '../api/paginated_list.dart';
import 'formatters.dart';

/// Status chips + quick periods (today, this week, this month, this year)
/// + a custom date-range picker, shared by every filterable list screen.
/// Emits the combined ListFilters whenever any of them changes.
class FilterBar extends StatefulWidget {
  const FilterBar({
    super.key,
    required this.statusOptions,
    required this.initial,
    required this.onChanged,
    this.showDateRange = true,
    this.allLabel = 'All',
  });

  final List<String> statusOptions;
  final ListFilters initial;
  final ValueChanged<ListFilters> onChanged;
  final bool showDateRange;

  /// Label of the first (no status) chip.
  final String allLabel;

  @override
  State<FilterBar> createState() => _FilterBarState();
}

class _FilterBarState extends State<FilterBar> {
  late String? _status = widget.initial.status;
  late DateTime? _from = widget.initial.from;
  late DateTime? _to = widget.initial.to;

  String _label(String status) {
    final text = status.replaceAll('_', ' ');
    return text[0].toUpperCase() + text.substring(1);
  }

  void _emit() =>
      widget.onChanged(ListFilters(status: _status, from: _from, to: _to));

  Future<void> _pickRange() async {
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365 * 3)),
      initialDateRange: _from != null && _to != null
          ? DateTimeRange(start: _from!, end: _to!)
          : null,
    );

    if (range != null) {
      setState(() {
        _from = range.start;
        _to = range.end;
      });
      _emit();
    }
  }

  /// Today / this week / this month / this year, as (label, from, to).
  static List<(String, DateTime, DateTime)> quickPeriods(DateTime now) {
    final today = DateTime(now.year, now.month, now.day);
    return [
      ('Today', today, today),
      ('This week', today.subtract(Duration(days: today.weekday - 1)), today),
      ('This month', DateTime(now.year, now.month), today),
      ('This year', DateTime(now.year), today),
    ];
  }

  bool _sameDay(DateTime? a, DateTime b) =>
      a != null && a.year == b.year && a.month == b.month && a.day == b.day;

  void _pickPeriod(DateTime from, DateTime to) {
    final alreadyOn = _sameDay(_from, from) && _sameDay(_to, to);
    setState(() {
      _from = alreadyOn ? null : from;
      _to = alreadyOn ? null : to;
    });
    _emit();
  }

  void _clearRange() {
    setState(() {
      _from = null;
      _to = null;
    });
    _emit();
  }

  @override
  Widget build(BuildContext context) {
    final quick = quickPeriods(DateTime.now());
    final isQuick = quick.any(
      (p) => _sameDay(_from, p.$2) && _sameDay(_to, p.$3),
    );

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (widget.statusOptions.isNotEmpty) _statusRow(context),
          if (widget.showDateRange) ...[
            if (widget.statusOptions.isNotEmpty) const SizedBox(height: 6),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                children: [
                  for (final (label, from, to) in quick)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: FilterChip(
                        label: Text(context.tr(label)),
                        selected: _sameDay(_from, from) && _sameDay(_to, to),
                        showCheckmark: false,
                        visualDensity: VisualDensity.compact,
                        onSelected: (_) => _pickPeriod(from, to),
                      ),
                    ),
                  ActionChip(
                    avatar: const Icon(Icons.date_range, size: 18),
                    visualDensity: VisualDensity.compact,
                    label: Text(
                      _from != null && _to != null && !isQuick
                          ? '${formatDate(_from!.toIso8601String())} – ${formatDate(_to!.toIso8601String())}'
                          : context.tr('Date range'),
                    ),
                    onPressed: _pickRange,
                  ),
                  if (_from != null || _to != null) ...[
                    const SizedBox(width: 4),
                    IconButton(
                      icon: const Icon(Icons.close, size: 18),
                      onPressed: _clearRange,
                      tooltip: context.tr('Clear date range'),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _statusRow(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          for (final option in <String?>[null, ...widget.statusOptions])
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: ChoiceChip(
                label: Text(
                  context.tr(option == null ? widget.allLabel : _label(option)),
                ),
                selected: _status == option,
                showCheckmark: false,
                shape: const StadiumBorder(),
                onSelected: (_) {
                  setState(() => _status = option);
                  _emit();
                },
              ),
            ),
        ],
      ),
    );
  }
}
