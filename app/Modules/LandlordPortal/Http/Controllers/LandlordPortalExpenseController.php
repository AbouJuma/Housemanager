<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandlordPortalExpenseController extends Controller
{
    public function index(): View
    {
        $landlord = Auth::guard('landlord')->user();

        return view('landlord-portal::expenses.index', [
            'expenses' => $landlord->expenses()->with(['account', 'property'])->latest('expense_date')->get(),
        ]);
    }
}
