<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordContactUpdateRequest;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Sales\Models\Buyer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** People buying units in the landlord's properties. */
class LandlordPortalBuyerController extends Controller
{
    public function index(Request $request): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'buyers.viewAny');
        $search = $request->string('search')->trim()->toString();

        return view('landlord-portal::buyers.index', [
            'buyers' => $landlord->buyersQuery()
                ->with(['sales' => fn ($q) => $q->whereHas('unit.building.property', fn (Builder $p) => $p->where('landlord_id', $landlord->id))
                    ->with('unit:id,uuid,unit_number,building_id', 'unit.building:id,property_id', 'unit.building.property:id,name')])
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'canUpdate' => LandlordAccess::can($landlord, 'buyers.update'),
        ]);
    }

    public function edit(Buyer $buyer): View
    {
        $landlord = Auth::guard('landlord')->user();
        LandlordAccess::authorize($landlord, 'buyers.update');
        abort_unless($landlord->buyersQuery()->whereKey($buyer->id)->exists(), 404);

        return view('landlord-portal::contacts.edit', ['person' => $buyer, 'kind' => 'buyer']);
    }

    public function update(LandlordContactUpdateRequest $request, Buyer $buyer): RedirectResponse
    {
        $buyer->update($request->validatedDetails());

        return redirect()->route('landlord-portal.buyers.index')->with('success', __(':name updated.', ['name' => $buyer->name]));
    }
}
