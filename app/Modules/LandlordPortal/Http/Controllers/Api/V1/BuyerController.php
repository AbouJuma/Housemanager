<?php

namespace App\Modules\LandlordPortal\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordContactUpdateRequest;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** People buying units in the landlord's properties - list, detail, edit. */
class BuyerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $landlord = $request->user();
        $search = $request->string('search')->trim()->toString();

        $page = $landlord->buyersQuery()
            ->with($this->with($landlord))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->when(in_array($request->query('status'), ['active', 'inactive'], true), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'data' => $page->getCollection()->map(fn (Buyer $buyer) => $this->present($buyer))->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, Buyer $buyer): JsonResponse
    {
        $landlord = $request->user();
        abort_unless($landlord->buyersQuery()->whereKey($buyer->id)->exists(), 404);

        return response()->json(['data' => $this->present($buyer->load($this->with($landlord)))]);
    }

    public function update(LandlordContactUpdateRequest $request, Buyer $buyer): JsonResponse
    {
        $buyer->update($request->validatedDetails());

        return response()->json(['data' => $this->present($buyer->fresh()->load($this->with($request->user())))]);
    }

    protected function with(Landlord $landlord): array
    {
        return ['sales' => fn ($q) => $q->whereHas('unit.building.property', fn (Builder $p) => $p->where('landlord_id', $landlord->id))
            ->with('unit:id,uuid,unit_number,building_id', 'unit.building:id,property_id', 'unit.building.property:id,uuid,name')];
    }

    protected function present(Buyer $buyer): array
    {
        return [
            'uuid' => $buyer->uuid,
            'name' => $buyer->name,
            'username' => $buyer->username,
            'phone' => $buyer->phone,
            'email' => $buyer->email,
            'status' => $buyer->status,
            'address' => $buyer->address,
            'id_number' => $buyer->id_number,
            'purchases' => $buyer->sales->map(fn (Sale $sale) => [
                'uuid' => $sale->uuid,
                'status' => $sale->status,
                'sale_date' => $sale->sale_date?->toDateString(),
                'unit_number' => $sale->unit?->unit_number,
                'property' => $sale->unit?->building?->property ? ['uuid' => $sale->unit->building->property->uuid, 'name' => $sale->unit->building->property->name] : null,
                'total_price' => (float) $sale->total_price,
                'total_paid' => $sale->total_paid,
                'remaining_balance' => $sale->remaining_balance,
                'completion_percentage' => $sale->completion_percentage,
            ])->values()->all(),
        ];
    }
}
