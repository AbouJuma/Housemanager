<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Nullable polymorphic pair, not a deferred FK to one
            // specific module - a document can be attached to a
            // Property/Landlord/Tenant/Lease/Vendor, or stand alone as a
            // company-wide record (a business license, a master
            // insurance policy). No FK constraint on a polymorphic
            // column - the same reasoning journal_entries.source_type/
            // source_id already established (ARCHITECTURE.md §12).
            $table->nullableMorphs('documentable');
            $table->string('title');
            $table->enum('category', [
                'contract', 'lease_agreement', 'id_document', 'certificate',
                'insurance', 'deed', 'invoice', 'license', 'other',
            ])->default('other');
            $table->text('description')->nullable();
            // For tracking renewals - a license, certificate, or
            // insurance policy that needs re-issuing before a date.
            // Nullable - most documents (a signed contract, an ID scan)
            // have no expiry.
            $table->date('expiry_date')->nullable();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'category']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
