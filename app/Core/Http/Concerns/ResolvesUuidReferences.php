<?php

namespace App\Core\Http\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Some FormRequests (StoreLeaseRequest's tenant_id/unit_id,
 * StoreInvoiceRequest's lease_id) validate a foreign key against the
 * internal numeric `id` column - correct for the web form, whose
 * server-rendered <select> options are built from that same id
 * (ARCHITECTURE.md's HasUuid: the numeric id is for fast joins/indexes,
 * never meant to leave the app). But every JsonResource in this app
 * deliberately only ever exposes `uuid` (the "public, non-enumerable
 * identity" HasUuid's own docblock describes), so a JSON API client -
 * this app's own mobile app included - can never submit a numeric id
 * because it never receives one.
 *
 * Rather than fork the FormRequest (duplicating its other business
 * rules - company scoping, "not blacklisted", "vacant units only" -
 * across a web and an API copy that could drift out of sync),
 * `prepareForValidation()` in the requests using this trait silently
 * translates a uuid-shaped input into its numeric id BEFORE the
 * `Rule::exists(..., 'id')` check runs. A web submission's numeric
 * value is already numeric and passes through unchanged - this is
 * additive, not a behavior change for the existing web form.
 */
trait ResolvesUuidReferences
{
    protected function resolveUuidReference(string $table, ?string $value): ?string
    {
        if ($value === null || ctype_digit($value)) {
            return $value;
        }

        $id = DB::table($table)->where('uuid', $value)->value('id');

        // Deliberately return the original (invalid) value, not null,
        // when nothing matches - letting the real Rule::exists check
        // fail normally and report "the selected X is invalid" rather
        // than this trait silently swallowing a bad reference.
        return $id !== null ? (string) $id : $value;
    }
}
