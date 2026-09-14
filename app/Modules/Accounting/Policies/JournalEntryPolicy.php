<?php

namespace App\Modules\Accounting\Policies;

use App\Models\User;
use App\Modules\Accounting\Models\JournalEntry;

/**
 * No `update`/`delete` methods - a posted journal entry is immutable
 * (see JournalEntryService's docblock); `void` is the only mutating
 * action after creation and gets its own permission rather than being
 * folded under `update`, since there's no generic "update" action to
 * fold it into (ARCHITECTURE.md §12).
 */
class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('journal-entries.viewAny');
    }

    public function view(User $user, JournalEntry $entry): bool
    {
        return $user->company_id === $entry->company_id && $user->hasPermissionTo('journal-entries.view');
    }

    public function create(User $user): bool
    {
        return ! $user->isSaasOwner() && $user->hasPermissionTo('journal-entries.create');
    }

    public function void(User $user, JournalEntry $entry): bool
    {
        return $user->company_id === $entry->company_id && $user->hasPermissionTo('journal-entries.void');
    }
}
