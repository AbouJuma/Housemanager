import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/token_storage.dart';
import '../auth/providers/auth_provider.dart';
import '../reports/presentation/occupancy_screen.dart';
import '../reports/presentation/rent_roll_screen.dart';
import '../reports/presentation/reports_hub_screen.dart';
import 'modules/staff_modules.dart';
import 'presentation/staff_invoice_detail_screen.dart';
import 'presentation/staff_invoice_form_screen.dart';
import 'presentation/staff_invoices_screen.dart';
import 'presentation/staff_lease_detail_screen.dart';
import 'presentation/staff_lease_form_screen.dart';
import 'presentation/staff_leases_screen.dart';
import 'presentation/staff_tenant_detail_screen.dart';
import 'presentation/staff_tenant_form_screen.dart';
import 'presentation/staff_tenants_screen.dart';

/// Everything the app can offer a company's staff, each unlocked by one
/// permission. A role gets exactly the features its permissions allow -
/// an Accountant sees invoices, expenses and reports; a Caretaker or
/// Cleaner sees maintenance; Security sees visitors; a Storekeeper sees
/// inventory - with no app update when a company invents a new role.
class StaffFeature {
  const StaffFeature({
    required this.id,
    required this.label,
    required this.subtitle,
    required this.icon,
    required this.selectedIcon,
    required this.permission,
    required this.screen,
    this.routes = _noRoutes,
    this.canBeTab = true,
  });

  final String id;
  final String label;

  /// One-liner under the dashboard tile.
  final String subtitle;
  final IconData icon;
  final IconData selectedIcon;

  /// The "see the list" permission - `<module>.viewAny`.
  final String permission;

  /// The feature's root screen, given where it is mounted.
  final Widget Function(String basePath) screen;

  /// Pages beneath the root (detail, new, edit).
  final List<RouteBase> Function() routes;

  /// Whether it may take a bottom-nav slot (Reports always lives in More).
  final bool canBeTab;

  static List<RouteBase> _noRoutes() => const [];
}

/// A list + detail feature over one of [staffModules].
StaffFeature _moduleFeature(String id, String subtitle, IconData icon, IconData selectedIcon) {
  final module = staffModules[id]!;
  return StaffFeature(
    id: id,
    label: module.title,
    subtitle: subtitle,
    icon: icon,
    selectedIcon: selectedIcon,
    permission: '$id.viewAny',
    screen: (basePath) => ModuleListScreen(module: module, basePath: basePath),
    routes: () => [
      GoRoute(path: ':uuid', builder: (c, s) => ModuleDetailScreen(module: module, uuid: s.pathParameters['uuid']!)),
    ],
  );
}

/// In priority order: the first three a role may use become its tabs,
/// the rest sit under More. Tenants, leases and invoices come first, so
/// they are always tabs when allowed (their screens link to their own
/// fixed `/staff/<id>` paths).
final staffFeatures = <StaffFeature>[
  // The super admin's platform tab (companies and their admins).
  _moduleFeature('companies', 'Clients & their admins', Icons.domain_outlined, Icons.domain),
  StaffFeature(
    id: 'tenants',
    label: 'Tenants',
    subtitle: 'Profiles & contacts',
    icon: Icons.people_outline,
    selectedIcon: Icons.people,
    permission: 'tenants.viewAny',
    screen: (_) => const StaffTenantsScreen(),
    routes: () => [
      GoRoute(path: 'new', builder: (c, s) => const StaffTenantFormScreen()),
      GoRoute(
        path: ':uuid',
        builder: (c, s) => StaffTenantDetailScreen(uuid: s.pathParameters['uuid']!),
        routes: [GoRoute(path: 'edit', builder: (c, s) => StaffTenantFormScreen(uuid: s.pathParameters['uuid']))],
      ),
    ],
  ),
  StaffFeature(
    id: 'leases',
    label: 'Leases',
    subtitle: 'Active & expiring',
    icon: Icons.home_work_outlined,
    selectedIcon: Icons.home_work,
    permission: 'leases.viewAny',
    screen: (_) => const StaffLeasesScreen(),
    routes: () => [
      GoRoute(path: 'new', builder: (c, s) => const StaffLeaseFormScreen()),
      GoRoute(
        path: ':uuid',
        builder: (c, s) => StaffLeaseDetailScreen(uuid: s.pathParameters['uuid']!),
        routes: [GoRoute(path: 'edit', builder: (c, s) => StaffLeaseFormScreen(uuid: s.pathParameters['uuid']))],
      ),
    ],
  ),
  StaffFeature(
    id: 'invoices',
    label: 'Invoices',
    subtitle: 'Billing & payments',
    icon: Icons.receipt_long_outlined,
    selectedIcon: Icons.receipt_long,
    permission: 'invoices.viewAny',
    screen: (_) => const StaffInvoicesScreen(),
    routes: () => [
      GoRoute(path: 'new', builder: (c, s) => const StaffInvoiceFormScreen()),
      GoRoute(path: ':uuid', builder: (c, s) => StaffInvoiceDetailScreen(uuid: s.pathParameters['uuid']!)),
    ],
  ),
  _moduleFeature('maintenance', 'Repairs & job status', Icons.build_outlined, Icons.build),
  _moduleFeature('expenses', 'Bills & payments out', Icons.payments_outlined, Icons.payments),
  _moduleFeature('visitors', 'Gate log & check-out', Icons.badge_outlined, Icons.badge),
  _moduleFeature('inventory', 'Stock & supplies', Icons.inventory_2_outlined, Icons.inventory_2),
  _moduleFeature('properties', 'Buildings & units', Icons.apartment_outlined, Icons.apartment),
  _moduleFeature('vendors', 'Suppliers & contractors', Icons.handyman_outlined, Icons.handyman),
  _moduleFeature('documents', 'Contracts & files', Icons.folder_outlined, Icons.folder),
  StaffFeature(
    id: 'reports',
    label: 'Reports',
    subtitle: 'Rent roll & occupancy',
    icon: Icons.insights_outlined,
    selectedIcon: Icons.insights,
    permission: 'reports.view',
    canBeTab: false,
    screen: (_) => const ReportsHubScreen(),
    routes: () => [
      GoRoute(path: 'rent-roll', builder: (c, s) => const RentRollScreen()),
      GoRoute(path: 'occupancy', builder: (c, s) => const OccupancyScreen()),
    ],
  ),
];

/// How a role's features are laid out: up to three bottom-nav tabs, the
/// rest under More.
class StaffLayout {
  const StaffLayout(this.tabs, this.overflow);

  static const maxTabs = 3;

  final List<StaffFeature> tabs;
  final List<StaffFeature> overflow;

  List<StaffFeature> get all => [...tabs, ...overflow];

  bool get isEmpty => tabs.isEmpty && overflow.isEmpty;

  bool allows(String id) => all.any((f) => f.id == id);

  /// `/staff/<id>` for a tab, `/staff/more/<id>` otherwise.
  String routeOf(StaffFeature feature) =>
      tabs.contains(feature) ? '/staff/${feature.id}' : '/staff/more/${feature.id}';
}

StaffLayout staffLayoutFor(Set<String> permissions) {
  final allowed = staffFeatures.where((f) => permissions.contains(f.permission)).toList();
  final tabs = allowed.where((f) => f.canBeTab).take(StaffLayout.maxTabs).toList();
  return StaffLayout(tabs, allowed.where((f) => !tabs.contains(f)).toList());
}

/// The signed-in staff member's layout (empty for other account types).
final staffLayoutProvider = Provider<StaffLayout>((ref) {
  final permissions = ref.watch(authProvider.select(
    (s) => s.role == UserRole.staff ? (s.profile?.permissions ?? const <String>[]).join('|') : '',
  ));
  return staffLayoutFor(permissions.split('|').where((p) => p.isNotEmpty).toSet());
});
