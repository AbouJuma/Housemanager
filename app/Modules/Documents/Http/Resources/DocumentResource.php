<?php

namespace App\Modules\Documents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Documents\Models\Document
 */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'category' => $this->category,
            'description' => $this->description,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'documentable_type' => $this->documentableTypeKey(),
            'documentable' => $this->whenLoaded('documentable', fn () => $this->documentable ? [
                'uuid' => $this->documentable->uuid,
                'name' => $this->documentable->name ?? $this->documentable->title ?? null,
            ] : null),
            'file_url' => $this->fileUrl(),
            'file_name' => $this->fileName(),
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => $this->uploadedBy?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
