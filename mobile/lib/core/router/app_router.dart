import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../features/notifications/notifications.dart';

import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/providers/auth_provider.dart';
import '../api/token_storage.dart';
import '../../features/buyer/presentation/buyer_invoice_detail_screen.dart';
import '../../features/buyer/presentation/buyer_invoices_screen.dart';
import '../../features/buyer/presentation/buyer_sale_detail_screen.dart';
import '../../features/buyer/presentation/buyer_sales_screen.dart';
import '../../features/dashboard/presentation/dashboard_screen.dart';
import '../../features/documents/presentation/documents_screen.dart';
import '../../features/landlord/presentation/landlord_expenses_screen.dart';
import '../../features/landlord/presentation/landlord_maintenance_screen.dart';
import '../../features/landlord/presentation/landlord_property_detail_screen.dart';
import '../../features/landlord/presentation/landlord_properties_screen.dart';
import '../../features/landlord/presentation/landlord_property_form_screen.dart';
import '../../features/landlord/presentation/landlord_reports_screen.dart';
import '../../features/landlord/presentation/landlord_tenants_screen.dart';
import '../../features/more/presentation/more_screen.dart';
import '../../features/more/presentation/profile_screen.dart';
import '../../features/tenant/presentation/tenant_invoice_detail_screen.dart';
import '../../features/tenant/presentation/tenant_invoices_screen.dart';
import '../../features/tenant/presentation/tenant_lease_detail_screen.dart';
import '../../features/tenant/presentation/tenant_leases_screen.dart';
import '../../features/tenant/presentation/tenant_maintenance_detail_screen.dart';
import '../../features/tenant/presentation/tenant_maintenance_screen.dart';
import '../../features/tenant/presentation/tenant_new_maintenance_request_screen.dart';
import '../../features/staff/staff_features.dart';
import 'app_shell.dart';

/// Bridges Riverpod's authProvider (which GoRouter can't `watch`
/// directly) to GoRouter's own `refreshListenable`, so a login/logout
/// re-evaluates `redirect` without manually calling context.go()
/// everywhere a session change happens.
class _AuthRefresh extends ChangeNotifier {
  _AuthRefresh(Ref ref) {
    ref.listen(authProvider, (_, _) => notifyListeners());
  }
}

/// Rebuilds the whole router whenever the signed-in role changes (login,
/// logout, or switching accounts) - each role has a genuinely different
/// set of bottom-nav tabs/branches, not just different data behind the
/// same tabs, so recreating the router (one of the two standard
/// go_router patterns for auth, alongside redirect-only) is simpler
/// here than trying to force one universal shell to conditionally
/// show/hide branches.
final routerProvider = Provider<GoRouter>((ref) {
  // Watching only (signed-in-ness, role) rather than the whole
  // AuthState is deliberate: AuthState carries a transient `error`
  // message too (see login_screen.dart's _submit, which reads that
  // error back out to display it), and a failed-login attempt sets
  // that error while status *stays* signedOut. If this provider
  // rebuilt on that change, it would hand MaterialApp.router a brand
  // new GoRouter (fresh LoginScreen instance, initialLocation
  // '/login') before the old LoginScreen's setState could ever render
  // the error - the screen would just silently "reset" instead of
  // showing what went wrong. Selecting only what actually changes the
  // route *set* avoids rebuilding for an error-only update.
  final signedInRole = ref.watch(authProvider.select((s) => s.status == AuthStatus.signedIn ? s.role : null));
  // Staff tabs follow the person's permissions, so any role a company
  // creates (Accountant, Caretaker…) gets exactly the screens it may use.
  final staffPermissions = ref.watch(authProvider.select(
    (s) => s.role == UserRole.staff || s.role == UserRole.landlord ? (s.profile?.permissions ?? const <String>[]).join('|') : '',
  ));
  final refresh = _AuthRefresh(ref);

  if (signedInRole == null) {
    return GoRouter(
      initialLocation: '/login',
      refreshListenable: refresh,
      redirect: (context, state) {
        final au = ref.read(authProvider);
        if (au.status == AuthStatus.signedIn) return '/dashboard';
        return state.matchedLocation == '/login' ? null : '/login';
      },
      // An unknown address never shows go_router's raw error page.
      onException: (context, state, router) => router.go('/login'),
      routes: [GoRoute(path: '/login', builder: (context, state) => const LoginScreen())],
    );
  }

  return GoRouter(
    initialLocation: '/dashboard',
    refreshListenable: refresh,
    redirect: (context, state) {
      final au = ref.read(authProvider);
      if (au.status != AuthStatus.signedIn) return '/login';
      if (state.matchedLocation == '/' || state.matchedLocation == '/login') return '/dashboard';
      return null;
    },
    // An unknown address (stale deep link, old route) goes home instead
    // of showing go_router's raw "no routes for location" page.
    onException: (context, state, router) => router.go('/dashboard'),
    routes: [
      _shellForRole(signedInRole, staffPermissions.split('|').toSet()),
      GoRoute(path: '/notifications', builder: (c, s) => const NotificationsScreen()),
    ],
  );
});

StatefulShellRoute _shellForRole(UserRole role, Set<String> permissions) {
  final staff = staffLayoutFor(permissions);
  final landlord = LandlordLayout(permissions);

  final branches = switch (role) {
    UserRole.tenant => _tenantBranches(),
    UserRole.landlord => _landlordBranches(landlord),
    UserRole.buyer => _buyerBranches(),
    UserRole.staff => _staffBranches(staff),
  };

  final destinations = switch (role) {
    UserRole.tenant => const [
        NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
        NavigationDestination(icon: Icon(Icons.home_work_outlined), selectedIcon: Icon(Icons.home_work), label: 'Leases'),
        NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'Invoices'),
        NavigationDestination(icon: Icon(Icons.build_outlined), selectedIcon: Icon(Icons.build), label: 'Maintenance'),
        NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More'),
      ],
    // Landlords: what the company's Landlord role allows.
    UserRole.landlord => [
        const NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
        if (landlord.properties)
          const NavigationDestination(icon: Icon(Icons.apartment_outlined), selectedIcon: Icon(Icons.apartment), label: 'Properties'),
        if (landlord.tenants)
          const NavigationDestination(icon: Icon(Icons.people_outline), selectedIcon: Icon(Icons.people), label: 'Tenants'),
        if (landlord.reports)
          const NavigationDestination(icon: Icon(Icons.insights_outlined), selectedIcon: Icon(Icons.insights), label: 'Reports'),
        const NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More'),
      ],
    UserRole.buyer => const [
        NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
        NavigationDestination(icon: Icon(Icons.home_work_outlined), selectedIcon: Icon(Icons.home_work), label: 'Purchases'),
        NavigationDestination(icon: Icon(Icons.receipt_long_outlined), selectedIcon: Icon(Icons.receipt_long), label: 'Invoices'),
        NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More'),
      ],
    // Home and More always; between them the role's top features.
    UserRole.staff => [
        const NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
        for (final feature in staff.tabs)
          NavigationDestination(icon: Icon(feature.icon), selectedIcon: Icon(feature.selectedIcon), label: feature.label),
        const NavigationDestination(icon: Icon(Icons.more_horiz), label: 'More'),
      ],
  };

  return StatefulShellRoute.indexedStack(
    builder: (context, state, navigationShell) => AppShell(navigationShell: navigationShell, destinations: destinations),
    branches: branches,
  );
}

List<StatefulShellBranch> _tenantBranches() => [
      StatefulShellBranch(routes: [GoRoute(path: '/dashboard', builder: (c, s) => const DashboardScreen())]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/tenant/leases',
          builder: (c, s) => const TenantLeasesScreen(),
          routes: [
            GoRoute(path: ':uuid', builder: (c, s) => TenantLeaseDetailScreen(uuid: s.pathParameters['uuid']!)),
          ],
        ),
      ]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/tenant/invoices',
          builder: (c, s) => const TenantInvoicesScreen(),
          routes: [
            GoRoute(path: ':uuid', builder: (c, s) => TenantInvoiceDetailScreen(uuid: s.pathParameters['uuid']!)),
          ],
        ),
      ]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/tenant/maintenance',
          builder: (c, s) => const TenantMaintenanceScreen(),
          routes: [
            GoRoute(path: 'new', builder: (c, s) => const TenantNewMaintenanceRequestScreen()),
            GoRoute(path: ':uuid', builder: (c, s) => TenantMaintenanceDetailScreen(uuid: s.pathParameters['uuid']!)),
          ],
        ),
      ]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/tenant/more',
          builder: (c, s) => const MoreScreen(documentsRoute: '/tenant/more/documents'),
          routes: [
            GoRoute(path: 'documents', builder: (c, s) => const DocumentsScreen(role: 'tenant')),
            GoRoute(path: 'profile', builder: (c, s) => const ProfileScreen()),
          ],
        ),
      ]),
    ];

/// Which landlord features the company's "Landlord" role allows
/// (Settings -> Roles on the web).
class LandlordLayout {
  LandlordLayout(Set<String> permissions)
      : properties = permissions.contains('properties.viewAny'),
        tenants = permissions.contains('tenants.viewAny'),
        reports = permissions.contains('reports.view') || permissions.contains('properties.viewAny'),
        expenses = permissions.contains('expenses.viewAny'),
        maintenance = permissions.contains('maintenance.viewAny'),
        documents = permissions.contains('documents.viewAny');

  final bool properties;
  final bool tenants;
  final bool reports;
  final bool expenses;
  final bool maintenance;
  final bool documents;
}

List<StatefulShellBranch> _landlordBranches(LandlordLayout can) => [
      StatefulShellBranch(routes: [GoRoute(path: '/dashboard', builder: (c, s) => const DashboardScreen())]),
      if (can.properties)
        StatefulShellBranch(routes: [
          GoRoute(
            path: '/landlord/properties',
            builder: (c, s) => const LandlordPropertiesScreen(),
            routes: [
              GoRoute(path: 'new', builder: (c, s) => const LandlordPropertyFormScreen()),
              GoRoute(
                path: ':uuid',
                builder: (c, s) => LandlordPropertyDetailScreen(uuid: s.pathParameters['uuid']!),
                routes: [GoRoute(path: 'edit', builder: (c, s) => LandlordPropertyFormScreen(uuid: s.pathParameters['uuid']))],
              ),
            ],
          ),
        ]),
      if (can.tenants)
        StatefulShellBranch(routes: [
          GoRoute(
            path: '/landlord/tenants',
            builder: (c, s) => const LandlordTenantsScreen(),
            routes: [
              GoRoute(path: 'new', builder: (c, s) => const LandlordTenantFormScreen()),
              GoRoute(path: ':uuid', builder: (c, s) => LandlordTenantDetailScreen(uuid: s.pathParameters['uuid']!)),
            ],
          ),
        ]),
      if (can.reports)
        StatefulShellBranch(routes: [
          GoRoute(
            path: '/landlord/reports',
            builder: (c, s) => const LandlordReportsScreen(),
            routes: [
              GoRoute(path: 'profit-loss', builder: (c, s) => const LandlordProfitLossScreen()),
              GoRoute(path: 'units', builder: (c, s) => const LandlordUnitsScreen()),
            ],
          ),
        ]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/landlord/more',
          builder: (c, s) => MoreScreen(
            documentsRoute: can.documents ? '/landlord/more/documents' : null,
            extraLinks: [
              if (can.expenses) const MoreScreenLink(icon: Icons.receipt_outlined, label: 'Expenses', route: '/landlord/more/expenses'),
              if (can.maintenance) const MoreScreenLink(icon: Icons.build_outlined, label: 'Maintenance', route: '/landlord/more/maintenance'),
            ],
          ),
          routes: [
            if (can.documents) GoRoute(path: 'documents', builder: (c, s) => const DocumentsScreen(role: 'landlord')),
            if (can.expenses) GoRoute(path: 'expenses', builder: (c, s) => const LandlordExpensesScreen()),
            if (can.maintenance) GoRoute(path: 'maintenance', builder: (c, s) => const LandlordMaintenanceScreen()),
            GoRoute(path: 'profile', builder: (c, s) => const ProfileScreen()),
          ],
        ),
      ]),
    ];

List<StatefulShellBranch> _buyerBranches() => [
      StatefulShellBranch(routes: [GoRoute(path: '/dashboard', builder: (c, s) => const DashboardScreen())]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/buyer/sales',
          builder: (c, s) => const BuyerSalesScreen(),
          routes: [
            GoRoute(path: ':uuid', builder: (c, s) => BuyerSaleDetailScreen(uuid: s.pathParameters['uuid']!)),
          ],
        ),
      ]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/buyer/invoices',
          builder: (c, s) => const BuyerInvoicesScreen(),
          routes: [
            GoRoute(path: ':uuid', builder: (c, s) => BuyerInvoiceDetailScreen(uuid: s.pathParameters['uuid']!)),
          ],
        ),
      ]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/buyer/more',
          builder: (c, s) => const MoreScreen(),
          routes: [GoRoute(path: 'profile', builder: (c, s) => const ProfileScreen())],
        ),
      ]),
    ];

List<StatefulShellBranch> _staffBranches(StaffLayout staff) => [
      StatefulShellBranch(routes: [GoRoute(path: '/dashboard', builder: (c, s) => const DashboardScreen())]),
      for (final feature in staff.tabs)
        StatefulShellBranch(routes: [
          GoRoute(
            path: '/staff/${feature.id}',
            builder: (c, s) => feature.screen('/staff/${feature.id}'),
            routes: feature.routes(),
          ),
        ]),
      StatefulShellBranch(routes: [
        GoRoute(
          path: '/staff/more',
          builder: (c, s) => MoreScreen(
            extraLinks: [
              for (final feature in staff.overflow)
                MoreScreenLink(icon: feature.icon, label: feature.label, route: staff.routeOf(feature)),
            ],
          ),
          routes: [
            GoRoute(path: 'profile', builder: (c, s) => const ProfileScreen()),
            for (final feature in staff.overflow)
              GoRoute(
                path: feature.id,
                builder: (c, s) => feature.screen('/staff/more/${feature.id}'),
                routes: feature.routes(),
              ),
          ],
        ),
      ]),
    ];
