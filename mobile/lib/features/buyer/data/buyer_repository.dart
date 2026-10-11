import '../../../core/api/api_client.dart';
import '../../../core/api/json_helpers.dart';
import '../../../core/api/paginated_list.dart';
import '../../../core/models/models.dart';

class BuyerDashboard {
  final List<Sale> sales;
  final int totalPurchases;
  final double totalSpent;
  final double outstandingBalance;
  final int outstandingInvoiceCount;

  BuyerDashboard({
    required this.sales,
    required this.totalPurchases,
    required this.totalSpent,
    required this.outstandingBalance,
    required this.outstandingInvoiceCount,
  });
}

/// Mirrors App\Modules\BuyerPortal\Http\Controllers\Api\V1\*.
class BuyerRepository {
  BuyerRepository(this._apiClient);

  final ApiClient _apiClient;

  Future<BuyerDashboard> dashboard() async {
    final response = await _apiClient.dio.get('/portal/buyer/dashboard');
    final body = response.data as Map<String, dynamic>;

    return BuyerDashboard(
      sales: (body['sales'] as List<dynamic>).map((e) => Sale.fromJson(e as Map<String, dynamic>)).toList(),
      totalPurchases: asInt(body['total_purchases']),
      totalSpent: asDouble(body['total_spent']),
      outstandingBalance: asDouble(body['outstanding_balance']),
      outstandingInvoiceCount: asInt(body['outstanding_invoice_count']),
    );
  }

  Future<List<Sale>> sales() async {
    final response = await _apiClient.dio.get('/portal/buyer/sales');
    return unwrapList(response.data).map((e) => Sale.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PageResult<Sale>> salesPage(int page, ListFilters filters) =>
      fetchPagedList(_apiClient, '/portal/buyer/sales', Sale.fromJson, page: page, filters: filters);

  Future<Sale> sale(String uuid) async {
    final response = await _apiClient.dio.get('/portal/buyer/sales/$uuid');
    return Sale.fromJson(unwrapObject(response.data));
  }

  Future<List<Invoice>> invoices() async {
    final response = await _apiClient.dio.get('/portal/buyer/invoices');
    return unwrapList(response.data).map((e) => Invoice.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<PageResult<Invoice>> invoicesPage(int page, ListFilters filters) =>
      fetchPagedList(_apiClient, '/portal/buyer/invoices', Invoice.fromJson, page: page, filters: filters);

  Future<Invoice> invoice(String uuid) async {
    final response = await _apiClient.dio.get('/portal/buyer/invoices/$uuid');
    return Invoice.fromJson(unwrapObject(response.data));
  }

  Future<String> payInvoice(String uuid, double amount) async {
    final response = await _apiClient.dio.post('/portal/buyer/invoices/$uuid/pay', data: {'amount': amount});
    return (response.data as Map<String, dynamic>)['message'] as String;
  }
}
