<?php

namespace App\Modules\Documents\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Policies\DocumentPolicy;
use App\Modules\Documents\Repositories\DocumentRepositoryInterface;
use App\Modules\Documents\Repositories\EloquentDocumentRepository;
use Illuminate\Support\Facades\Gate;

class DocumentsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DocumentRepositoryInterface::class, EloquentDocumentRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Document::class, DocumentPolicy::class);
    }
}
