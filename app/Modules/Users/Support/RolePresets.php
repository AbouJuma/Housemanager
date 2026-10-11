<?php

namespace App\Modules\Users\Support;

/**
 * Starting points for the staff roles companies most often add. Picking
 * one on the New Role page ticks its permissions - which is also exactly
 * what the mobile app shows that role (each app feature is unlocked by
 * its module's permissions), so no app change is needed for a new role.
 */
class RolePresets
{
    /** @return array<string, array{label: string, hint: string, permissions: list<string>}> */
    public static function all(): array
    {
        return [
            'accountant' => [
                'label' => 'Accountant',
                'hint' => 'Invoices, expenses, accounts and reports.',
                'permissions' => [
                    'invoices.viewAny', 'invoices.view', 'invoices.create', 'invoices.update',
                    'expenses.viewAny', 'expenses.view', 'expenses.create', 'expenses.update',
                    'accounts.viewAny', 'accounts.view',
                    'vendors.viewAny', 'vendors.view',
                    'reports.view',
                ],
            ],
            'caretaker' => [
                'label' => 'Caretaker / Cleaner',
                'hint' => 'Reports and updates maintenance jobs at the properties.',
                'permissions' => [
                    'maintenance.viewAny', 'maintenance.view', 'maintenance.create', 'maintenance.update',
                    'properties.viewAny', 'properties.view',
                ],
            ],
            'security' => [
                'label' => 'Security / Gatekeeper',
                'hint' => 'Checks visitors in and out.',
                'permissions' => ['visitors.viewAny', 'visitors.view', 'visitors.create', 'visitors.update'],
            ],
            'storekeeper' => [
                'label' => 'Storekeeper',
                'hint' => 'Stock levels and suppliers.',
                'permissions' => [
                    'inventory.viewAny', 'inventory.view', 'inventory.create', 'inventory.update',
                    'vendors.viewAny', 'vendors.view',
                ],
            ],
            'contractor' => [
                'label' => 'Contractor / Vendor',
                'hint' => 'Sees maintenance jobs and updates their progress.',
                'permissions' => ['maintenance.viewAny', 'maintenance.view', 'maintenance.update'],
            ],
        ];
    }

    /**
     * The presets, keeping only permissions that exist in this install.
     *
     * @return array<string, array{label: string, hint: string, permissions: list<string>}>
     */
    public static function available(): array
    {
        $known = collect(config('permissions'))->flatMap(fn (array $group) => array_keys($group))->all();

        return collect(self::all())
            ->map(fn (array $preset) => [...$preset, 'permissions' => array_values(array_intersect($preset['permissions'], $known))])
            ->all();
    }
}
