import 'api_client.dart';

/// Optional filters every filterable list screen (invoices, maintenance
/// requests, expenses, sales) supports.
class ListFilters {
  final String? status;
  final DateTime? from;
  final DateTime? to;

  const ListFilters({this.status, this.from, this.to});

  const ListFilters.none() : this();

  Map<String, dynamic> toQuery() => {
        if (status != null) 'status': status,
        if (from != null) 'from': _isoDate(from!),
        if (to != null) 'to': _isoDate(to!),
      };

  static String _isoDate(DateTime date) =>
      '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';

  bool get isEmpty => status == null && from == null && to == null;
}

class PageResult<T> {
  final List<T> items;
  final bool hasMore;

  const PageResult(this.items, this.hasMore);
}

typedef PageFetcher<T> = Future<PageResult<T>> Function(int page, ListFilters filters);

/// Shared "GET a paginated endpoint, parse data+meta" logic - every
/// repository's paginated list method is this same shape, just with a
/// different path and fromJson.
Future<PageResult<T>> fetchPagedList<T>(
  ApiClient apiClient,
  String path,
  T Function(Map<String, dynamic>) fromJson, {
  required int page,
  required ListFilters filters,
}) async {
  final response = await apiClient.dio.get(path, queryParameters: {...filters.toQuery(), 'page': page});
  final body = response.data as Map<String, dynamic>;
  final items = (body['data'] as List<dynamic>).map((e) => fromJson(e as Map<String, dynamic>)).toList();
  final meta = body['meta'] as Map<String, dynamic>?;
  final currentPage = meta?['current_page'] as int? ?? page;
  final lastPage = meta?['last_page'] as int? ?? page;

  return PageResult(items, currentPage < lastPage);
}
