<?php

namespace App\Modules\Landlords\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landlords\Http\Requests\StoreLandlordRequest;
use App\Modules\Landlords\Http\Requests\UpdateLandlordRequest;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Landlords\Services\LandlordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandlordController extends Controller
{
    public function __construct(protected LandlordService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Landlord::class);

        $landlords = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('type')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('landlords::index', compact('landlords'));
    }

    public function create(): View
    {
        $this->authorize('create', Landlord::class);

        return view('landlords::create');
    }

    public function store(StoreLandlordRequest $request): RedirectResponse|JsonResponse
    {
        $landlord = $this->service->create($request->validated());

        // The property form's "Add New" modal (properties::properties._form)
        // creates a landlord via fetch() without leaving the page - same
        // JSON-on-request pattern as BuyerController::store().
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'landlord' => $landlord,
                'message' => 'Landlord created successfully.',
            ]);
        }

        return redirect()->route('landlords.show', $landlord)->with('success', __('Landlord ":name" created. Portal login - username: :username, password: :password', ['name' => $landlord->name, 'username' => $landlord->username, 'password' => $this->service->issuedPassword()]));
    }

    public function show(Landlord $landlord): View
    {
        $this->authorize('view', $landlord);

        return view('landlords::show', [
            'landlord' => $landlord,
            'properties' => $landlord->properties()->withCount('buildings')->get(),
            'documents' => $landlord->documents()->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Landlord $landlord): View
    {
        $this->authorize('update', $landlord);

        return view('landlords::edit', compact('landlord'));
    }

    public function update(UpdateLandlordRequest $request, Landlord $landlord): RedirectResponse
    {
        $this->service->update($landlord, $request->validated());

        return redirect()->route('landlords.show', $landlord)->with('success', 'Landlord updated.');
    }

    public function destroy(Landlord $landlord): RedirectResponse
    {
        $this->authorize('delete', $landlord);

        $this->service->delete($landlord);

        return redirect()->route('landlords.index')->with('success', "Landlord \"{$landlord->name}\" deleted.");
    }
}
