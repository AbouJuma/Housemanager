import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import 'reports_repository.dart';

final reportsRepositoryProvider = Provider<ReportsRepository>((ref) => ReportsRepository(ref.watch(apiClientProvider)));

/// Keyed by status filter (null = all).
final rentRollProvider = FutureProvider.autoDispose.family(
  (ref, String? status) => ref.watch(reportsRepositoryProvider).rentRoll(status: status),
);

const rentRollStatuses = ['vacant', 'occupied', 'reserved', 'under_maintenance'];

/// Keyed by property status filter (null = all).
final occupancyProvider = FutureProvider.autoDispose.family(
  (ref, String? status) => ref.watch(reportsRepositoryProvider).occupancy(status: status),
);

const occupancyStatuses = ['active', 'inactive', 'under_maintenance', 'archived'];
