<?php

namespace App\Modules\Accounting\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Http\Requests\StoreJournalEntryRequest;
use App\Modules\Accounting\Http\Resources\JournalEntryResource;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Services\JournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JournalEntryController extends Controller
{
    public function __construct(protected JournalEntryService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', JournalEntry::class);

        return JournalEntryResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('status')->toString() ?: null,
            )
        );
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $entry = $this->service->post([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
            'created_by_id' => $request->user()->id,
        ]);

        return JournalEntryResource::make($entry->load('lines.account'))->response()->setStatusCode(201);
    }

    public function show(JournalEntry $entry): JournalEntryResource
    {
        $this->authorize('view', $entry);

        return JournalEntryResource::make($entry->load('lines.account'));
    }

    public function void(Request $request, JournalEntry $entry): JournalEntryResource
    {
        $this->authorize('void', $entry);

        $this->service->void($entry, $request->string('reason')->toString() ?: null);

        return JournalEntryResource::make($entry->fresh('lines.account'));
    }
}
