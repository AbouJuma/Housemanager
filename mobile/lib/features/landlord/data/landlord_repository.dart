import '../../../core/api/api_client.dart';
import '../../../core/api/json_helpers.dart';
import '../../../core/api/paginated_list.dart';
import '../../../core/models/models.dart';

class LandlordDashboard {
  final Map<String, int> unitCounts;
  final double totalCollected;
  final double totalExpenses;
  final double netAmount;
  final List<MaintenanceRequestItem> recentMaintenanceRequests;

  LandlordDashboard({
    required this.unitCounts,
    required this.totalCollected,
    required this.totalExpenses,
    required this.netAmount,
    required this.recentMaintenanceRequests,
  });
}

/// Mirrors App\Modules\LandlordPortal\Http\Controllers\Api\V1\* -
/// same "one method per endpoint" shape as TenantRepository.
class LandlordRepository {
  LandlordRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<LandlordDashboard> dashboard() async {
    final response = await _apiClient.dio.get('/portal/landlord/dashboard');
    final body = response.data as Map<String, dynamic>;
    final unitCounts = (body['unit_counts'] as Map<String, dynamic>).map((key, value) => MapEntry(key, asInt(value)));

    return LandlordDashboard(
      unitCounts: unitCounts,
      totalCollected: asDouble(body['total_collected']),
      totalExpenses: asDouble(body['total_expenses']),
      netAmount: asDouble(body['net_amount']),
      recentMaintenanceRequests: (body['recent_maintenance_requests'] as List<dynamic>)
          .map((e) => MaintenanceRequestItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  Future<List<PropertyItem>> properties() async {
    final response = await _apiClient.dio.get('/portal/landlord/properties');
    return unwrapList(response.data).map((e) => PropertyItem.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PageResult<PropertyItem>> propertiesPage(int page, ListFilters filters) =>
      fetchPagedList(_apiClient, '/portal/landlord/properties', PropertyItem.fromJson, page: page, filters: filters);

  Future<PropertyItem> property(String uuid) async {
    final response = await _apiClient.dio.get('/portal/landlord/properties/$uuid');
    return PropertyItem.fromJson(unwrapObject(response.data));
  }

  Future<List<Expense>> expenses() async {
    final response = await _apiClient.dio.get('/portal/landlord/expenses');
    return unwrapList(response.data).map((e) => Expense.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PageResult<Expense>> expensesPage(int page, ListFilters filters) =>
      fetchPagedList(_apiClient, '/portal/landlord/expenses', Expense.fromJson, page: page, filters: filters);

  Future<List<MaintenanceRequestItem>> maintenanceRequests() async {
    final response = await _apiClient.dio.get('/portal/landlord/maintenance-requests');
    return unwrapList(response.data).map((e) => MaintenanceRequestItem.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PageResult<MaintenanceRequestItem>> maintenanceRequestsPage(int page, ListFilters filters) => fetchPagedList(
        _apiClient,
        '/portal/landlord/maintenance-requests',
        MaintenanceRequestItem.fromJson,
        page: page,
        filters: filters,
      );

  // --- Add / edit their own properties ---

  /// Raw property JSON, for the edit form.
  Future<Map<String, dynamic>> propertyJson(String uuid) async {
    final response = await _apiClient.dio.get('/portal/landlord/properties/$uuid');
    return unwrapObject(response.data);
  }

  Future<Map<String, dynamic>> createProperty(Map<String, dynamic> data) async {
    final response = await _apiClient.dio.post('/portal/landlord/properties', data: data);
    return unwrapObject(response.data);
  }

  Future<Map<String, dynamic>> updateProperty(String uuid, Map<String, dynamic> data) async {
    final response = await _apiClient.dio.put('/portal/landlord/properties/$uuid', data: data);
    return unwrapObject(response.data);
  }

  /// Property types, statuses and Tanzania regions -> districts (+ areas).
  Future<Map<String, dynamic>> propertyOptions() async {
    final response = await _apiClient.dio.get('/portal/landlord/property-options');
    return response.data as Map<String, dynamic>;
  }

  // --- Their tenants ---

  /// `filters.status`: 'with_lease' / 'no_lease' (null = all).
  Future<PageResult<Map<String, dynamic>>> tenantsPage(int page, ListFilters filters, {String? search}) async {
    final response = await _apiClient.dio.get('/portal/landlord/tenants', queryParameters: {
      'page': page,
      if (filters.status != null) 'status': filters.status,
      if (search != null && search.isNotEmpty) 'search': search,
    });
    final body = response.data as Map<String, dynamic>;
    final meta = body['meta'] as Map<String, dynamic>?;
    return PageResult(
      (body['data'] as List<dynamic>).cast<Map<String, dynamic>>(),
      (asIntOrNull(meta?['current_page']) ?? page) < (asIntOrNull(meta?['last_page']) ?? page),
    );
  }

  Future<Map<String, dynamic>> tenant(String uuid) async {
    final response = await _apiClient.dio.get('/portal/landlord/tenants/$uuid');
    return unwrapObject(response.data);
  }

  /// Returns the whole body: `data` (the tenant) and `login` (username + first password).
  Future<Map<String, dynamic>> createTenant(Map<String, dynamic> data) async {
    final response = await _apiClient.dio.post('/portal/landlord/tenants', data: data);
    return response.data as Map<String, dynamic>;
  }

  // --- Reports ---

  Future<Map<String, dynamic>> profitLoss({String? from, String? to}) async {
    final response = await _apiClient.dio.get('/portal/landlord/reports/profit-loss', queryParameters: {
      'from': ?from,
      'to': ?to,
    });
    return unwrapObject(response.data);
  }

  Future<Map<String, dynamic>> units({String? status}) async {
    final response = await _apiClient.dio.get('/portal/landlord/reports/units', queryParameters: {
      'status': ?status,
    });
    return unwrapObject(response.data);
  }
}
