<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Http\Requests\StoreAccountRequest;
use App\Modules\Accounting\Http\Requests\UpdateAccountRequest;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(protected AccountService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Account::class);

        $accounts = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('type')->toString() ?: null,
        );

        return view('accounting::accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $this->authorize('create', Account::class);

        return view('accounting::accounts.create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = $this->service->create([...$request->validated(), 'company_id' => $request->user()->company_id]);

        return redirect()->route('accounts.show', $account)->with('success', "Account \"{$account->name}\" created.");
    }

    public function show(Account $account): View
    {
        $this->authorize('view', $account);

        return view('accounting::accounts.show', [
            'account' => $account,
            'recentLines' => $account->lines()->with('journalEntry')->latest()->limit(20)->get(),
        ]);
    }

    public function edit(Account $account): View
    {
        $this->authorize('update', $account);

        return view('accounting::accounts.edit', compact('account'));
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $this->service->update($account, $request->validated());

        return redirect()->route('accounts.show', $account)->with('success', 'Account updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $this->service->delete($account);

        return redirect()->route('accounts.index')->with('success', "Account \"{$account->name}\" deleted.");
    }

    /**
     * AJAX endpoint for creating an account from another form
     */
    public function storeAjax(StoreAccountRequest $request): \Illuminate\Http\JsonResponse
    {
        $account = $this->service->create([...$request->validated(), 'company_id' => $request->user()->company_id]);

        return response()->json([
            'success' => true,
            'account' => [
                'id' => $account->id,
                'uuid' => $account->uuid,
                'code' => $account->code,
                'name' => $account->name,
            ],
        ]);
    }
}
