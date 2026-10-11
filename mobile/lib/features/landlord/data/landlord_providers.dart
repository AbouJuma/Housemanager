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
