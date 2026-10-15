import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/providers.dart';
import 'landlord_repository.dart';

final landlordRepositoryProvider = Provider<LandlordRepository>((ref) => LandlordRepository(ref.watch(apiClientProvider)));

final landlordDashboardProvider = FutureProvider.autoDispose((ref) => ref.watch(landlordRepositoryProvider).dashboard());

final landlordPropertiesProvider = FutureProvider.autoDispose((ref) => ref.watch(landlordRepositoryProvider).properties());

final landlordPropertyProvider = FutureProvider.autoDispose.family<PropertyItem, String>(
  (ref, uuid) => ref.watch(landlordRepositoryProvider).property(uuid),
);

final landlordExpensesProvider = FutureProvider.autoDispose((ref) => ref.watch(landlordRepositoryProvider).expenses());

final landlordMaintenanceRequestsProvider =
    FutureProvider.autoDispose((ref) => ref.watch(landlordRepositoryProvider).maintenanceRequests());

final landlordPropertyOptionsProvider = FutureProvider.autoDispose((ref) => ref.watch(landlordRepositoryProvider).propertyOptions());

final landlordTenantProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, String>(
  (ref, uuid) => ref.watch(landlordRepositoryProvider).tenant(uuid),
);

/// Profit & loss for (named period, group) - e.g. ('this_month', 'week');
/// null group = picked by the server from the range length.
final landlordProfitLossProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, (String, String?)>(
  (ref, key) => ref.watch(landlordRepositoryProvider).profitLoss(period: key.$1, group: key.$2),
);

/// Units, optionally only one status ('occupied' = rented, 'vacant' = free).
final landlordUnitsProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, String?>(
  (ref, status) => ref.watch(landlordRepositoryProvider).units(status: status),
);

final landlordExpenseOptionsProvider = FutureProvider.autoDispose((ref) => ref.watch(landlordRepositoryProvider).expenseOptions());

final landlordBuyerProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, String>(
  (ref, uuid) => ref.watch(landlordRepositoryProvider).buyer(uuid),
);

final landlordLeaseOptionsProvider = FutureProvider.autoDispose((ref) => ref.watch(landlordRepositoryProvider).leaseOptions());
