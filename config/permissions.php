<?php

/*
|--------------------------------------------------------------------------
| Permission Catalog
|--------------------------------------------------------------------------
|
| The single source of truth for every permission name in the system,
| grouped by the module that owns them. Spatie Permission's `permissions`
| table itself is global (not team-scoped) - only role/permission
| *assignments* are scoped to a company via the teams feature. Each
| module seeds its own group here as it's built; nothing needs to change
| in Core when a new module adds permissions.
|
*/

return [

    'users' => [
        'users.viewAny' => 'View all users in the company',
        'users.view' => 'View a single user',
        'users.create' => 'Create users',
        'users.update' => 'Update users',
        'users.delete' => 'Delete users',
    ],

    'roles' => [
        'roles.viewAny' => 'View all roles',
        'roles.view' => 'View a single role',
        'roles.create' => 'Create roles',
        'roles.update' => 'Update roles',
        'roles.delete' => 'Delete roles',
    ],

    'settings' => [
        'settings.view' => 'View company settings',
        'settings.update' => 'Update company settings',
    ],

    'audit-logs' => [
        'audit-logs.view' => 'View the company activity log',
    ],

    'integrations' => [
        'integrations.view' => 'View integration configurations',
        'integrations.update' => 'Update integration configurations',
    ],

    'properties' => [
        'properties.viewAny' => 'View all properties, buildings, and units',
        'properties.view' => 'View a single property, building, or unit',
        'properties.create' => 'Create properties, buildings, and units',
        'properties.update' => 'Update properties, buildings, and units',
        'properties.delete' => 'Delete properties, buildings, and units',
    ],

    'landlords' => [
        'landlords.viewAny' => 'View all landlords',
        'landlords.view' => 'View a single landlord, including bank details',
        'landlords.create' => 'Create landlords',
        'landlords.update' => 'Update landlords',
        'landlords.delete' => 'Delete landlords',
    ],

    'tenants' => [
        'tenants.viewAny' => 'View all tenants',
        'tenants.view' => 'View a single tenant, including identification details',
        'tenants.create' => 'Create tenants',
        'tenants.update' => 'Update tenants',
        'tenants.delete' => 'Delete tenants',
    ],

    'leases' => [
        'leases.viewAny' => 'View all leases',
        'leases.view' => 'View a single lease',
        'leases.create' => 'Create leases',
        'leases.update' => 'Update leases, including status changes and termination',
        'leases.delete' => 'Delete leases',
    ],

    'invoices' => [
        'invoices.viewAny' => 'View all invoices',
        'invoices.view' => 'View a single invoice',
        'invoices.create' => 'Create invoices',
        'invoices.update' => 'Update invoices, including sending, cancelling, and recording payments',
        'invoices.delete' => 'Delete draft or cancelled invoices',
    ],

    'accounts' => [
        'accounts.viewAny' => 'View the chart of accounts',
        'accounts.view' => 'View a single account, including its balance',
        'accounts.create' => 'Create accounts',
        'accounts.update' => 'Update accounts',
        'accounts.delete' => 'Delete accounts',
    ],

    'journal-entries' => [
        'journal-entries.viewAny' => 'View all journal entries',
        'journal-entries.view' => 'View a single journal entry',
        'journal-entries.create' => 'Post manual journal entries',
        'journal-entries.void' => 'Void a posted journal entry',
    ],

    'expenses' => [
        'expenses.viewAny' => 'View all expenses',
        'expenses.view' => 'View a single expense',
        'expenses.create' => 'Record expenses',
        'expenses.update' => 'Update expenses, including marking paid and voiding',
    ],

    'vendors' => [
        'vendors.viewAny' => 'View all vendors',
        'vendors.view' => 'View a single vendor, including bank details',
        'vendors.create' => 'Create vendors',
        'vendors.update' => 'Update vendors',
        'vendors.delete' => 'Delete vendors',
    ],

    'maintenance' => [
        'maintenance.viewAny' => 'View all maintenance requests',
        'maintenance.view' => 'View a single maintenance request',
        'maintenance.create' => 'Log maintenance requests',
        'maintenance.update' => 'Update maintenance requests, including recording their cost as an expense',
        'maintenance.delete' => 'Delete maintenance requests',
    ],

    'inventory' => [
        'inventory.viewAny' => 'View all inventory items',
        'inventory.view' => 'View a single inventory item, including its transaction history',
        'inventory.create' => 'Add inventory items',
        'inventory.update' => 'Update inventory items, including recording stock transactions',
        'inventory.delete' => 'Delete inventory items',
    ],

    'documents' => [
        'documents.viewAny' => 'View all documents',
        'documents.view' => 'View a single document, including downloading its file',
        'documents.create' => 'Upload documents',
        'documents.update' => 'Update documents, including replacing their file',
        'documents.delete' => 'Delete documents',
    ],

    'visitors' => [
        'visitors.viewAny' => 'View all visitors',
        'visitors.view' => 'View a single visitor record',
        'visitors.create' => 'Check in visitors',
        'visitors.update' => 'Update visitor records, including checking them out',
        'visitors.delete' => 'Delete visitor records',
    ],

    'reports' => [
        'reports.view' => 'View and export reports (Rent Roll, Occupancy, Income Statement, AR Aging)',
    ],

    'sales' => [
        'sales.viewAny' => 'View all sales',
        'sales.view' => 'View a single sale',
        'sales.create' => 'Create sales',
        'sales.update' => 'Update sales',
        'sales.delete' => 'Delete sales',
    ],

    'buyers' => [
        'buyers.viewAny' => 'View all buyers',
        'buyers.view' => 'View a single buyer',
        'buyers.create' => 'Create buyers',
        'buyers.update' => 'Update buyers',
        'buyers.delete' => 'Delete buyers',
    ],

];
