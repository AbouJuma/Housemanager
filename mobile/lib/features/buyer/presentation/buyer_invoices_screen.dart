import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/invoice_list_tile.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../data/buyer_providers.dart';

class BuyerInvoicesScreen extends ConsumerWidget {
  const BuyerInvoicesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final repository = ref.watch(buyerRepositoryProvider);

    return AppScaffold(
      title: 'My Invoices',
      body: PaginatedListView(
        fetchPage: repository.invoicesPage,
        statusOptions: const ['sent', 'partially_paid', 'paid', 'overdue'],
        emptyLabel: 'No invoices yet.',
        itemBuilder: (context, invoice) => InvoiceListTile(
          invoice: invoice,
          onTap: () => context.push('/buyer/invoices/${invoice.uuid}'),
        ),
      ),
    );
  }
}
