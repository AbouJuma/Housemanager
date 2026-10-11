import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/buyer_providers.dart';

class BuyerSaleDetailScreen extends ConsumerWidget {
  const BuyerSaleDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sale = ref.watch(buyerSaleProvider(uuid));

    return AppScaffold(
      title: 'Purchase',
      body: sale.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(buyerSaleProvider(uuid))),
        data: (sale) => ListView(
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
                        Expanded(child: Text(sale.unit?.label ?? 'Unit', style: Theme.of(context).textTheme.titleMedium)),
                        StatusChip(sale.status),
                      ],
                    ),
                    const Divider(height: 24),
                    _row('Total price', formatMoney(sale.totalPrice)),
                    _row('Total paid', formatMoney(sale.totalPaid)),
                    _row('Remaining balance', formatMoney(sale.remainingBalance)),
                    const SizedBox(height: 8),
                    LinearProgressIndicator(value: sale.completionPercentage / 100),
                    const SizedBox(height: 4),
                    Text('${sale.completionPercentage}% paid', style: TextStyle(color: context.mutedText)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Text('Installments', style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            ...sale.installments.map(
              (installment) => Card(
                child: ListTile(
                  title: Text('Installment #${installment.installmentNumber}'),
                  subtitle: Text('Due ${formatDate(installment.dueDate)} · ${formatMoney(installment.amount)}'),
                  trailing: StatusChip(installment.status),
                ),
              ),
            ),
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
            Builder(builder: (context) => Text(label, style: TextStyle(color: context.mutedText))),
            const SizedBox(width: 12),
            // Long values wrap instead of overflowing on narrow phones.
            Flexible(child: Text(value, textAlign: TextAlign.end)),
          ],
        ),
      );
}
