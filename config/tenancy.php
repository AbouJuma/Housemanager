<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company (Tenant) Model
    |--------------------------------------------------------------------------
    |
    | Fully-qualified class name of the model that represents a company
    | (tenant). Set by the Companies module once it exists; the
    | App\Core\Concerns\BelongsToCompany trait resolves relations
    | against this value so Core never has a compile-time dependency
    | on a module built after it.
    |
    */

    'company_model' => \App\Modules\Companies\Models\Company::class,

];
