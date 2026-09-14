<?php

namespace App\Modules\Expenses\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Expenses\Policies\ExpensePolicy;
use App\Modules\Expenses\Repositories\EloquentExpenseRepository;
use App\Modules\Expenses\Repositories\ExpenseRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class ExpensesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExpenseRepositoryInterface::class, EloquentExpenseRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Expense::class, ExpensePolicy::class);
    }
}
