<?php

namespace App\Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Modules\Accounting\Models\JournalEntryLine
 */
class JournalEntryLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'account' => $this->whenLoaded('account', fn () => [
                'uuid' => $this->account->uuid,
                'code' => $this->account->code,
                'name' => $this->account->name,
            ]),
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'memo' => $this->memo,
        ];
    }
}
