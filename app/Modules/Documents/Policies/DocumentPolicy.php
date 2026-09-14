<?php

namespace App\Modules\Documents\Policies;

use App\Models\User;
use App\Modules\Documents\Models\Document;

/**
 * Same no-SaaS-owner-bypass pattern as every other Phase C/D domain
 * policy (ARCHITECTURE.md §14/§15/§16) - a company's documents,
 * including whatever they're attached to, are its own.
 */
class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('documents.viewAny');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->company_id === $document->company_id && $user->hasPermissionTo('documents.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->company_id === $document->company_id && $user->hasPermissionTo('documents.update');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->company_id === $document->company_id && $user->hasPermissionTo('documents.delete');
    }
}
