import '../../../core/api/api_client.dart';
import '../../../core/api/json_helpers.dart';
import '../../../core/api/paginated_list.dart';
import '../../../core/models/models.dart';

class TenantDashboard {
  final List<Lease> activeLeases;
  final double outstandingBalance;
  final int outstandingInvoiceCount;

  TenantDashboard({required this.activeLeases, required this.outstandingBalance, required this.outstandingInvoiceCount});
}

/// Everything the tenant mobile screens need - mirrors
/// App\Modules\TenantPortal\Http\Controllers\Api\V1\* one method at a
/// time. See core/api/json_helpers.dart for why list/show responses
/// unwrap `data` but the dashboard response doesn't.
class TenantRepository {
  TenantRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<TenantDashboard> dashboard() async {
    final response = await _apiClient.dio.get('/portal/tenant/dashboard');
    final body = response.data as Map<String, dynamic>;

    return TenantDashboard(
      activeLeases: (body['active_leases'] as List<dynamic>).map((e) => Lease.fromJson(e as Map<String, dynamic>)).toList(),
      outstandingBalance: asDouble(body['outstanding_balance']),
      outstandingInvoiceCount: asInt(body['outstanding_invoice_count']),
    );
  }

  Future<List<Lease>> leases() async {
    final response = await _apiClient.dio.get('/portal/tenant/leases');
    return unwrapList(response.data).map((e) => Lease.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<Lease> lease(String uuid) async {
    final response = await _apiClient.dio.get('/portal/tenant/leases/$uuid');
    return Lease.fromJson(unwrapObject(response.data));
  }

  Future<List<Invoice>> invoices() async {
    final response = await _apiClient.dio.get('/portal/tenant/invoices');
    return unwrapList(response.data).map((e) => Invoice.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PageResult<Invoice>> invoicesPage(int page, ListFilters filters) =>
      fetchPagedList(_apiClient, '/portal/tenant/invoices', Invoice.fromJson, page: page, filters: filters);

  Future<Invoice> invoice(String uuid) async {
    final response = await _apiClient.dio.get('/portal/tenant/invoices/$uuid');
    return Invoice.fromJson(unwrapObject(response.data));
  }

  Future<String> payInvoice(String uuid, double amount) async {
    final response = await _apiClient.dio.post('/portal/tenant/invoices/$uuid/pay', data: {'amount': amount});
    return (response.data as Map<String, dynamic>)['message'] as String;
  }

  Future<List<MaintenanceRequestItem>> maintenanceRequests() async {
    final response = await _apiClient.dio.get('/portal/tenant/maintenance-requests');
    return unwrapList(response.data).map((e) => MaintenanceRequestItem.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PageResult<MaintenanceRequestItem>> maintenanceRequestsPage(int page, ListFilters filters) => fetchPagedList(
        _apiClient,
        '/portal/tenant/maintenance-requests',
        MaintenanceRequestItem.fromJson,
        page: page,
        filters: filters,
      );

  Future<List<DocumentItem>> documents() async {
    final response = await _apiClient.dio.get('/portal/tenant/documents');
    return unwrapList(response.data).map((e) => DocumentItem.fromJson(e as Map<String, dynamic>)).toList();
  }
}
