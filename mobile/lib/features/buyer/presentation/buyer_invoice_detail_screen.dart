import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/invoice_detail_body.dart';
import '../data/buyer_providers.dart';

class BuyerInvoiceDetailScreen extends ConsumerWidget {
  const BuyerInvoiceDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final invoice = ref.watch(buyerInvoiceProvider(uuid));

    return AppScaffold(
      title: 'Invoice',
      body: invoice.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(buyerInvoiceProvider(uuid))),
        data: (invoice) => InvoiceDetailBody(
          invoice: invoice,
          onPay: (amount) async {
            final message = await ref.read(buyerRepositoryProvider).payInvoice(uuid, amount);
            ref.invalidate(buyerInvoiceProvider(uuid));
            ref.invalidate(buyerInvoicesProvider);
            return message;
          },
        ),
      ),
    );
  }
}
