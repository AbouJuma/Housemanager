@extends('layouts.portal', [
    'portalName' => 'Buyer Portal',
    'homeRoute' => 'buyer-portal.dashboard',
    'logoutRoute' => 'buyer-portal.logout',
    'guard' => 'buyer',
    'heroImage' => 'villa',
    'navItems' => [
        ['Dashboard', 'buyer-portal.dashboard', 'buyer-portal.dashboard', 'bi-grid-1x2'],
        ['My Purchases', 'buyer-portal.purchases.index', 'buyer-portal.purchases.*', 'bi-house-check'],
        ['Invoices', 'buyer-portal.invoices.index', 'buyer-portal.invoices.*', 'bi-receipt'],
    ],
])
