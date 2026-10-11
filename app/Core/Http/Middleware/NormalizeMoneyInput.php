<?php

namespace App\Core\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * Money fields are typed with thousand separators ("1,250,000.50" -
 * see the money-input code in resources/js/app.js). The browser strips
 * them on a normal submit, but not on a programmatic form.submit() or
 * from an API client, so this strips them server-side before
 * validation's `numeric` rule ever sees the value.
 *
 * Only fields whose name looks like money are touched, and only when
 * the whole value is a grouped number - "Plot 1,2" in a notes field, or
 * a phone number, is left alone.
 */
class NormalizeMoneyInput extends TransformsRequest
{
    protected const MONEY_FIELD = '/(amount|price|rent|deposit|cost|fee|total|paid|balance|salary|budget|value|debit|credit)$/i';

    protected const GROUPED_NUMBER = '/^\s*-?\d{1,3}(,\d{3})+(\.\d+)?\s*$/';

    protected function transform($key, $value)
    {
        if (! is_string($value)) {
            return $value;
        }

        $field = last(explode('.', (string) $key));

        if (preg_match(self::MONEY_FIELD, $field) && preg_match(self::GROUPED_NUMBER, $value)) {
            return str_replace(',', '', trim($value));
        }

        return $value;
    }
}
