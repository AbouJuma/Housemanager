<?php

namespace App\Modules\Invoices\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Invoices\Models\Invoice;

class EloquentInvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }
}
