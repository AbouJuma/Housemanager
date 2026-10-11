@extends('layouts.portal', [
    'portalName' => 'Tenant Portal',
    'homeRoute' => 'portal.dashboard',
    'logoutRoute' => 'portal.logout',
    'guard' => 'tenant',
    'heroImage' => 'interior',
    'navItems' => [
        ['Dashboard', 'portal.dashboard', 'portal.dashboard', 'bi-grid-1x2'],
        ['My Leases', 'portal.leases.index', 'portal.leases.*', 'bi-file-earmark-text'],
        ['My Invoices', 'portal.invoices.index', 'portal.invoices.*', 'bi-receipt'],
        ['Maintenance', 'portal.maintenance-requests.index', 'portal.maintenance-requests.*', 'bi-tools'],
        ['Documents', 'portal.documents.index', 'portal.documents.*', 'bi-folder2-open'],
    ],
])
