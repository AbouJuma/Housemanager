import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

import '../models/models.dart';
import 'formatters.dart';
import 'status_chip.dart';

/// Shared invoice list-row - used by both the tenant and buyer invoice
/// lists. Shows a thin paid/remaining progress bar so a partially-paid
/// invoice reads clearly at a glance, not just via the status chip's
/// text.
class InvoiceListTile extends StatelessWidget {
  const InvoiceListTile({super.key, required this.invoice, required this.onTap});

  final Invoice invoice;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final double progress = invoice.totalAmount > 0 ? (invoice.amountPaid / invoice.totalAmount).clamp(0, 1).toDouble() : 0.0;

    final accent = StatusChip.colorFor(context, invoice.status);

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Container(
          decoration: BoxDecoration(border: Border(left: BorderSide(color: accent, width: 4))),
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(invoice.invoiceNumber, style: const TextStyle(fontWeight: FontWeight.w600)),
                  ),
                  StatusChip(invoice.status),
                ],
              ),
              const SizedBox(height: 4),
              Text('Due ${formatDate(invoice.dueDate)}', style: TextStyle(color: context.mutedText, fontSize: 13)),
              const SizedBox(height: 8),
              ClipRRect(
                borderRadius: BorderRadius.circular(3),
                child: LinearProgressIndicator(
                  value: progress,
                  minHeight: 6,
                  color: accent,
                  backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                invoice.balanceDue <= 0 ? 'Fully paid' : '${formatMoney(invoice.balanceDue)} remaining of ${formatMoney(invoice.totalAmount)}',
                style: TextStyle(
                  fontSize: 13,
                  color: invoice.balanceDue <= 0 ? StatusChip.colorFor(context, 'paid') : context.mutedText,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
