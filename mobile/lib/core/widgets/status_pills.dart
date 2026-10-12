import 'package:flutter/material.dart';

import '../l10n/l10n.dart';

/// "All | Vacant | Occupied …" filter pills - one tap filters a list or
/// report by status. [selected] null = All.
class StatusPills extends StatelessWidget {
  const StatusPills({super.key, required this.options, required this.selected, required this.onChanged, this.labels = const {}});

  final List<String> options;
  final String? selected;
  final ValueChanged<String?> onChanged;

  /// Optional display names per option (e.g. occupied -> Rented).
  final Map<String, String> labels;

  String _label(BuildContext context, String status) {
    if (labels[status] != null) return context.tr(labels[status]!);
    final text = status.replaceAll('_', ' ');
    final translated = context.tr(text);
    return translated[0].toUpperCase() + translated.substring(1);
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: labels.isEmpty ? null : const EdgeInsets.fromLTRB(16, 12, 16, 0),
      child: Row(
        children: [
          for (final option in <String?>[null, ...options])
            Padding(
              padding: const EdgeInsets.only(right: 8),
              child: ChoiceChip(
                label: Text(option == null ? context.tr('All') : _label(context, option)),
                selected: selected == option,
                showCheckmark: false,
                shape: const StadiumBorder(),
                onSelected: (_) => onChanged(option),
              ),
            ),
        ],
      ),
    );
  }
}
