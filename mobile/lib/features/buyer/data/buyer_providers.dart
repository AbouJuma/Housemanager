import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/models.dart';
import '../../../core/providers.dart';
import 'buyer_repository.dart';

final buyerRepositoryProvider = Provider<BuyerRepository>((ref) => BuyerRepository(ref.watch(apiClientProvider)));

final buyerDashboardProvider = FutureProvider.autoDispose((ref) => ref.watch(buyerRepositoryProvider).dashboard());

final buyerSalesProvider = FutureProvider.autoDispose((ref) => ref.watch(buyerRepositoryProvider).sales());

final buyerSaleProvider = FutureProvider.autoDispose.family<Sale, String>(
  (ref, uuid) => ref.watch(buyerRepositoryProvider).sale(uuid),
);

final buyerInvoicesProvider = FutureProvider.autoDispose((ref) => ref.watch(buyerRepositoryProvider).invoices());

final buyerInvoiceProvider = FutureProvider.autoDispose.family<Invoice, String>(
  (ref, uuid) => ref.watch(buyerRepositoryProvider).invoice(uuid),
);
