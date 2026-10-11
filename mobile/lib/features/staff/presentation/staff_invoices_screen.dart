import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../auth/providers/auth_provider.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/invoice_list_tile.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../data/staff_providers.dart';

class StaffInvoicesScreen extends ConsumerStatefulWidget {
  const StaffInvoicesScreen({super.key});

  @override
  ConsumerState<StaffInvoicesScreen> createState() => _StaffInvoicesScreenState();
}

class _StaffInvoicesScreenState extends ConsumerState<StaffInvoicesScreen> {
  int _refreshToken = 0;

  Future<void> _openNewInvoice() async {
    final created = await context.push<bool>('/staff/invoices/new');
    if (created == true && mounted) setState(() => _refreshToken++);
  }

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(staffRepositoryProvider);

    return AppScaffold(
      title: 'Invoices',
      floatingActionButton: !hasPermission(ref, 'invoices.create') ? null : FloatingActionButton(onPressed: _openNewInvoice, child: const Icon(Icons.add)),
      body: PaginatedListView(
        key: ValueKey(_refreshToken),
        fetchPage: repository.invoicesPage,
        statusOptions: const ['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled'],
        emptyLabel: 'No invoices yet. Tap + to create one.',
        itemBuilder: (context, invoice) => InvoiceListTile(
          invoice: invoice,
          onTap: () => context.push('/staff/invoices/${invoice.uuid}'),
        ),
      ),
    );
  }
}
