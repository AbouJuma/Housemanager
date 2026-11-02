import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../features/auth/providers/auth_provider.dart';
import 'api/token_storage.dart';

/// One thing a person can start from the gold centre button or the round
/// buttons on their home screen ("Report an issue", "Add tenant"...).
class QuickAction {
  const QuickAction(this.icon, this.label, this.route);

  final IconData icon;
  final String label;
  final String route;
}

/// What the signed-in person can start, by role and permissions - so a
/// landlord whose role can't add tenants never sees "Add tenant".
final quickActionsProvider = Provider<List<QuickAction>>((ref) {
  final auth = ref.watch(authProvider);
  final profile = auth.profile;
  bool can(String permission) => profile?.can(permission) ?? false;

  return switch (auth.role) {
    UserRole.tenant => const [
        QuickAction(LucideIcons.wrench, 'Report issue', '/tenant/maintenance/new'),
        QuickAction(LucideIcons.receiptText, 'My invoices', '/tenant/invoices'),
        QuickAction(LucideIcons.keyRound, 'My lease', '/tenant/leases'),
        QuickAction(LucideIcons.folderOpen, 'Documents', '/tenant/more/documents'),
      ],
    UserRole.landlord => [
        if (can('properties.create')) const QuickAction(LucideIcons.building2, 'Add property', '/landlord/properties/new'),
        if (can('tenants.create')) const QuickAction(LucideIcons.userPlus, 'Add tenant', '/landlord/tenants/new'),
        if (can('leases.create')) const QuickAction(LucideIcons.keyRound, 'Rent out', '/landlord/more/leases/new'),
        if (can('expenses.create')) const QuickAction(LucideIcons.banknote, 'Add expense', '/landlord/more/expenses/new'),
      ],
    UserRole.buyer => const [
        QuickAction(LucideIcons.receiptText, 'Invoices', '/buyer/invoices'),
        QuickAction(LucideIcons.house, 'Purchases', '/buyer/sales'),
      ],
    UserRole.staff => [
        if (can('tenants.create')) const QuickAction(LucideIcons.userPlus, 'New tenant', '/staff/tenants/new'),
        if (can('leases.create')) const QuickAction(LucideIcons.keyRound, 'New lease', '/staff/leases/new'),
        if (can('invoices.create')) const QuickAction(LucideIcons.receiptText, 'New invoice', '/staff/invoices/new'),
      ],
    null => const [],
  };
});
