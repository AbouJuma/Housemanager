<?php

namespace App\Modules\Accounting\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Http\Requests\StoreAccountRequest;
use App\Modules\Accounting\Http\Requests\UpdateAccountRequest;
use App\Modules\Accounting\Http\Resources\AccountResource;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    public function __construct(protected AccountService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Account::class);

        return AccountResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('type')->toString() ?: null,
            )
        );
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->service->create([...$request->validated(), 'company_id' => $request->user()->company_id]);

        return AccountResource::make($account)->response()->setStatusCode(201);
    }

    public function show(Account $account): AccountResource
    {
        $this->authorize('view', $account);

        return AccountResource::make($account);
    }

    public function update(UpdateAccountRequest $request, Account $account): AccountResource
    {
        $account = $this->service->update($account, $request->validated());

        return AccountResource::make($account);
    }

    public function destroy(Account $account): JsonResponse
    {
        $this->authorize('delete', $account);

        $this->service->delete($account);

        return response()->json(null, 204);
    }
}
