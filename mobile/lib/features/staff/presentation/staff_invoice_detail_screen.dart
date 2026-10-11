import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';
import '../../auth/providers/auth_provider.dart';

import '../../../core/widgets/toast.dart';

import '../../../core/widgets/money_input.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/models/models.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/staff_providers.dart';

const _paymentMethods = ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'];

final staffInvoiceProvider = FutureProvider.autoDispose.family((ref, String uuid) {
  return ref.watch(staffRepositoryProvider).invoice(uuid);
});

class StaffInvoiceDetailScreen extends ConsumerStatefulWidget {
  const StaffInvoiceDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  ConsumerState<StaffInvoiceDetailScreen> createState() => _StaffInvoiceDetailScreenState();
}

class _StaffInvoiceDetailScreenState extends ConsumerState<StaffInvoiceDetailScreen> {
  bool _busy = false;

  Future<void> _sendInvoice() async {
    setState(() => _busy = true);
    try {
      await ref.read(staffRepositoryProvider).sendInvoice(widget.uuid);
      ref.invalidate(staffInvoiceProvider(widget.uuid));
      if (mounted) showToast(context, 'Invoice sent', type: ToastType.success);
    } catch (e) {
      if (mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _recordPayment(Invoice invoice) async {
    final amountController = TextEditingController(text: formatMoneyInput(invoice.balanceDue.toStringAsFixed(2)));
    final referenceController = TextEditingController();
    String method = _paymentMethods.first;

    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: Text(context.tr('Record payment')),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: amountController,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
          inputFormatters: [MoneyInputFormatter()],
                decoration: InputDecoration(labelText: context.tr('Amount'), helperText: 'Balance due: ${formatMoney(invoice.balanceDue)}'),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: method,
                decoration: InputDecoration(labelText: context.tr('Method')),
                items: _paymentMethods.map((m) => DropdownMenuItem(value: m, child: Text(m.replaceAll('_', ' ')))).toList(),
                onChanged: (value) => setDialogState(() => method = value!),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: referenceController,
                decoration: InputDecoration(labelText: context.tr('Reference (optional)')),
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: Text(context.tr('Cancel'))),
            FilledButton(
              onPressed: () => Navigator.pop(context, {
                'amount': parseMoney(amountController.text),
                'method': method,
                'reference': referenceController.text.trim().isEmpty ? null : referenceController.text.trim(),
              }),
              child: Text(context.tr('Record')),
            ),
          ],
        ),
      ),
    );

    if (result == null || result['amount'] == null || (result['amount'] as double) <= 0 || !mounted) return;

    setState(() => _busy = true);
    try {
      await ref.read(staffRepositoryProvider).recordPayment(
            widget.uuid,
            result['amount'] as double,
            method: result['method'] as String,
            reference: result['reference'] as String?,
          );
      ref.invalidate(staffInvoiceProvider(widget.uuid));
      if (mounted) showToast(context, 'Payment recorded', type: ToastType.success);
    } catch (e) {
      if (mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final invoice = ref.watch(staffInvoiceProvider(widget.uuid));

    return AppScaffold(
      title: 'Invoice',
      body: invoice.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(staffInvoiceProvider(widget.uuid))),
        data: (invoice) => ListView(
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
                        value: invoice.totalAmount > 0 ? (invoice.amountPaid / invoice.totalAmount).clamp(0, 1).toDouble() : 0,
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
            const SizedBox(height: 16),
            // Sending and recording payments both need invoices.update.
            if (invoice.status == 'draft' && hasPermission(ref, 'invoices.update'))
              FilledButton.icon(
                onPressed: _busy ? null : _sendInvoice,
                icon: _busy
                    ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.send),
                label: Text(context.tr('Send invoice')),
              ),
            if (invoice.status != 'draft' && invoice.balanceDue > 0 && invoice.status != 'cancelled' && hasPermission(ref, 'invoices.update')) ...[
              FilledButton.icon(
                onPressed: _busy ? null : () => _recordPayment(invoice),
                icon: _busy
                    ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.payments),
                label: Text(context.tr('Record payment')),
              ),
            ],
          ],
        ),
      ),
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
