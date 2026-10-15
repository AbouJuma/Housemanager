import 'package:flutter/material.dart';
import '../../../core/l10n/l10n.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/widgets/error_view.dart';
import '../../auth/providers/auth_provider.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/staff_providers.dart';

final staffLeaseProvider = FutureProvider.autoDispose.family((ref, String uuid) {
  return ref.watch(staffRepositoryProvider).lease(uuid);
});

class StaffLeaseDetailScreen extends ConsumerWidget {
  const StaffLeaseDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lease = ref.watch(staffLeaseProvider(uuid));

    return AppScaffold(
      title: 'Lease',
      actions: [
        if (hasPermission(ref, 'leases.update'))
          IconButton(icon: const Icon(Icons.edit), onPressed: () => context.push('/staff/leases/$uuid/edit')),
      ],
      body: lease.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(staffLeaseProvider(uuid))),
        data: (lease) => ListView(
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
                        Expanded(
                          child: Text(lease.unit?.label ?? 'Unit', style: Theme.of(context).textTheme.titleMedium),
                        ),
                        StatusChip(lease.status),
                      ],
                    ),
                    const SizedBox(height: 8),
                    _row('Tenant', lease.tenantName ?? 'Unassigned'),
                    _row('Rent', '${formatMoney(lease.rentAmount)} / '.toString() + context.tr(const {'monthly': 'month', 'quarterly': 'quarter', 'semi_annual': 'half year', 'annual': 'year'}[lease.billingCycle] ?? 'month')),
                    if (lease.contractValue != null) ...[
                      const Divider(height: 24),
                      _row('Total for the lease', '${lease.periods ?? 1} × ${formatMoney(lease.rentAmount)} = ${formatMoney(lease.contractValue!)}'),
                      _row('Paid so far', formatMoney(lease.amountPaid ?? 0)),
                      _row('Balance', formatMoney((lease.balance ?? 0) < 0 ? 0 : lease.balance!)),
                    ],
                    _row('Deposit', lease.depositAmount != null ? formatMoney(lease.depositAmount!) : '—'),
                    _row('Start date', formatDate(lease.startDate)),
                    _row('End date', formatDate(lease.endDate)),
                    if (lease.terminationReason != null && lease.terminationReason!.isNotEmpty)
                      _row('Termination reason', lease.terminationReason!),
                    if (lease.notes != null && lease.notes!.isNotEmpty) ...[
                      const Divider(height: 24),
                      Text(lease.notes!),
                    ],
                  ],
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
          children: [Builder(builder: (context) => Text(label, style: TextStyle(color: context.mutedText))), Flexible(child: Text(value, textAlign: TextAlign.end))],
        ),
      );
}
