import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/providers.dart';
import 'tenant_repository.dart';

final tenantRepositoryProvider = Provider<TenantRepository>((ref) => TenantRepository(ref.watch(apiClientProvider)));

final tenantDashboardProvider = FutureProvider.autoDispose((ref) => ref.watch(tenantRepositoryProvider).dashboard());

final tenantLeasesProvider = FutureProvider.autoDispose((ref) => ref.watch(tenantRepositoryProvider).leases());

final tenantLeaseProvider = FutureProvider.autoDispose.family<Lease, String>(
  (ref, uuid) => ref.watch(tenantRepositoryProvider).lease(uuid),
);

final tenantInvoicesProvider = FutureProvider.autoDispose((ref) => ref.watch(tenantRepositoryProvider).invoices());

final tenantInvoiceProvider = FutureProvider.autoDispose.family<Invoice, String>(
  (ref, uuid) => ref.watch(tenantRepositoryProvider).invoice(uuid),
);

final tenantMaintenanceRequestsProvider =
    FutureProvider.autoDispose((ref) => ref.watch(tenantRepositoryProvider).maintenanceRequests());
