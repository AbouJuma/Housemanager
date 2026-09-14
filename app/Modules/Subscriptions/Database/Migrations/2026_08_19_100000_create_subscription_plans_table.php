<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            // No company_id - this is a platform-level catalog the
            // SaaS owner maintains, not tenant-owned data, the same
            // reasoning integrations.provider's global driver list
            // (rather than a per-company one) already established.
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            // Nullable = unlimited. Informational limits only (shown on
            // the company's own billing page as "X of Y used") - not
            // enforced at creation time on Property/Unit/User, a
            // deliberate scope cut documented in ARCHITECTURE.md §22:
            // enforcing them there would mean Modules 13/3 (Properties,
            // Users) reaching forward into Module 29, the wrong
            // direction for this app's established dependency rules.
            $table->unsignedInteger('max_properties')->nullable();
            $table->unsignedInteger('max_units')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
