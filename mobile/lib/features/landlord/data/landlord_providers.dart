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

/// Profit & loss for a (from, to) period - null dates = this year so far.
final landlordProfitLossProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, (String?, String?)>(
  (ref, period) => ref.watch(landlordRepositoryProvider).profitLoss(from: period.$1, to: period.$2),
);

/// Units, optionally only one status ('occupied' = rented, 'vacant' = free).
final landlordUnitsProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, String?>(
  (ref, status) => ref.watch(landlordRepositoryProvider).units(status: status),
);
