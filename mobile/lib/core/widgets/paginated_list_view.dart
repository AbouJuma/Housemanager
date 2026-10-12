import 'package:flutter/material.dart';
import 'error_view.dart';
import '../l10n/l10n.dart';

import '../theme/app_theme.dart';

import '../api/paginated_list.dart';
import 'filter_bar.dart';

/// Generic "fetch a Laravel-paginated endpoint, filter by status/date
/// range, load more on scroll" list - every filterable list in this app
/// (invoices x2, maintenance requests x2, expenses, sales) is the exact
/// same shape, so this is written once as a plain StatefulWidget
/// (deliberately not a Riverpod AsyncNotifier.family - this app's
/// Riverpod version has no code generation set up, and the manual/
/// runtime API for a family AsyncNotifier is awkward enough that a
/// plain widget-owned State is both simpler and more obviously correct
/// here) instead of duplicated six times.
class PaginatedListView<T> extends StatefulWidget {
  const PaginatedListView({
    super.key,
    required this.fetchPage,
    required this.itemBuilder,
    required this.emptyLabel,
    this.statusOptions,
    this.showDateRange = true,
    this.allLabel = 'All',
  });

  final PageFetcher<T> fetchPage;
  final Widget Function(BuildContext, T) itemBuilder;
  final String emptyLabel;
  final List<String>? statusOptions;
  final bool showDateRange;
  final String allLabel;

  @override
  State<PaginatedListView<T>> createState() => _PaginatedListViewState<T>();
}

class _PaginatedListViewState<T> extends State<PaginatedListView<T>> {
  final _scrollController = ScrollController();
  final List<T> _items = [];
  ListFilters _filters = const ListFilters.none();
  int _page = 1;
  bool _hasMore = true;
  bool _loading = true;
  bool _loadingMore = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _loadFirstPage();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_hasMore || _loadingMore || _loading) return;
    if (_scrollController.position.pixels > _scrollController.position.maxScrollExtent - 200) {
      _loadNextPage();
    }
  }

  Future<void> _loadFirstPage() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final result = await widget.fetchPage(1, _filters);
      setState(() {
        _items
          ..clear()
          ..addAll(result.items);
        _page = 1;
        _hasMore = result.hasMore;
      });
    } catch (e) {
      setState(() => _error = e);
    } finally {
      setState(() => _loading = false);
    }
  }

  Future<void> _loadNextPage() async {
    setState(() => _loadingMore = true);
    try {
      final result = await widget.fetchPage(_page + 1, _filters);
      setState(() {
        _items.addAll(result.items);
        _page += 1;
        _hasMore = result.hasMore;
      });
    } catch (_) {
      // A failed "load more" just stops there - the user already has the
      // first page's worth of real data, no need for a disruptive error.
    } finally {
      setState(() => _loadingMore = false);
    }
  }

  void _onFiltersChanged(ListFilters filters) {
    setState(() => _filters = filters);
    _loadFirstPage();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        if (widget.statusOptions != null)
          FilterBar(
            statusOptions: widget.statusOptions!,
            initial: _filters,
            onChanged: _onFiltersChanged,
            showDateRange: widget.showDateRange,
            allLabel: widget.allLabel,
          ),
        Expanded(child: _buildBody()),
      ],
    );
  }

  Widget _buildBody() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) {
      return ErrorView(error: _error!, onRetry: _loadFirstPage);

    }
    if (_items.isEmpty) {
      return Center(child: Text(context.tr(widget.emptyLabel), style: TextStyle(color: context.mutedText)));
    }

    return RefreshIndicator(
      onRefresh: _loadFirstPage,
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.all(16),
        itemCount: _items.length + (_hasMore ? 1 : 0),
        separatorBuilder: (_, _) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          if (index >= _items.length) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
            );
          }
          return widget.itemBuilder(context, _items[index]);
        },
      ),
    );
  }
}
