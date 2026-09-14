<?php

namespace Tests\Support\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesCompanyScopedWidgetsTable
{
    protected function createCompanyScopedWidgetsTable(): void
    {
        Schema::create('company_scoped_widgets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
        });
    }
}
