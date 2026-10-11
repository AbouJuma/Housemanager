import 'package:flutter/material.dart';
import 'error_view.dart';
import '../../core/l10n/l10n.dart';

import 'toast.dart';

import 'money_input.dart';

import '../theme/app_theme.dart';

import '../models/models.dart';
import 'formatters.dart';
import 'status_chip.dart';

/// Shared invoice-detail + "pay" UI, used by both the tenant and buyer
/// portals (App\Modules\TenantPortal\Http\Controllers\Api\V1\
/// InvoiceController and its BuyerPortal equivalent share the exact
/// same chargeInvoice() flow server-side, so the client-side UI is
/// shared too rather than duplicated per role).
class InvoiceDetailBody extends StatefulWidget {
  const InvoiceDetailBody({super.key, required this.invoice, required this.onPay});

  final Invoice invoice;
  final Future<String> Function(double amount) onPay;

  @override
  State<InvoiceDetailBody> createState() => _InvoiceDetailBodyState();
}

class _InvoiceDetailBodyState extends State<InvoiceDetailBody> {
  bool _paying = false;

  Future<void> _showPayDialog() async {
    final controller = TextEditingController(text: formatMoneyInput(widget.invoice.balanceDue.toStringAsFixed(2)));
    final amount = await showDialog<double>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(context.tr('Pay invoice')),
        content: TextField(
          controller: controller,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          inputFormatters: [MoneyInputFormatter()],
          decoration: InputDecoration(labelText: context.tr('Amount'), helperText: 'Balance due: ${formatMoney(widget.invoice.balanceDue)}'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: Text(context.tr('Cancel'))),
          FilledButton(
            onPressed: () => Navigator.pop(context, parseMoney(controller.text)),
            child: Text(context.tr('Pay')),
          ),
        ],
      ),
    );

    if (amount == null || amount <= 0 || !mounted) return;

    setState(() => _paying = true);
    try {
      final message = await widget.onPay(amount);
      if (mounted) {
        showToast(context, message, type: ToastType.success);
      }
    } catch (e) {
      if (mounted) {
        showToast(context, friendlyErrorMessage(e), type: ToastType.error);
      }
    } finally {
      if (mounted) setState(() => _paying = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final invoice = widget.invoice;
    final canPay = invoice.balanceDue > 0 && !['draft', 'cancelled', 'paid'].contains(invoice.status);

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(child: Text(invoice.invoiceNumber, style: Theme.of(context).textTheme.titleMedium)),
                    const SizedBox(width: 8),
                    StatusChip(invoice.status),
                  ],
                ),
                if (invoice.unit != null) Text(invoice.unit!.label, style: TextStyle(color: context.mutedText)),
                const SizedBox(height: 12),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: invoice.totalAmount > 0 ? (invoice.amountPaid / invoice.totalAmount).clamp(0, 1) : 0,
                    minHeight: 8,
                    backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  invoice.balanceDue <= 0
                      ? 'Fully paid'
                      : '${formatMoney(invoice.amountPaid)} paid of ${formatMoney(invoice.totalAmount)} — ${formatMoney(invoice.balanceDue)} remaining',
                  style: TextStyle(
                    color: invoice.balanceDue <= 0 ? Colors.green.shade700 : context.mutedText,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const Divider(height: 24),
                _row('Due date', formatDate(invoice.dueDate)),
                _row('Total amount', formatMoney(invoice.totalAmount)),
                _row('Amount paid', formatMoney(invoice.amountPaid)),
                _row('Balance due', formatMoney(invoice.balanceDue)),
              ],
            ),
          ),
        ),
        if (canPay) ...[
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _paying ? null : _showPayDialog,
            icon: _paying
                ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2))
                : const Icon(Icons.payment),
            label: Text(context.tr('Pay now')),
          ),
        ],
      ],
    );
  }

  Widget _row(String label, String value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: TextStyle(color: context.mutedText)),
            const SizedBox(width: 12),
            Flexible(child: Text(value, textAlign: TextAlign.end)),
          ],
        ),
      );
}
