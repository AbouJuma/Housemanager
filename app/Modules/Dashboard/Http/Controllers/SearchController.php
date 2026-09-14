<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Backs the command palette (Ctrl+K). Deliberately reuses
 * CompanyPolicy/UserPolicy rather than inventing separate search
 * authorization: results a user can't otherwise see are omitted,
 * never merely hidden client-side.
 */
class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        if (mb_strlen($query) < 2) {
            return response()->json(['groups' => []]);
        }

        $user = $request->user();
        $groups = [];

        if ($user->can('viewAny', Company::class)) {
            $companies = Company::query()
                ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('slug', 'like', "%{$query}%"))
                ->limit(5)
                ->get();

            $groups[] = [
                'label' => 'Companies',
                'items' => $companies->map(fn (Company $company) => [
                    'title' => $company->name,
                    'subtitle' => $company->slug,
                    'url' => route('companies.show', $company),
                ])->all(),
            ];
        }

        if ($user->can('viewAny', User::class)) {
            $usersQuery = User::query()
                ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%"));

            if (! $user->isSaasOwner()) {
                $usersQuery->where('company_id', $user->company_id);
            }

            $groups[] = [
                'label' => 'Users',
                'items' => $usersQuery->limit(5)->get()->map(fn (User $match) => [
                    'title' => $match->name,
                    'subtitle' => $match->email,
                    'url' => route('users.show', $match),
                ])->all(),
            ];
        }

        return response()->json(['groups' => $groups]);
    }
}
