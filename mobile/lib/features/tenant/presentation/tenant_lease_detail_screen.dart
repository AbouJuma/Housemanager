import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/tenant_providers.dart';

class TenantLeaseDetailScreen extends ConsumerWidget {
  const TenantLeaseDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final lease = ref.watch(tenantLeaseProvider(uuid));

    return AppScaffold(
      title: 'Lease',
      body: lease.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(tenantLeaseProvider(uuid))),
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
                        Expanded(child: Text(lease.unit?.label ?? 'Unit', style: Theme.of(context).textTheme.titleMedium)),
                        const SizedBox(width: 8),
                        StatusChip(lease.status),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _row('Start date', formatDate(lease.startDate)),
                    _row('End date', formatDate(lease.endDate)),
                    _row('Monthly rent', formatMoney(lease.rentAmount)),
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
          children: [
            Builder(builder: (context) => Text(label, style: TextStyle(color: context.mutedText))),
            const SizedBox(width: 12),
            // Long values wrap instead of overflowing on narrow phones.
            Flexible(child: Text(value, textAlign: TextAlign.end)),
          ],
        ),
      );
}
