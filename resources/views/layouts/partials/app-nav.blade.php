{{-- One nav definition for both the desktop sidebar and the mobile
     offcanvas - each entry: [label, route, icon, active patterns, visible]. --}}
@php
    $user = auth()->user();
    $sections = [
        'Overview' => [
            ['Dashboard', 'dashboard', 'bi-grid-1x2', ['dashboard'], true],
            // Only the platform owner (super admin) manages companies.
            ['Companies', 'companies.index', 'bi-building', ['companies.*'], $user->isSaasOwner()],
            ['Subscription Plans', 'subscription-plans.index', 'bi-credit-card', ['subscription-plans.*'], $user->can('viewAny', App\Modules\Subscriptions\Models\SubscriptionPlan::class)],
            ['Billing', 'billing.index', 'bi-wallet2', ['billing.*'], ! $user->isSaasOwner()],
        ],
        'Portfolio' => [
            ['Properties', 'properties.index', 'bi-buildings', ['properties.*', 'buildings.*', 'units.*'], $user->can('viewAny', App\Modules\Properties\Models\Property::class)],
            ['Landlords', 'landlords.index', 'bi-person-badge', ['landlords.*'], $user->can('viewAny', App\Modules\Landlords\Models\Landlord::class)],
            ['Tenants', 'tenants.index', 'bi-people', ['tenants.*'], $user->can('viewAny', App\Modules\Tenants\Models\Tenant::class)],
            ['Leases', 'leases.index', 'bi-file-earmark-text', ['leases.*'], $user->can('viewAny', App\Modules\Leases\Models\Lease::class)],
            ['Sales', 'sales.index', 'bi-house-check', ['sales.*'], $user->can('sales.viewAny')],
            ['Buyers', 'buyers.index', 'bi-person-check', ['buyers.*'], $user->can('buyers.viewAny')],
        ],
        'Finance' => [
            ['Invoices', 'invoices.index', 'bi-receipt', ['invoices.*'], $user->can('viewAny', App\Modules\Invoices\Models\Invoice::class)],
            ['Chart of Accounts', 'accounts.index', 'bi-journal-text', ['accounts.*'], $user->can('viewAny', App\Modules\Accounting\Models\Account::class)],
            ['Journal Entries', 'journal-entries.index', 'bi-journals', ['journal-entries.*'], $user->can('viewAny', App\Modules\Accounting\Models\JournalEntry::class)],
            ['Expenses', 'expenses.index', 'bi-cash-stack', ['expenses.*'], $user->can('viewAny', App\Modules\Expenses\Models\Expense::class)],
            ['Vendors', 'vendors.index', 'bi-truck', ['vendors.*'], $user->can('viewAny', App\Modules\Vendors\Models\Vendor::class)],
            ['Reports', 'reports.index', 'bi-bar-chart-line', ['reports.*'], $user->can('reports.view')],
        ],
        'Operations' => [
            ['Maintenance', 'maintenance-requests.index', 'bi-tools', ['maintenance-requests.*'], $user->can('viewAny', App\Modules\Maintenance\Models\MaintenanceRequest::class)],
            ['Inventory', 'inventory-items.index', 'bi-box-seam', ['inventory-items.*'], $user->can('viewAny', App\Modules\Inventory\Models\InventoryItem::class)],
            ['Documents', 'documents.index', 'bi-folder2-open', ['documents.*'], $user->can('viewAny', App\Modules\Documents\Models\Document::class)],
            ['Visitors', 'visitors.index', 'bi-person-vcard', ['visitors.*'], $user->can('viewAny', App\Modules\VisitorManagement\Models\Visitor::class)],
        ],
        'Messaging' => [
            ['Message Templates', 'message-templates.index', 'bi-envelope', ['message-templates.*'], true],
            ['Message Log', 'message-logs.index', 'bi-chat-left-text', ['message-logs.*'], $user->can('message-logs.view')],
            ['Reminder Rules', 'reminder-rules.index', 'bi-alarm', ['reminder-rules.*'], $user->can('reminder-rules.manage')],
        ],
        'Administration' => [
            ['Users', 'users.index', 'bi-people-fill', ['users.*'], $user->can('viewAny', App\Models\User::class)],
            ['Roles', 'roles.index', 'bi-shield-lock', ['roles.*'], $user->can('viewAny', Spatie\Permission\Models\Role::class)],
            ['Company Profile', 'company-profile.edit', 'bi-building-gear', ['company-profile.*'], ! $user->isSaasOwner() && $user->can('settings.view-company')],
            ['Settings', 'settings.edit', 'bi-gear', ['settings.edit'], $user->can('settings.view-company')],
            ['Branding', 'branding.edit', 'bi-palette', ['branding.*'], ! $user->isSaasOwner() && $user->can('settings.view-company')],
            ['Integrations', 'integrations.index', 'bi-plug', ['integrations.*'], $user->can('viewAny', App\Modules\Integrations\Models\Integration::class)],
            ['System Settings', 'settings.system.edit', 'bi-sliders', ['settings.system.*'], $user->can('settings.view-system')],
            ['Audit Logs', 'audit-logs.index', 'bi-clock-history', ['audit-logs.*'], $user->can('viewAny', App\Modules\AuditLogs\Models\ActivityLogEntry::class)],
        ],
    ];
@endphp

<nav class="nav flex-column gap-1">
    @foreach ($sections as $sectionLabel => $items)
        @php($visible = array_filter($items, fn ($item) => $item[4]))
        @continue(empty($visible))

        <div class="nav-section-label">{{ __($sectionLabel) }}</div>
        @foreach ($visible as [$label, $routeName, $icon, $patterns])
            @php($active = request()->routeIs(...$patterns))
            <a href="{{ route($routeName) }}" class="nav-link {{ $active ? 'active' : '' }}" @if ($active) aria-current="page" @endif>
                <i class="bi {{ $icon }}"></i> {{ __($label) }}
            </a>
        @endforeach
    @endforeach
</nav>
