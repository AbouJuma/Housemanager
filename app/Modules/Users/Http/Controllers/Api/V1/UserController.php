<?php

namespace App\Modules\Users\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Users\Http\Requests\StoreUserRequest;
use App\Modules\Users\Http\Requests\UpdateUserRequest;
use App\Modules\Users\Http\Resources\UserResource;
use App\Modules\Users\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $companyId = $request->user()->isSaasOwner() ? null : $request->user()->company_id;

        return UserResource::collection(
            $this->service->paginate($companyId, $request->string('search')->toString() ?: null)
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $attributes = $request->validated();

        if (! $request->user()->isSaasOwner()) {
            $attributes['company_id'] = $request->user()->company_id;
        }

        $user = $this->service->create($attributes);

        return UserResource::make($user)->response()->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return UserResource::make($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->service->update($user, $request->validated());

        return UserResource::make($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->service->delete($user);

        return response()->json(null, 204);
    }
}
