@php
    // Menu follows the company's "Landlord" role (Settings -> Roles).
    $landlordCan = fn (string $permission) => \App\Modules\LandlordPortal\Support\LandlordAccess::can(auth('landlord')->user(), $permission);
    $landlordNav = array_values(array_filter([
        ['Dashboard', 'landlord-portal.dashboard', 'landlord-portal.dashboard', 'bi-grid-1x2'],
        $landlordCan('properties.viewAny') ? ['My Properties', 'landlord-portal.properties.index', 'landlord-portal.properties.*', 'bi-buildings'] : null,
        $landlordCan('properties.viewAny') ? ['Units', 'landlord-portal.reports.units', 'landlord-portal.reports.units', 'bi-door-open'] : null,
        $landlordCan('tenants.viewAny') ? ['My Tenants', 'landlord-portal.tenants.index', 'landlord-portal.tenants.*', 'bi-people'] : null,
        $landlordCan('buyers.viewAny') ? ['My Buyers', 'landlord-portal.buyers.index', 'landlord-portal.buyers.*', 'bi-house-check'] : null,
        $landlordCan('invoices.viewAny') ? ['Invoices', 'landlord-portal.invoices.index', 'landlord-portal.invoices.*', 'bi-receipt'] : null,
        $landlordCan('reports.view') ? ['Profit & Loss', 'landlord-portal.reports.profit-loss', 'landlord-portal.reports.profit-loss', 'bi-graph-up-arrow'] : null,
        $landlordCan('expenses.viewAny') ? ['Expenses', 'landlord-portal.expenses.index', 'landlord-portal.expenses.*', 'bi-cash-stack'] : null,
        $landlordCan('maintenance.viewAny') ? ['Maintenance', 'landlord-portal.maintenance-requests.index', 'landlord-portal.maintenance-requests.*', 'bi-tools'] : null,
        $landlordCan('documents.viewAny') ? ['Documents', 'landlord-portal.documents.index', 'landlord-portal.documents.*', 'bi-folder2-open'] : null,
    ]));
@endphp

@extends('layouts.portal', [
    'portalName' => 'Landlord Portal',
    'homeRoute' => 'landlord-portal.dashboard',
    'logoutRoute' => 'landlord-portal.logout',
    'guard' => 'landlord',
    'heroImage' => 'apartments',
    'navItems' => $landlordNav,
])
