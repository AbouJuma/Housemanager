import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/landlord_providers.dart';

class LandlordExpensesScreen extends ConsumerWidget {
  const LandlordExpensesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final repository = ref.watch(landlordRepositoryProvider);

    return AppScaffold(
      title: 'Expenses',
      body: PaginatedListView(
        fetchPage: repository.expensesPage,
        statusOptions: const ['unpaid', 'paid'],
        emptyLabel: 'No expenses yet.',
        itemBuilder: (context, expense) => Card(
          child: ListTile(
            title: Text(expense.description),
            subtitle: Text('${expense.propertyName ?? ''}\n${formatDate(expense.expenseDate)} · ${formatMoney(expense.amount)}'),
            isThreeLine: true,
            trailing: StatusChip(expense.paymentStatus),
          ),
        ),
      ),
    );
  }
}
