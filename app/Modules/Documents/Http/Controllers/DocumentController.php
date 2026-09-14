<?php

namespace App\Modules\Documents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Documents\Http\Requests\StoreDocumentRequest;
use App\Modules\Documents\Http\Requests\UpdateDocumentRequest;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Leases\Models\Lease;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(protected DocumentService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Document::class);

        $typeMap = Document::documentableTypeMap();
        $typeKey = $request->string('documentable_type')->toString() ?: null;

        $documents = $this->service->paginate(
            $request->string('search')->toString() ?: null,
            $request->string('category')->toString() ?: null,
            $typeKey ? ($typeMap[$typeKey] ?? null) : null,
            $request->boolean('expiring_soon'),
        );

        return view('documents::index', ['documents' => $documents]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Document::class);

        $typeKey = $request->query('documentable_type');
        $documentable = $this->resolveDocumentable($typeKey, $request->query('documentable_id'));

        return view('documents::create', [
            'documentableType' => $documentable ? $typeKey : null,
            'documentableId' => $documentable?->id,
            'documentableLabel' => $documentable ? $this->labelFor($documentable) : null,
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $typeKey = $request->validated('documentable_type');
        $typeMap = Document::documentableTypeMap();

        $document = $this->service->create([
            ...$request->validated(),
            'documentable_type' => $typeKey ? $typeMap[$typeKey] : null,
            'company_id' => $request->user()->company_id,
            'uploaded_by_id' => $request->user()->id,
        ]);

        return redirect()->route('documents.show', $document)->with('success', 'Document uploaded.');
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load(['documentable', 'uploadedBy']);

        return view('documents::show', [
            'document' => $document,
            'documentableLabel' => $document->documentable ? $this->labelFor($document->documentable) : null,
        ]);
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        return view('documents::edit', [
            'document' => $document,
            'documentableLabel' => $document->documentable ? $this->labelFor($document->documentable) : null,
        ]);
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->service->update($document, $request->validated());

        return redirect()->route('documents.show', $document)->with('success', 'Document updated.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $this->service->delete($document);

        return redirect()->route('documents.index')->with('success', 'Document deleted.');
    }

    protected function resolveDocumentable(?string $typeKey, mixed $id): ?Model
    {
        $map = Document::documentableTypeMap();

        if (! $typeKey || ! $id || ! isset($map[$typeKey])) {
            return null;
        }

        return $map[$typeKey]::query()->find($id);
    }

    protected function labelFor(Model $documentable): string
    {
        if ($documentable instanceof Lease) {
            return 'Lease #'.$documentable->id.' — '.($documentable->tenant?->name ?? 'Unknown tenant');
        }

        return $documentable->name ?? ('#'.$documentable->id);
    }
}
