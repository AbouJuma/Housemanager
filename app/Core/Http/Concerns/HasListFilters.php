<?php

namespace App\Core\Http\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

/**
 * Shared `?status=&from=&to=` query-param filtering for the mobile
 * portal APIs' list endpoints - extracted once here rather than
 * duplicated per controller, since every list (leases, invoices,
 * maintenance requests, properties, expenses, sales) needs the exact
 * same "optionally filter by status, optionally filter a date column
 * between two bounds" shape. Pairs with ->paginate() at each call site
 * (not done here, since the page size may reasonably differ per
 * resource).
 *
 * Accepts Builder|Relation - every call site starts from a relation
 * query (e.g. $tenant->leases(), a HasMany/HasManyThrough), not a bare
 * model query, and a Relation instance isn't a Builder itself even
 * though it forwards every query method to one via __call().
 */
trait HasListFilters
{
    protected function applyStatusFilter(Builder|Relation $query, Request $request, string $column = 'status'): Builder|Relation
    {
        return $query->when(
            $request->filled('status'),
            fn ($q) => $q->where($column, $request->string('status')->toString())
        );
    }

    protected function applyDateRangeFilter(Builder|Relation $query, Request $request, string $column): Builder|Relation
    {
        return $query
            ->when(
                $request->filled('from'),
                fn ($q) => $q->whereDate($column, '>=', $request->string('from')->toString())
            )
            ->when(
                $request->filled('to'),
                fn ($q) => $q->whereDate($column, '<=', $request->string('to')->toString())
            );
    }
}
