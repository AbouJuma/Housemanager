import 'package:flutter/material.dart';
import '../l10n/l10n.dart';

import '../api/paginated_list.dart';
import 'formatters.dart';

/// Status dropdown + date-range picker, shared by every filterable list
/// screen. Emits the combined ListFilters whenever either changes.
class FilterBar extends StatefulWidget {
  const FilterBar({
    super.key,
    required this.statusOptions,
    required this.initial,
    required this.onChanged,
    this.showDateRange = true,
  });

  final List<String> statusOptions;
  final ListFilters initial;
  final ValueChanged<ListFilters> onChanged;
  final bool showDateRange;

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

  void _emit() => widget.onChanged(ListFilters(status: _status, from: _from, to: _to));

  Future<void> _pickRange() async {
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365 * 3)),
      initialDateRange: _from != null && _to != null ? DateTimeRange(start: _from!, end: _to!) : null,
    );

    if (range != null) {
      setState(() {
        _from = range.start;
        _to = range.end;
      });
      _emit();
    }
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
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: [
            for (final option in <String?>[null, ...widget.statusOptions])
              Padding(
                padding: const EdgeInsets.only(right: 8),
                child: ChoiceChip(
                  label: Text(context.tr(option == null ? 'All' : _label(option))),
                  selected: _status == option,
                  showCheckmark: false,
                  shape: const StadiumBorder(),
                  onSelected: (_) {
                    setState(() => _status = option);
                    _emit();
                  },
                ),
              ),
            if (widget.showDateRange) ...[
              const SizedBox(width: 12),
              ActionChip(
                avatar: const Icon(Icons.date_range, size: 18),
                label: Text(_from != null && _to != null ? '${formatDate(_from!.toIso8601String())} – ${formatDate(_to!.toIso8601String())}' : context.tr('Date range')),
                onPressed: _pickRange,
              ),
              if (_from != null || _to != null) ...[
                const SizedBox(width: 4),
                IconButton(icon: const Icon(Icons.close, size: 18), onPressed: _clearRange, tooltip: context.tr('Clear date range')),
              ],
            ],
          ],
        ),
      ),
    );
  }
}
