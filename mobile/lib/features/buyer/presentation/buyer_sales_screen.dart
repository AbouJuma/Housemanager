import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/buyer_providers.dart';

class BuyerSalesScreen extends ConsumerWidget {
  const BuyerSalesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final repository = ref.watch(buyerRepositoryProvider);

    return AppScaffold(
      title: 'My Purchases',
      body: PaginatedListView(
        fetchPage: repository.salesPage,
        statusOptions: const ['pending', 'active', 'completed', 'cancelled'],
        emptyLabel: 'No purchases yet.',
        itemBuilder: (context, sale) => Card(
          child: ListTile(
            title: Text(sale.unit?.label ?? 'Unit'),
            subtitle: Text(
              'Total: ${formatMoney(sale.totalPrice)}\nPaid: ${formatMoney(sale.totalPaid)} (${sale.completionPercentage}%)',
            ),
            isThreeLine: true,
            trailing: StatusChip(sale.status),
            onTap: () => context.push('/buyer/sales/${sale.uuid}'),
          ),
        ),
      ),
    );
  }
}
