import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/providers.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';

final _maintenanceDetailProvider = FutureProvider.autoDispose.family((ref, String uuid) async {
  final apiClient = ref.watch(apiClientProvider);
  final response = await apiClient.dio.get('/portal/tenant/maintenance-requests/$uuid');
  return (response.data as Map<String, dynamic>)['data'] as Map<String, dynamic>;
});

class TenantMaintenanceDetailScreen extends ConsumerWidget {
  const TenantMaintenanceDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final request = ref.watch(_maintenanceDetailProvider(uuid));

    return AppScaffold(
      title: 'Maintenance Request',
      body: request.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(_maintenanceDetailProvider(uuid))),
        data: (data) {
          final vendor = data['vendor'] as Map<String, dynamic>?;

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
                          Expanded(child: Text(data['title'] as String, style: Theme.of(context).textTheme.titleMedium)),
                          StatusChip(data['status'] as String),
                        ],
                      ),
                      const SizedBox(height: 8),
                      if (data['description'] != null) Text(data['description'] as String),
                      const Divider(height: 24),
                      _row('Category', (data['category'] as String).replaceAll('_', ' ')),
                      _row('Priority', data['priority'] as String),
                      _row('Reported', formatDate(data['reported_date'] as String?)),
                      if (data['scheduled_date'] != null) _row('Scheduled', formatDate(data['scheduled_date'] as String?)),
                      if (data['completed_date'] != null) _row('Completed', formatDate(data['completed_date'] as String?)),
                    ],
                  ),
                ),
              ),
              if (vendor != null) ...[
                const SizedBox(height: 16),
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.engineering_outlined),
                    title: Text(vendor['name'] as String),
                    subtitle: Text(context.tr('Assigned vendor')),
                  ),
                ),
              ],
            ],
          );
        },
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
