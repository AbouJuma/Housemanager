import 'package:flutter/material.dart';
import '../l10n/l10n.dart';

/// Small colored pill for a status string (invoice/lease/sale/expense/
/// maintenance status) - green for a "good" state, amber for one still
/// in progress, red for a "bad" one, neutral otherwise. Good enough for
/// every status enum this app displays without hand-mapping each one.
class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});

  final String status;

  static const _positive = {'active', 'paid', 'sent', 'completed', 'resolved'};
  static const _pending = {'pending', 'partially_paid', 'partial', 'draft', 'in_progress', 'open', 'reserved', 'unpaid', 'checked_in', 'low_stock', 'expiring_soon'};
  static const _negative = {'overdue', 'suspended', 'cancelled', 'blacklisted', 'failed', 'terminated', 'expired', 'void', 'voided'};

  /// The status's accent color - also used for list-card stripes and
  /// progress bars so a row's color matches its chip.
  static Color colorFor(BuildContext context, String status) {
    final normalized = status.toLowerCase();
    if (_positive.contains(normalized)) return const Color(0xFF12B76A);
    if (_pending.contains(normalized)) return const Color(0xFFF79009);
    if (_negative.contains(normalized)) return const Color(0xFFF04438);
    return Theme.of(context).colorScheme.onSurfaceVariant;
  }

  @override
  Widget build(BuildContext context) {
    final color = colorFor(context, status);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final label = context.tr(status.replaceAll('_', ' '));

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: isDark ? 0.2 : 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.isEmpty ? label : label[0].toUpperCase() + label.substring(1),
        // Darker text on the light tint, lighter on the dark one, so the
        // label always clears AA contrast.
        style: TextStyle(
          color: isDark ? Color.lerp(color, Colors.white, 0.35) : Color.lerp(color, Colors.black, 0.35),
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
