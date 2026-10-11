@extends('layouts.portal', [
    'portalName' => 'Landlord Portal',
    'homeRoute' => 'landlord-portal.dashboard',
    'logoutRoute' => 'landlord-portal.logout',
    'guard' => 'landlord',
    'heroImage' => 'apartments',
    'navItems' => [
        ['Dashboard', 'landlord-portal.dashboard', 'landlord-portal.dashboard', 'bi-grid-1x2'],
        ['My Properties', 'landlord-portal.properties.index', 'landlord-portal.properties.*', 'bi-buildings'],
        ['Expenses', 'landlord-portal.expenses.index', 'landlord-portal.expenses.*', 'bi-cash-stack'],
        ['Maintenance', 'landlord-portal.maintenance-requests.index', 'landlord-portal.maintenance-requests.*', 'bi-tools'],
        ['Documents', 'landlord-portal.documents.index', 'landlord-portal.documents.*', 'bi-folder2-open'],
    ],
])
