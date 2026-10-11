import '../../../core/api/api_client.dart';
import '../../../core/api/json_helpers.dart';
import '../../../core/api/paginated_list.dart';
import '../../../core/models/models.dart';

/// Staff CRUD over the app's own existing, already-full `/api/v1/*`
/// admin endpoints (Tenants, Leases, Invoices) - no `/portal/...`
/// prefix, these are the same endpoints the web back office's staff
/// screens are built on. First tier of staff mobile CRUD (Tenants,
/// Leases, Invoices/Payments) - Properties/Units and Sales/Buyers
/// follow the same pattern in a later phase.
class StaffRepository {
  StaffRepository(this._apiClient);

  final ApiClient _apiClient;

  // --- Tenants ---

  Future<PageResult<StaffTenant>> tenantsPage(int page, ListFilters filters, {String? search}) => fetchPagedList(
        _apiClient,
        '/tenants',
        StaffTenant.fromJson,
        page: page,
        filters: filters,
      );

  Future<StaffTenant> tenant(String uuid) async {
    final response = await _apiClient.dio.get('/tenants/$uuid');
    return StaffTenant.fromJson(unwrapObject(response.data));
  }

  Future<StaffTenant> createTenant(Map<String, dynamic> data) async {
    final response = await _apiClient.dio.post('/tenants', data: data);
    return StaffTenant.fromJson(unwrapObject(response.data));
  }

  Future<StaffTenant> updateTenant(String uuid, Map<String, dynamic> data) async {
    final response = await _apiClient.dio.put('/tenants/$uuid', data: data);
    return StaffTenant.fromJson(unwrapObject(response.data));
  }

  Future<void> deleteTenant(String uuid) => _apiClient.dio.delete('/tenants/$uuid');

  /// For the lease form's tenant picker - a plain, unpaginated search
  /// (server caps to a page's worth internally); good enough for a
  /// type-ahead where a handful of matches is all that's shown anyway.
  Future<List<StaffTenant>> searchTenants(String query) async {
    final response = await _apiClient.dio.get('/tenants', queryParameters: {'search': query});
    return unwrapList(response.data).map((e) => StaffTenant.fromJson(e as Map<String, dynamic>)).toList();
  }

  // --- Leases ---

  Future<PageResult<Lease>> leasesPage(int page, ListFilters filters) =>
      fetchPagedList(_apiClient, '/leases', Lease.fromJson, page: page, filters: filters);

  Future<Lease> lease(String uuid) async {
    final response = await _apiClient.dio.get('/leases/$uuid');
    return Lease.fromJson(unwrapObject(response.data));
  }

  Future<Lease> createLease(Map<String, dynamic> data) async {
    final response = await _apiClient.dio.post('/leases', data: data);
    return Lease.fromJson(unwrapObject(response.data));
  }

  Future<Lease> updateLease(String uuid, Map<String, dynamic> data) async {
    final response = await _apiClient.dio.put('/leases/$uuid', data: data);
    return Lease.fromJson(unwrapObject(response.data));
  }

  /// For the invoice form's lease picker - same unpaginated
  /// type-ahead-search shape as [searchTenants].
  Future<List<Lease>> searchLeases(String query) async {
    final response = await _apiClient.dio.get('/leases', queryParameters: {'search': query});
    return unwrapList(response.data).map((e) => Lease.fromJson(e as Map<String, dynamic>)).toList();
  }

  // --- Properties/Buildings/Units (read-only, for the lease form's cascading picker) ---

  Future<List<PropertySummary>> properties() async {
    final response = await _apiClient.dio.get('/properties');
    return unwrapList(response.data).map((e) => PropertySummary.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<BuildingSummary>> buildings(String propertyUuid) async {
    final response = await _apiClient.dio.get('/properties/$propertyUuid/buildings');
    return unwrapList(response.data).map((e) => BuildingSummary.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<UnitSummary>> units(String buildingUuid) async {
    final response = await _apiClient.dio.get('/buildings/$buildingUuid/units');
    return unwrapList(response.data)
        .map((e) => UnitSummary.fromJson(e as Map<String, dynamic>))
        .where((unit) => unit.status == 'vacant')
        .toList();
  }

  // --- Invoices ---

  Future<PageResult<Invoice>> invoicesPage(int page, ListFilters filters) =>
      fetchPagedList(_apiClient, '/invoices', Invoice.fromJson, page: page, filters: filters);

  Future<Invoice> invoice(String uuid) async {
    final response = await _apiClient.dio.get('/invoices/$uuid');
    return Invoice.fromJson(unwrapObject(response.data));
  }

  Future<Invoice> createInvoice(Map<String, dynamic> data) async {
    final response = await _apiClient.dio.post('/invoices', data: data);
    return Invoice.fromJson(unwrapObject(response.data));
  }

  Future<Invoice> sendInvoice(String uuid) async {
    final response = await _apiClient.dio.post('/invoices/$uuid/send');
    return Invoice.fromJson(unwrapObject(response.data));
  }

  Future<Invoice> recordPayment(String uuid, double amount, {required String method, String? reference}) async {
    final response = await _apiClient.dio.post('/invoices/$uuid/payments', data: {
      'amount': amount,
      'paid_on': DateTime.now().toIso8601String().split('T').first,
      'method': method,
      'reference': ?reference,
    });
    return Invoice.fromJson(unwrapObject(response.data));
  }
}
