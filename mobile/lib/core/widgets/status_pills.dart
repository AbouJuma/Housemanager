import 'package:flutter/material.dart';

import '../l10n/l10n.dart';

/// "All | Vacant | Occupied …" filter pills - one tap filters a list or
/// report by status. [selected] null = All.
class StatusPills extends StatelessWidget {
  const StatusPills({super.key, required this.options, required this.selected, required this.onChanged});

  final List<String> options;
  final String? selected;
  final ValueChanged<String?> onChanged;

  String _label(BuildContext context, String status) {
    final text = status.replaceAll('_', ' ');
    final translated = context.tr(text);
    return translated[0].toUpperCase() + translated.substring(1);
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
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
