<?php

namespace App\Modules\Companies\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Companies\Http\Requests\StoreCompanyRequest;
use App\Modules\Companies\Http\Requests\UpdateCompanyRequest;
use App\Modules\Companies\Http\Resources\CompanyResource;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyController extends Controller
{
    public function __construct(protected CompanyService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Company::class);

        return CompanyResource::collection(
            $this->service->paginate($request->string('search')->toString() ?: null)
        );
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        [$company, $admin, $password] = $this->service->createWithAdmin($request->validated());

        return CompanyResource::make($company)
            ->additional(['admin' => ['name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email, 'password' => $password]])
            ->response()->setStatusCode(201);
    }

    public function show(Company $company): CompanyResource
    {
        $this->authorize('view', $company);

        return CompanyResource::make($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company): CompanyResource
    {
        $company = $this->service->update($company, $request->validated());

        return CompanyResource::make($company);
    }

    public function destroy(Company $company): JsonResponse
    {
        $this->authorize('delete', $company);

        $this->service->delete($company);

        return response()->json(null, 204);
    }
}
