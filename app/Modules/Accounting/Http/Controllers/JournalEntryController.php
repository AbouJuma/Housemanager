<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Http\Requests\StoreJournalEntryRequest;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function __construct(protected JournalEntryService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', JournalEntry::class);

        $entries = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return view('accounting::journal-entries.index', compact('entries'));
    }

    public function create(): View
    {
        $this->authorize('create', JournalEntry::class);

        return view('accounting::journal-entries.create', ['accounts' => $this->accountOptions()]);
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $entry = $this->service->post([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'created_by_id' => $request->user()->id,
        ]);

        return redirect()->route('journal-entries.show', $entry)->with('success', 'Journal entry posted.');
    }

    public function show(JournalEntry $entry): View
    {
        $this->authorize('view', $entry);

        return view('accounting::journal-entries.show', [
            'entry' => $entry->load(['lines.account', 'createdBy', 'source']),
        ]);
    }

    public function void(Request $request, JournalEntry $entry): RedirectResponse
    {
        $this->authorize('void', $entry);

        $this->service->void($entry, $request->string('reason')->toString() ?: null);

        return redirect()->route('journal-entries.show', $entry)->with('success', 'Journal entry voided.');
    }

    protected function accountOptions(): \Illuminate\Support\Collection
    {
        return Account::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'type']);
    }
}
