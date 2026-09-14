<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="app-sidebar d-none d-lg-flex flex-column p-3">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-4 text-decoration-none">
                <span class="d-flex align-items-center justify-content-center rounded-3 bg-primary text-white me-2" style="width: 32px; height: 32px;">
                    <i class="bi bi-buildings-fill"></i>
                </span>
                <span class="fs-5 fw-semibold text-body">{{ config('app.name') }}</span>
            </a>
            <nav class="nav nav-pills flex-column gap-1">
                <a href="{{ route('dashboard') }}" class="nav-link d-flex align-items-center {{ request()->routeIs('dashboard') ? 'active' : 'text-body' }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a href="{{ route('companies.index') }}"
                   class="nav-link d-flex align-items-center {{ request()->routeIs('companies.*') ? 'active' : 'text-body' }}">
                    <i class="bi bi-building me-2"></i> Companies
                </a>
                @can('viewAny', App\Modules\Subscriptions\Models\SubscriptionPlan::class)
                    <a href="{{ route('subscription-plans.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('subscription-plans.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-credit-card me-2"></i> Subscription Plans
                    </a>
                @endcan
                @unless (auth()->user()->isSaasOwner())
                    <a href="{{ route('billing.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('billing.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-receipt me-2"></i> Billing
                    </a>
                @endunless
                @can('viewAny', App\Modules\Properties\Models\Property::class)
                    <a href="{{ route('properties.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('properties.*', 'buildings.*', 'units.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-buildings me-2"></i> Properties
                    </a>
                @endcan
                @can('viewAny', App\Modules\Landlords\Models\Landlord::class)
                    <a href="{{ route('landlords.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('landlords.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-person-badge me-2"></i> Landlords
                    </a>
                @endcan
                @can('viewAny', App\Modules\Tenants\Models\Tenant::class)
                    <a href="{{ route('tenants.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('tenants.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-people me-2"></i> Tenants
                    </a>
                @endcan
                @can('viewAny', App\Modules\Leases\Models\Lease::class)
                    <a href="{{ route('leases.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('leases.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-file-earmark-text me-2"></i> Leases
                    </a>
                @endcan
                @can('sales.viewAny')
                    <a href="{{ route('sales.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('sales.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-currency-dollar me-2"></i> Sales
                    </a>
                @endcan
                @can('buyers.viewAny')
                    <a href="{{ route('buyers.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('buyers.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-person me-2"></i> Buyers
                    </a>
                @endcan
                @can('viewAny', App\Modules\Invoices\Models\Invoice::class)
                    <a href="{{ route('invoices.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('invoices.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-receipt me-2"></i> Invoices
                    </a>
                @endcan
                @can('viewAny', App\Modules\Accounting\Models\Account::class)
                    <a href="{{ route('accounts.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('accounts.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-journal-text me-2"></i> Chart of Accounts
                    </a>
                @endcan
                @can('viewAny', App\Modules\Accounting\Models\JournalEntry::class)
                    <a href="{{ route('journal-entries.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('journal-entries.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-journals me-2"></i> Journal Entries
                    </a>
                @endcan
                @can('viewAny', App\Modules\Expenses\Models\Expense::class)
                    <a href="{{ route('expenses.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('expenses.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-cash-stack me-2"></i> Expenses
                    </a>
                @endcan
                @can('viewAny', App\Modules\Vendors\Models\Vendor::class)
                    <a href="{{ route('vendors.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('vendors.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-truck me-2"></i> Vendors
                    </a>
                @endcan
                <a href="{{ route('message-templates.index') }}"
                   class="nav-link d-flex align-items-center {{ request()->routeIs('message-templates.*') ? 'active' : 'text-body' }}">
                    <i class="bi bi-envelope me-2"></i> Message Templates
                </a>
                @can('viewAny', App\Modules\Maintenance\Models\MaintenanceRequest::class)
                    <a href="{{ route('maintenance-requests.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('maintenance-requests.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-tools me-2"></i> Maintenance
                    </a>
                @endcan
                @can('viewAny', App\Modules\Inventory\Models\InventoryItem::class)
                    <a href="{{ route('inventory-items.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('inventory-items.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-box-seam me-2"></i> Inventory
                    </a>
                @endcan
                @can('viewAny', App\Modules\Documents\Models\Document::class)
                    <a href="{{ route('documents.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('documents.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-file-earmark-text me-2"></i> Documents
                    </a>
                @endcan
                @can('viewAny', App\Modules\VisitorManagement\Models\Visitor::class)
                    <a href="{{ route('visitors.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('visitors.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-person-badge me-2"></i> Visitors
                    </a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('reports.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('reports.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-bar-chart-line me-2"></i> Reports
                    </a>
                @endcan
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('users.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('users.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-people me-2"></i> Users
                    </a>
                @endcan
                @can('viewAny', Spatie\Permission\Models\Role::class)
                    <a href="{{ route('roles.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('roles.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-shield-lock me-2"></i> Roles
                    </a>
                @endcan
                @can('settings.view-company')
                    <a href="{{ route('settings.edit') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('settings.edit') ? 'active' : 'text-body' }}">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                @endcan
                @can('viewAny', App\Modules\Integrations\Models\Integration::class)
                    <a href="{{ route('integrations.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('integrations.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-plug me-2"></i> Integrations
                    </a>
                @endcan
                @can('settings.view-system')
                    <a href="{{ route('settings.system.edit') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('settings.system.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-sliders me-2"></i> System Settings
                    </a>
                @endcan
                @can('viewAny', App\Modules\AuditLogs\Models\ActivityLogEntry::class)
                    <a href="{{ route('audit-logs.index') }}"
                       class="nav-link d-flex align-items-center {{ request()->routeIs('audit-logs.*') ? 'active' : 'text-body' }}">
                        <i class="bi bi-clock-history me-2"></i> Audit Logs
                    </a>
                @endcan
            </nav>
        </aside>

        <div class="app-main d-flex flex-column">
            <header class="app-topbar d-flex align-items-center justify-content-between px-3 px-lg-4">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarOffcanvas">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-none d-lg-block">@yield('page-heading')</div>
                <div class="d-flex align-items-center gap-2">
                    @auth
                        <button class="btn btn-outline-secondary btn-sm rounded-3 d-flex align-items-center gap-2 border-0 bg-body-tertiary" type="button" data-bs-toggle="modal" data-bs-target="#commandPaletteModal" title="Search (Ctrl+K)">
                            <i class="bi bi-search"></i>
                            <span class="d-none d-md-inline text-secondary small">Search</span>
                            <kbd class="d-none d-md-inline small text-secondary bg-transparent border px-1">Ctrl K</kbd>
                        </button>
                    @endauth
                    <button class="btn btn-outline-secondary btn-sm rounded-3 border-0 bg-body-tertiary" type="button" onclick="toggleTheme()" title="Toggle theme">
                        <i class="bi bi-circle-half"></i>
                    </button>
                    @auth
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm rounded-3 border-0 bg-body-tertiary position-relative" type="button" id="notificationBellButton" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <span id="notificationUnreadBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger d-none">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px;" id="notificationDropdown">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <span class="fw-semibold small">Notifications</span>
                                    <a href="{{ route('notifications.index') }}" class="small">View all</a>
                                </div>
                                <div id="notificationList" class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;">
                                    <div class="list-group-item text-secondary small">Loading…</div>
                                </div>
                            </div>
                        </div>
                    @endauth
                    @auth
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm rounded-3 border-0 bg-body-tertiary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                {{ auth()->user()->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-box-arrow-right me-2"></i>Log Out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </header>

            <main class="flex-grow-1 p-3 p-lg-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="appSidebarOffcanvas">
        <div class="offcanvas-header">
            <span class="fs-5 fw-semibold">{{ config('app.name') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="nav nav-pills flex-column gap-1">
                <a href="{{ route('dashboard') }}" class="nav-link text-body">Dashboard</a>
                <a href="{{ route('companies.index') }}" class="nav-link {{ request()->routeIs('companies.*') ? 'active' : 'text-body' }}">Companies</a>
                @can('viewAny', App\Modules\Subscriptions\Models\SubscriptionPlan::class)
                    <a href="{{ route('subscription-plans.index') }}" class="nav-link {{ request()->routeIs('subscription-plans.*') ? 'active' : 'text-body' }}">Subscription Plans</a>
                @endcan
                @unless (auth()->user()->isSaasOwner())
                    <a href="{{ route('billing.index') }}" class="nav-link {{ request()->routeIs('billing.*') ? 'active' : 'text-body' }}">Billing</a>
                @endunless
                @can('viewAny', App\Modules\Properties\Models\Property::class)
                    <a href="{{ route('properties.index') }}" class="nav-link {{ request()->routeIs('properties.*', 'buildings.*', 'units.*') ? 'active' : 'text-body' }}">Properties</a>
                @endcan
                @can('viewAny', App\Modules\Landlords\Models\Landlord::class)
                    <a href="{{ route('landlords.index') }}" class="nav-link {{ request()->routeIs('landlords.*') ? 'active' : 'text-body' }}">Landlords</a>
                @endcan
                @can('viewAny', App\Modules\Tenants\Models\Tenant::class)
                    <a href="{{ route('tenants.index') }}" class="nav-link {{ request()->routeIs('tenants.*') ? 'active' : 'text-body' }}">Tenants</a>
                @endcan
                @can('viewAny', App\Modules\Leases\Models\Lease::class)
                    <a href="{{ route('leases.index') }}" class="nav-link {{ request()->routeIs('leases.*') ? 'active' : 'text-body' }}">Leases</a>
                @endcan
                @can('sales.viewAny')
                    <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'active' : 'text-body' }}">Sales</a>
                @endcan
                @can('buyers.viewAny')
                    <a href="{{ route('buyers.index') }}" class="nav-link {{ request()->routeIs('buyers.*') ? 'active' : 'text-body' }}">Buyers</a>
                @endcan
                @can('viewAny', App\Modules\Invoices\Models\Invoice::class)
                    <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : 'text-body' }}">Invoices</a>
                @endcan
                @can('viewAny', App\Modules\Accounting\Models\Account::class)
                    <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : 'text-body' }}">Chart of Accounts</a>
                @endcan
                @can('viewAny', App\Modules\Accounting\Models\JournalEntry::class)
                    <a href="{{ route('journal-entries.index') }}" class="nav-link {{ request()->routeIs('journal-entries.*') ? 'active' : 'text-body' }}">Journal Entries</a>
                @endcan
                @can('viewAny', App\Modules\Expenses\Models\Expense::class)
                    <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : 'text-body' }}">Expenses</a>
                @endcan
                @can('viewAny', App\Modules\Vendors\Models\Vendor::class)
                    <a href="{{ route('vendors.index') }}" class="nav-link {{ request()->routeIs('vendors.*') ? 'active' : 'text-body' }}">Vendors</a>
                @endcan
                @can('viewAny', App\Modules\Maintenance\Models\MaintenanceRequest::class)
                    <a href="{{ route('maintenance-requests.index') }}" class="nav-link {{ request()->routeIs('maintenance-requests.*') ? 'active' : 'text-body' }}">Maintenance</a>
                @endcan
                @can('viewAny', App\Modules\Inventory\Models\InventoryItem::class)
                    <a href="{{ route('inventory-items.index') }}" class="nav-link {{ request()->routeIs('inventory-items.*') ? 'active' : 'text-body' }}">Inventory</a>
                @endcan
                @can('viewAny', App\Modules\Documents\Models\Document::class)
                    <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : 'text-body' }}">Documents</a>
                @endcan
                @can('viewAny', App\Modules\VisitorManagement\Models\Visitor::class)
                    <a href="{{ route('visitors.index') }}" class="nav-link {{ request()->routeIs('visitors.*') ? 'active' : 'text-body' }}">Visitors</a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : 'text-body' }}">Reports</a>
                @endcan
                @can('viewAny', App\Models\User::class)
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : 'text-body' }}">Users</a>
                @endcan
                @can('viewAny', Spatie\Permission\Models\Role::class)
                    <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : 'text-body' }}">Roles</a>
                @endcan
                @can('settings.view-company')
                    <a href="{{ route('settings.edit') }}" class="nav-link {{ request()->routeIs('settings.edit') ? 'active' : 'text-body' }}">Settings</a>
                @endcan
                @can('viewAny', App\Modules\Integrations\Models\Integration::class)
                    <a href="{{ route('integrations.index') }}" class="nav-link {{ request()->routeIs('integrations.*') ? 'active' : 'text-body' }}">Integrations</a>
                @endcan
                @can('settings.view-system')
                    <a href="{{ route('settings.system.edit') }}" class="nav-link {{ request()->routeIs('settings.system.*') ? 'active' : 'text-body' }}">System Settings</a>
                @endcan
                @can('viewAny', App\Modules\AuditLogs\Models\ActivityLogEntry::class)
                    <a href="{{ route('audit-logs.index') }}" class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : 'text-body' }}">Audit Logs</a>
                @endcan
            </nav>
        </div>
    </div>

    @auth
        <div class="modal fade" id="commandPaletteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body p-0">
                        <div class="p-3 border-bottom">
                            <input type="text" id="commandPaletteInput" class="form-control form-control-lg border-0 shadow-none px-0" placeholder="Search companies, users…" autocomplete="off">
                        </div>
                        <div id="commandPaletteResults" class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endauth

    @stack('scripts')
</body>
</html>
