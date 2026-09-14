<?php

namespace App\Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Accounting\Models\JournalEntry
 */
class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'entry_date' => $this->entry_date?->toDateString(),
            'reference' => $this->reference,
            'description' => $this->description,
            'status' => $this->status,
            'source_type' => $this->source_type,
            'total' => $this->whenLoaded('lines', fn () => $this->totalDebits()),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy?->name),
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
