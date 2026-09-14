<?php

namespace App\Modules\Companies\Events;

use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Events\Dispatchable;

class CompanyCreated
{
    use Dispatchable;

    public function __construct(public readonly Company $company)
    {
    }
}
