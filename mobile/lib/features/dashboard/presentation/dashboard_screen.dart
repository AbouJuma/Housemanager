import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';
import '../../auth/data/auth_repository.dart';
import '../../notifications/notifications.dart';

import '../../../core/api/token_storage.dart';
import '../../../core/branding/branding.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/brand_widgets.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/progress_ring.dart';
import '../../auth/providers/auth_provider.dart';
import '../../buyer/data/buyer_providers.dart';
import '../../landlord/data/landlord_providers.dart';
import '../../staff/staff_features.dart';
import '../../tenant/data/tenant_providers.dart';

/// Home tab for every role. Layout: greeting + date, a photo hero card
/// with the role's headline number (ring where there's a ratio), stat
/// bars where there are amounts to compare, then a grid of solid-color
/// shortcut tiles. Tenants and staff also get a centre "+" quick action.
class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);

    // Checking/signedOut show briefly while _AuthRefresh's redirect
    // takes effect (see core/router/app_router.dart) - avoids a flash
    // of the wrong role's empty dashboard before that redirect fires.
    if (auth.status != AuthStatus.signedIn) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final role = auth.role!;

    return Scaffold(
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: switch (role) {
        UserRole.tenant => _AddButton(
            tooltip: context.tr('Report an issue'),
            onPressed: () => context.push('/tenant/maintenance/new'),
          ),
        UserRole.staff when _staffCreateOptions(auth.profile).isNotEmpty =>
          _AddButton(tooltip: context.tr('Create'), onPressed: () => _showStaffQuickCreate(context, auth.profile)),
        _ => null,
      },
      body: SafeArea(
        bottom: false,
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(tenantDashboardProvider);
            ref.invalidate(landlordDashboardProvider);
            ref.invalidate(buyerDashboardProvider);
          },
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 110),
            children: [
              _Greeting(name: auth.profile?.name),
              const SizedBox(height: 20),
              switch (role) {
                UserRole.tenant => const _TenantDashboard(),
                UserRole.landlord => const _LandlordDashboard(),
                UserRole.buyer => const _BuyerDashboard(),
                UserRole.staff => const _StaffDashboard(),
              },
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------
// Header
// ---------------------------------------------------------------------

class _Greeting extends ConsumerWidget {
  const _Greeting({this.name});

  final String? name;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final hour = DateTime.now().hour;
    final greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening');
    final parts = (name ?? '').trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    final initials = parts.isEmpty ? '?' : (parts.first[0] + (parts.length > 1 ? parts.last[0] : '')).toUpperCase();
    final scheme = Theme.of(context).colorScheme;

    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                DateFormat('EEEE, d MMM', context.languageCode).format(DateTime.now()),
                style: TextStyle(color: context.mutedText, fontSize: 13, fontWeight: FontWeight.w500),
              ),
              const SizedBox(height: 2),
              Text(
                parts.isEmpty ? context.tr(greeting) : context.tr('$greeting, :name', {'name': parts.first}),
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800, letterSpacing: -0.5),
              ),
            ],
          ),
        ),
        const NotificationBell(),
        const SizedBox(width: 4),
        PopupMenuButton<String>(
          tooltip: context.tr('Account'),
          position: PopupMenuPosition.under,
          onSelected: (value) {
            if (value == 'logout') ref.read(authProvider.notifier).logout();
          },
          itemBuilder: (context) => [
            PopupMenuItem(value: 'logout', child: ListTile(leading: const Icon(Icons.logout), title: Text(context.tr('Sign out')))),
          ],
          child: CircleAvatar(
            radius: 22,
            backgroundColor: scheme.primary,
            foregroundColor: scheme.onPrimary,
            child: Text(initials, style: const TextStyle(fontWeight: FontWeight.w700)),
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------
// Hero card - brand gradient over a property photo, with corner bubbles
// ---------------------------------------------------------------------

class _HeroCard extends ConsumerWidget {
  const _HeroCard({required this.image, required this.child});

  final String image;
  final Widget child;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final primary = ref.watch(brandingProvider).primary;

    return ClipRRect(
      borderRadius: BorderRadius.circular(28),
      child: Stack(
        children: [
          Positioned.fill(child: Image.asset('assets/images/$image.jpg', fit: BoxFit.cover)),
          Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    Color.lerp(primary, AppColors.ink, 0.35)!.withValues(alpha: 0.96),
                    primary.withValues(alpha: 0.86),
                  ],
                ),
              ),
            ),
          ),
          Positioned(right: -40, bottom: -60, child: _bubble(170, 0.10)),
          Positioned(left: -30, top: -50, child: _bubble(120, 0.07)),
          Padding(padding: const EdgeInsets.all(22), child: child),
        ],
      ),
    );
  }

  Widget _bubble(double size, double opacity) => Container(
        width: size,
        height: size,
        decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: opacity)),
      );
}

class _HeroText extends StatelessWidget {
  const _HeroText({required this.eyebrow, required this.value, this.pill});

  final String eyebrow;
  final String value;
  final String? pill;

  @override
  Widget build(BuildContext context) {
    final accent = Theme.of(context).colorScheme.secondary;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(width: 16, height: 3, decoration: BoxDecoration(color: accent, borderRadius: BorderRadius.circular(2))),
            const SizedBox(width: 8),
            Flexible(
              child: Text(
                context.tr(eyebrow).toUpperCase(),
                style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1.2),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        FittedBox(
          fit: BoxFit.scaleDown,
          alignment: Alignment.centerLeft,
          child: Text(value, style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -0.8)),
        ),
        if (pill != null) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(999),
              border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
            ),
            child: Text(context.tr(pill!), style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
          ),
        ],
      ],
    );
  }
}

class _HeroRing extends StatelessWidget {
  const _HeroRing({required this.value});

  final double value;

  @override
  Widget build(BuildContext context) => ProgressRing(
        value: value,
        size: 104,
        strokeWidth: 11,
        color: Theme.of(context).colorScheme.secondary,
        trackColor: Colors.white.withValues(alpha: 0.16),
        center: Text(
          '${(value * 100).round()}%',
          style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800),
        ),
      );
}

// ---------------------------------------------------------------------
// Stat bars and shortcut tiles
// ---------------------------------------------------------------------

class _StatBars extends StatelessWidget {
  const _StatBars({required this.rows});

  /// (label, amount, color)
  final List<(String, double, Color)> rows;

  @override
  Widget build(BuildContext context) {
    final max = rows.map((r) => r.$2.abs()).fold<double>(0, (a, b) => a > b ? a : b);

    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 8),
        child: Column(
          children: [
            for (final (label, amount, color) in rows)
              Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: Column(
                  children: [
                    Row(
                      children: [
                        Text(context.tr(label), style: TextStyle(color: context.mutedText, fontWeight: FontWeight.w600, fontSize: 13)),
                        const Spacer(),
                        Text(formatMoney(amount), style: const TextStyle(fontWeight: FontWeight.w700)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(999),
                      child: LinearProgressIndicator(
                        value: max == 0 ? 0 : amount.abs() / max,
                        minHeight: 8,
                        color: color,
                        backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Solid-color shortcut tiles, two per row.
class _TileGrid extends StatelessWidget {
  const _TileGrid({required this.tiles});

  final List<_Tile> tiles;

  // Coral, violet, teal, orange - the same four-color rhythm as the
  // reference designs, readable with white text.
  static const colors = [Color(0xFFE5534B), Color(0xFF6D3FD1), Color(0xFF13A89E), Color(0xFFF07F2D)];

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 14,
      crossAxisSpacing: 14,
      childAspectRatio: 1.08,
      children: [
        for (var i = 0; i < tiles.length; i++) _TileCard(tile: tiles[i], color: colors[i % colors.length]),
      ],
    );
  }
}

class _Tile {
  const _Tile(this.icon, this.label, this.subtitle, this.route);

  final IconData icon;
  final String label;
  final String subtitle;
  final String route;
}

class _TileCard extends StatelessWidget {
  const _TileCard({required this.tile, required this.color});

  final _Tile tile;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color,
      borderRadius: BorderRadius.circular(22),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        // go(), not push() - these routes are each their own bottom-nav
        // tab/branch root, so this switches tabs rather than stacking a
        // duplicate screen on top of Dashboard within its own tab.
        onTap: () => context.go(tile.route),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(13)),
                child: Icon(tile.icon, color: Colors.white),
              ),
              const Spacer(),
              Text(context.tr(tile.label), style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
              const SizedBox(height: 2),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      context.tr(tile.subtitle),
                      style: TextStyle(color: Colors.white.withValues(alpha: 0.82), fontSize: 12),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Container(
                    width: 22,
                    height: 22,
                    decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: Colors.white.withValues(alpha: 0.7), width: 1.5)),
                    child: const Icon(Icons.arrow_forward, size: 13, color: Colors.white),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(top: 24, bottom: 12),
        child: Text(context.tr(text), style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
      );
}

class _AddButton extends StatelessWidget {
  const _AddButton({required this.tooltip, required this.onPressed});

  final String tooltip;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) => FloatingActionButton(
        tooltip: tooltip,
        onPressed: onPressed,
        shape: const CircleBorder(),
        elevation: 6,
        child: const Icon(Icons.add, size: 30),
      );
}

/// The reference "Add task / Add group / Add project" sheet, for staff.
/// What this staff member's role lets them create from the + sheet.
List<(IconData, String, String, String, int)> _staffCreateOptions(AuthenticatedProfile? profile) => [
      if (profile?.can('tenants.create') ?? false)
        (Icons.person_add_alt_1, 'New tenant', 'Add a tenant and their contact details.', '/staff/tenants/new', 2),
      if (profile?.can('leases.create') ?? false)
        (Icons.description_outlined, 'New lease', 'Rent a vacant unit to a tenant.', '/staff/leases/new', 1),
      if (profile?.can('invoices.create') ?? false)
        (Icons.receipt_long, 'New invoice', 'Bill a tenant for rent or other charges.', '/staff/invoices/new', 4),
    ];

void _showStaffQuickCreate(BuildContext context, AuthenticatedProfile? profile) {
  final options = _staffCreateOptions(profile);

  showModalBottomSheet<void>(
    context: context,
    showDragHandle: true,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
    builder: (sheetContext) => SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            for (final (icon, title, subtitle, route, color) in options)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Card(
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    leading: IconTile(icon, paletteIndex: color, size: 44),
                    title: Text(sheetContext.tr(title), style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text(sheetContext.tr(subtitle), style: TextStyle(color: sheetContext.mutedText)),
                    onTap: () {
                      Navigator.of(sheetContext).pop();
                      context.push(route);
                    },
                  ),
                ),
              ),
          ],
        ),
      ),
    ),
  );
}

Widget _asyncError(Object error, VoidCallback onRetry) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 24),
      child: ErrorView(error: error, onRetry: onRetry),
    );

const _loading = Padding(padding: EdgeInsets.symmetric(vertical: 60), child: Center(child: CircularProgressIndicator()));

// ---------------------------------------------------------------------
// Per-role dashboards
// ---------------------------------------------------------------------

class _TenantDashboard extends ConsumerWidget {
  const _TenantDashboard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(tenantDashboardProvider);

    return dashboard.when(
      loading: () => _loading,
      error: (error, _) => _asyncError(error, () => ref.invalidate(tenantDashboardProvider)),
      data: (data) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _HeroCard(
            image: 'interior',
            child: Row(
              children: [
                Expanded(
                  child: _HeroText(
                    eyebrow: 'Outstanding balance',
                    value: formatMoney(data.outstandingBalance),
                    pill: data.outstandingInvoiceCount == 0
                        ? 'All paid up'
                        : context.tr(data.outstandingInvoiceCount == 1 ? ':count unpaid invoice' : ':count unpaid invoices', {'count': data.outstandingInvoiceCount}),
                  ),
                ),
                Container(
                  width: 64,
                  height: 64,
                  decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: 0.14)),
                  child: Icon(
                    data.outstandingInvoiceCount == 0 ? Icons.verified_outlined : Icons.account_balance_wallet_outlined,
                    color: Colors.white,
                    size: 30,
                  ),
                ),
              ],
            ),
          ),
          const _SectionTitle('Quick access'),
          const _TileGrid(tiles: [
            _Tile(Icons.home_work_outlined, 'My Leases', 'Your home', '/tenant/leases'),
            _Tile(Icons.receipt_long_outlined, 'Invoices', 'Bills & receipts', '/tenant/invoices'),
            _Tile(Icons.build_outlined, 'Maintenance', 'Report & track', '/tenant/maintenance'),
            _Tile(Icons.folder_open_outlined, 'Documents', 'Your files', '/tenant/more/documents'),
          ]),
        ],
      ),
    );
  }
}

class _LandlordDashboard extends ConsumerWidget {
  const _LandlordDashboard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(landlordDashboardProvider);

    return dashboard.when(
      loading: () => _loading,
      error: (error, _) => _asyncError(error, () => ref.invalidate(landlordDashboardProvider)),
      data: (data) {
        final total = data.unitCounts['total'] ?? 0;
        final occupied = data.unitCounts['occupied'] ?? 0;
        final vacant = data.unitCounts['vacant'] ?? 0;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _HeroCard(
              image: 'apartments',
              child: Row(
                children: [
                  Expanded(
                    child: _HeroText(
                      eyebrow: 'Occupancy',
                      value: context.tr(':occupied of :total units', {'occupied': occupied, 'total': total}),
                      pill: context.tr(':count vacant', {'count': vacant}),
                    ),
                  ),
                  const SizedBox(width: 12),
                  _HeroRing(value: total == 0 ? 0 : occupied / total),
                ],
              ),
            ),
            const _SectionTitle('This period'),
            _StatBars(rows: [
              ('Rent collected', data.totalCollected, const Color(0xFF13A89E)),
              ('Expenses', data.totalExpenses, const Color(0xFFE5534B)),
              ('Net', data.netAmount, Theme.of(context).colorScheme.primary),
            ]),
            const _SectionTitle('Quick access'),
            const _TileGrid(tiles: [
              _Tile(Icons.apartment_outlined, 'Properties', 'Units & tenants', '/landlord/properties'),
              _Tile(Icons.trending_down, 'Expenses', 'Where it went', '/landlord/expenses'),
              _Tile(Icons.build_outlined, 'Maintenance', 'Open requests', '/landlord/maintenance'),
              _Tile(Icons.folder_open_outlined, 'Documents', 'Contracts & files', '/landlord/more/documents'),
            ]),
          ],
        );
      },
    );
  }
}

class _BuyerDashboard extends ConsumerWidget {
  const _BuyerDashboard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(buyerDashboardProvider);

    return dashboard.when(
      loading: () => _loading,
      error: (error, _) => _asyncError(error, () => ref.invalidate(buyerDashboardProvider)),
      data: (data) {
        final owed = data.totalSpent + data.outstandingBalance;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _HeroCard(
              image: 'land',
              child: Row(
                children: [
                  Expanded(
                    child: _HeroText(
                      eyebrow: 'Paid so far',
                      value: formatMoney(data.totalSpent),
                      pill: context.tr(':amount remaining', {'amount': formatMoney(data.outstandingBalance)}),
                    ),
                  ),
                  const SizedBox(width: 12),
                  _HeroRing(value: owed == 0 ? 0 : data.totalSpent / owed),
                ],
              ),
            ),
            const _SectionTitle('Quick access'),
            _TileGrid(tiles: [
              _Tile(Icons.home_work_outlined, 'My Purchases', context.tr(data.totalPurchases == 1 ? ':count property' : ':count properties', {'count': data.totalPurchases}), '/buyer/sales'),
              _Tile(Icons.receipt_long_outlined, 'Invoices', context.tr(':count unpaid', {'count': data.outstandingInvoiceCount}), '/buyer/invoices'),
            ]),
          ],
        );
      },
    );
  }
}

class _StaffDashboard extends ConsumerWidget {
  const _StaffDashboard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final companyName = ref.watch(brandingProvider).companyName;
    final profile = ref.watch(authProvider).profile;
    final layout = ref.watch(staffLayoutProvider);

    // Tiles for what this role can open - any role, including custom ones.
    final tiles = [
      for (final feature in layout.all) _Tile(feature.icon, feature.label, feature.subtitle, layout.routeOf(feature)),
    ];
    final canCreate = _staffCreateOptions(profile).isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _HeroCard(
          image: 'office',
          child: Row(
            children: [
              Expanded(
                child: _HeroText(
                  eyebrow: 'Back office',
                  value: companyName ?? 'Your workspace',
                  pill: canCreate ? 'Tap + to create a record' : null,
                ),
              ),
              const SizedBox(width: 12),
              const BrandLogo(size: 64),
            ],
          ),
        ),
        if (tiles.isNotEmpty) ...[
          const _SectionTitle('Manage'),
          _TileGrid(tiles: tiles),
        ] else
          Padding(
            padding: const EdgeInsets.only(top: 24),
            child: Card(
              child: ListTile(
                leading: const Icon(Icons.info_outline),
                title: Text(context.tr('Your role has no app features yet.')),
                subtitle: Text(context.tr('Ask an administrator to give your role the permissions it needs.')),
              ),
            ),
          ),
      ],
    );
  }
}
