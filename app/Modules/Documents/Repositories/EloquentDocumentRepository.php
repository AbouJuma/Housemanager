<?php

namespace App\Modules\Documents\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Documents\Models\Document;

class EloquentDocumentRepository extends BaseRepository implements DocumentRepositoryInterface
{
    public function __construct(Document $model)
    {
        parent::__construct($model);
    }
}
