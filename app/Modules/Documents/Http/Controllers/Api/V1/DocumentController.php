<?php

namespace App\Modules\Documents\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Documents\Http\Requests\StoreDocumentRequest;
use App\Modules\Documents\Http\Requests\UpdateDocumentRequest;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentController extends Controller
{
    public function __construct(protected DocumentService $service)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Document::class);

        $typeMap = Document::documentableTypeMap();
        $typeKey = $request->string('documentable_type')->toString() ?: null;

        return DocumentResource::collection(
            $this->service->paginate(
                $request->string('search')->toString() ?: null,
                $request->string('category')->toString() ?: null,
                $typeKey ? ($typeMap[$typeKey] ?? null) : null,
                $request->boolean('expiring_soon'),
            )
        );
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $typeKey = $request->validated('documentable_type');
        $typeMap = Document::documentableTypeMap();

        $document = $this->service->create([
            ...$request->validated(),
            'documentable_type' => $typeKey ? $typeMap[$typeKey] : null,
            'company_id' => $request->user()->company_id,
            'uploaded_by_id' => $request->user()->id,
        ]);

        return DocumentResource::make($document->load(['documentable', 'uploadedBy']))->response()->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        $this->authorize('view', $document);

        return DocumentResource::make($document->load(['documentable', 'uploadedBy']));
    }

    public function update(UpdateDocumentRequest $request, Document $document): DocumentResource
    {
        $document = $this->service->update($document, $request->validated());

        return DocumentResource::make($document->load(['documentable', 'uploadedBy']));
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('delete', $document);

        $this->service->delete($document);

        return response()->json(null, 204);
    }
}
