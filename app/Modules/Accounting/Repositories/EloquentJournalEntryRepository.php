<?php

namespace App\Modules\Accounting\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Accounting\Models\JournalEntry;

class EloquentJournalEntryRepository extends BaseRepository implements JournalEntryRepositoryInterface
{
    public function __construct(JournalEntry $model)
    {
        parent::__construct($model);
    }
}
