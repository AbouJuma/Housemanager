import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/json_helpers.dart';
import '../../../core/models/models.dart';
import '../../../core/providers.dart';

/// Tenant and landlord both expose an identical `/portal/{role}/documents`
/// shape (App\Modules\TenantPortal\Http\Controllers\Api\V1\
/// DocumentController and its LandlordPortal equivalent), so one small
/// role-parametrized repository covers both instead of duplicating a
/// near-identical class per role. Buyers don't have a documents
/// endpoint (see BuyerPortal's provider docblock) - this is
/// deliberately not used for that role.
final documentsProvider = FutureProvider.autoDispose.family<List<DocumentItem>, String>((ref, role) async {
  final apiClient = ref.watch(apiClientProvider);
  final response = await apiClient.dio.get('/portal/$role/documents');
  return unwrapList(response.data).map((e) => DocumentItem.fromJson(e as Map<String, dynamic>)).toList();
});
