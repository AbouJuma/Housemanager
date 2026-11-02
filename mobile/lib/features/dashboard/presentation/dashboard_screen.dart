import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';

import '../../../core/api/json_helpers.dart';
import '../../../core/api/token_storage.dart';
import '../../../core/branding/branding.dart';
import '../../../core/l10n/l10n.dart';
import '../../../core/quick_actions.dart';
import '../../../core/router/app_shell.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/motion.dart';
import '../../auth/providers/auth_provider.dart';
import '../../buyer/data/buyer_providers.dart';
import '../../landlord/data/landlord_providers.dart';
import '../../notifications/notifications.dart';
import '../../staff/staff_features.dart';
import '../../tenant/data/tenant_providers.dart';

/// Hide / show money on the home header (the eye button).
class HideAmounts extends Notifier<bool> {
  @override
  bool build() => false;

  void toggle() => state = !state;
}

final hideAmountsProvider = NotifierProvider<HideAmounts, bool>(HideAmounts.new);

/// Home tab for every role: a brand-gradient header with the role's key
/// figure (counting up), round quick actions, small stat cards, then the
/// role's features as tiles. Everything eases in on open.
class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);

    // Checking/signedOut show briefly while the router redirects.
    if (auth.status != AuthStatus.signedIn) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(tenantDashboardProvider);
          ref.invalidate(landlordDashboardProvider);
          ref.invalidate(buyerDashboardProvider);
        },
        child: ListView(
          padding: const EdgeInsets.only(bottom: 24),
          children: [
            switch (auth.role!) {
              UserRole.tenant => const _TenantDashboard(),
              UserRole.landlord => const _LandlordDashboard(),
              UserRole.buyer => const _BuyerDashboard(),
              UserRole.staff => const _StaffDashboard(),
            },
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------
// Header (gradient panel)
// ---------------------------------------------------------------------

class _Header extends ConsumerWidget {
  const _Header({required this.label, required this.child, this.amount, this.pill, this.pillGood = true});

  final String label;

  /// The big figure (counts up); null when [child] carries the headline.
  final double? amount;
  final Widget? child;
  final String? pill;
  final bool pillGood;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branding = ref.watch(brandingProvider);
    final profile = ref.watch(authProvider).profile;
    final hidden = ref.watch(hideAmountsProvider);
    final actions = ref.watch(quickActionsProvider);
    final top = MediaQuery.of(context).padding.top;

    return Container(
      padding: EdgeInsets.fromLTRB(20, top + 14, 20, 24),
      decoration: BoxDecoration(
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(36)),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color.lerp(branding.primary, AppColors.ink, 0.25)!,
            branding.primary,
            Color.lerp(branding.primary, branding.accent, 0.55)!,
          ],
        ),
      ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          // Soft light blobs for depth.
          Positioned(right: -60, top: -40, child: _glow(180, Colors.white.withValues(alpha: 0.08))),
          Positioned(left: -50, bottom: 40, child: _glow(140, AppColors.gold.withValues(alpha: 0.10))),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _TopRow(name: profile?.name),
              const SizedBox(height: 22),
              Row(
                children: [
                  Text(context.tr(label), style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 13, fontWeight: FontWeight.w500)),
                  if (amount != null) ...[
                    const SizedBox(width: 6),
                    GestureDetector(
                      onTap: () => ref.read(hideAmountsProvider.notifier).toggle(),
                      child: Icon(hidden ? LucideIcons.eyeOff : LucideIcons.eye, size: 16, color: Colors.white.withValues(alpha: 0.8)),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 6),
              if (amount != null) _CountUp(value: amount!, hidden: hidden) else ?child,
              if (pill != null) ...[
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: pillGood ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(pill!, style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: pillGood ? const Color(0xFF166534) : const Color(0xFF92400E))),
                ),
              ],
              if (actions.isNotEmpty) ...[
                const SizedBox(height: 22),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    for (final (i, action) in actions.take(4).indexed)
                      Expanded(
                        child: RoundAction(action: action, onDark: true, onTap: () => context.push(action.route))
                            .motion((a) => a.fadeIn(delay: (120 + 70 * i).ms, duration: 300.ms).moveY(begin: 12, end: 0)),
                      ),
                  ],
                ),
              ],
            ],
          ),
        ],
      ),
    ).motion((a) => a.fadeIn(duration: 350.ms));
  }

  Widget _glow(double size, Color color) => IgnorePointer(
        child: Container(width: size, height: size, decoration: BoxDecoration(shape: BoxShape.circle, color: color)),
      );
}

class _TopRow extends ConsumerWidget {
  const _TopRow({this.name});

  final String? name;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final hour = DateTime.now().hour;
    final greeting = hour < 12 ? 'Good morning' : (hour < 17 ? 'Good afternoon' : 'Good evening');
    final parts = (name ?? '').trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    final initials = parts.isEmpty ? '?' : (parts.first[0] + (parts.length > 1 ? parts.last[0] : '')).toUpperCase();

    return Row(
      children: [
        PopupMenuButton<String>(
          tooltip: context.tr('Account'),
          position: PopupMenuPosition.under,
          onSelected: (value) {
            if (value == 'logout') ref.read(authProvider.notifier).logout();
          },
          itemBuilder: (context) => [
            PopupMenuItem(value: 'logout', child: ListTile(leading: const Icon(LucideIcons.logOut), title: Text(context.tr('Sign out')))),
          ],
          child: Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              gradient: const LinearGradient(colors: [Color(0xFFF2C14E), AppColors.goldDeep]),
              border: Border.all(color: Colors.white.withValues(alpha: 0.6), width: 2),
            ),
            alignment: Alignment.center,
            child: Text(initials, style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF1A1405))),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                DateFormat('EEEE, d MMM', context.languageCode).format(DateTime.now()),
                style: TextStyle(color: Colors.white.withValues(alpha: 0.75), fontSize: 12, fontWeight: FontWeight.w500),
              ),
              Text(
                parts.isEmpty ? context.tr(greeting) : context.tr('$greeting, :name', {'name': parts.first}),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -0.3),
              ),
            ],
          ),
        ),
        Container(
          decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.white.withValues(alpha: 0.16)),
          child: Theme(
            data: Theme.of(context).copyWith(iconTheme: const IconThemeData(color: Colors.white)),
            child: const IconTheme(data: IconThemeData(color: Colors.white), child: NotificationBell()),
          ),
        ),
      ],
    );
  }
}

/// The headline amount: counts up on open, cents a little lighter.
class _CountUp extends StatelessWidget {
  const _CountUp({required this.value, required this.hidden});

  final double value;
  final bool hidden;

  @override
  Widget build(BuildContext context) {
    if (hidden) {
      return const Text('••••••', style: TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.w800, letterSpacing: 2));
    }

    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: value),
      duration: const Duration(milliseconds: 900),
      curve: Curves.easeOutCubic,
      builder: (context, v, _) {
        final text = formatMoney(v);
        final dot = text.lastIndexOf('.');
        final whole = dot < 0 ? text : text.substring(0, dot);
        final cents = dot < 0 ? '' : text.substring(dot);

        return FittedBox(
          fit: BoxFit.scaleDown,
          alignment: Alignment.centerLeft,
          child: Text.rich(
            TextSpan(children: [
              TextSpan(text: whole),
              TextSpan(text: cents, style: TextStyle(color: Colors.white.withValues(alpha: 0.55))),
            ]),
            style: const TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.w800, letterSpacing: -1.2),
          ),
        );
      },
    );
  }
}

// ---------------------------------------------------------------------
// Stat cards, chart, tiles
// ---------------------------------------------------------------------

class _Stat {
  const _Stat(this.icon, this.label, this.value, this.color);

  final IconData icon;
  final String label;
  final String value;
  final Color color;
}

class _StatRow extends StatelessWidget {
  const _StatRow(this.stats);

  final List<_Stat> stats;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 0),
      child: Row(
        children: [
          for (final (i, stat) in stats.indexed) ...[
            if (i > 0) const SizedBox(width: 10),
            Expanded(
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 34,
                        height: 34,
                        decoration: BoxDecoration(color: stat.color.withValues(alpha: 0.14), borderRadius: BorderRadius.circular(11)),
                        child: Icon(stat.icon, size: 18, color: stat.color),
                      ),
                      const SizedBox(height: 10),
                      Text(context.tr(stat.label), maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: context.mutedText, fontSize: 11.5)),
                      const SizedBox(height: 2),
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        alignment: Alignment.centerLeft,
                        child: Text(stat.value, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                      ),
                    ],
                  ),
                ),
              ).motion((a) => a.fadeIn(delay: (200 + 80 * i).ms, duration: 350.ms).moveY(begin: 16, end: 0, curve: Curves.easeOutCubic)),
            ),
          ],
        ],
      ),
    );
  }
}

/// Landlord: income by month this year - bars grow on open.
class _IncomeChart extends ConsumerWidget {
  const _IncomeChart();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final report = ref.watch(landlordProfitLossProvider(('this_year', 'month')));
    final data = report.value;
    if (data == null) return const SizedBox.shrink();

    final rows = (data['breakdown'] as List<dynamic>).cast<Map<String, dynamic>>();
    final values = rows.map((r) => asDouble(r['income'])).toList();
    final peak = values.fold<double>(0, (a, b) => a > b ? a : b);
    final scheme = Theme.of(context).colorScheme;
    final lastIndex = values.lastIndexWhere((v) => v > 0);

    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 0),
      child: Card(
        child: InkWell(
          borderRadius: BorderRadius.circular(22),
          onTap: () => context.go('/landlord/reports/profit-loss'),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(18, 16, 18, 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(child: Text(context.tr('Monthly income'), style: Theme.of(context).textTheme.titleMedium)),
                    Text(formatMoney(asDouble((data['income'] as Map)['total'])), style: TextStyle(fontWeight: FontWeight.w800, color: scheme.primary)),
                  ],
                ),
                const SizedBox(height: 16),
                SizedBox(
                  height: 110,
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      for (final (i, v) in values.indexed)
                        Expanded(
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 3),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.end,
                              children: [
                                TweenAnimationBuilder<double>(
                                  tween: Tween(begin: 0, end: peak == 0 ? 0 : v / peak),
                                  duration: Duration(milliseconds: 500 + 40 * i),
                                  curve: Curves.easeOutCubic,
                                  builder: (context, f, _) => Container(
                                    height: 6 + 80 * f,
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(8),
                                      gradient: LinearGradient(
                                        begin: Alignment.bottomCenter,
                                        end: Alignment.topCenter,
                                        colors: i == lastIndex
                                            ? const [AppColors.goldDeep, Color(0xFFF2C14E)]
                                            : [scheme.primary.withValues(alpha: 0.35), scheme.primary],
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  (rows[i]['label'] as String? ?? '').split(' ').first,
                                  maxLines: 1,
                                  style: TextStyle(fontSize: 9.5, color: context.mutedText),
                                ),
                              ],
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ).motion((a) => a.fadeIn(delay: 300.ms, duration: 400.ms)),
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

/// The first four features as large cards, any others as small round
/// shortcuts - so a role with many features still looks calm.
class _TileGrid extends StatelessWidget {
  const _TileGrid({required this.tiles});

  final List<_Tile> tiles;

  static const mainCount = 4;

  @override
  Widget build(BuildContext context) {
    final main = tiles.take(mainCount).toList();
    final more = tiles.skip(mainCount).toList();

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            padding: EdgeInsets.zero,
            physics: const NeverScrollableScrollPhysics(),
            mainAxisSpacing: 12,
            crossAxisSpacing: 12,
            childAspectRatio: 1.32,
            children: [
              for (final (i, tile) in main.indexed)
                _TileCard(tile: tile, highlight: i == 0)
                    .motion((a) => a.fadeIn(delay: (250 + 70 * i).ms, duration: 350.ms).scale(begin: const Offset(0.94, 0.94), curve: Curves.easeOutBack)),
            ],
          ),
          if (more.isNotEmpty) ...[
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 6),
                child: Wrap(
                  alignment: WrapAlignment.spaceAround,
                  runSpacing: 14,
                  children: [
                    for (final tile in more)
                      SizedBox(
                        width: 78,
                        child: RoundAction(action: QuickAction(tile.icon, tile.label, tile.route), onTap: () => context.go(tile.route)),
                      ),
                  ],
                ),
              ),
            ).motion((a) => a.fadeIn(delay: 450.ms, duration: 350.ms)),
          ],
        ],
      ),
    );
  }
}

class _TileCard extends StatelessWidget {
  const _TileCard({required this.tile, this.highlight = false});

  final _Tile tile;

  /// The first tile is the brand-gradient one; the rest are plain cards.
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final onCard = highlight ? Colors.white : scheme.onSurface;

    return _Pressable(
      onTap: () => context.go(tile.route),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          color: highlight ? null : scheme.surface,
          gradient: highlight
              ? LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [scheme.primary, Color.lerp(scheme.primary, scheme.secondary, 0.6)!])
              : null,
          border: highlight || !context.isDark ? null : Border.all(color: AppColors.darkBorder),
          boxShadow: context.isDark ? null : [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 16, offset: const Offset(0, 6))],
        ),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: highlight ? Colors.white.withValues(alpha: 0.18) : scheme.primary.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: Icon(tile.icon, size: 20, color: highlight ? Colors.white : scheme.primary),
                ),
                const Spacer(),
                Icon(LucideIcons.arrowUpRight, size: 18, color: onCard.withValues(alpha: 0.55)),
              ],
            ),
            const Spacer(),
            Text(context.tr(tile.label), maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(color: onCard, fontSize: 15, fontWeight: FontWeight.w800)),
            const SizedBox(height: 2),
            Text(
              context.tr(tile.subtitle),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(color: highlight ? Colors.white.withValues(alpha: 0.8) : context.mutedText, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }
}

/// Shrinks a little while pressed - a tactile feel for tiles.
class _Pressable extends StatefulWidget {
  const _Pressable({required this.child, required this.onTap});

  final Widget child;
  final VoidCallback onTap;

  @override
  State<_Pressable> createState() => _PressableState();
}

class _PressableState extends State<_Pressable> {
  bool _down = false;

  @override
  Widget build(BuildContext context) => GestureDetector(
        onTapDown: (_) => setState(() => _down = true),
        onTapCancel: () => setState(() => _down = false),
        onTapUp: (_) => setState(() => _down = false),
        onTap: widget.onTap,
        child: AnimatedScale(scale: _down ? 0.96 : 1, duration: const Duration(milliseconds: 120), child: widget.child),
      );
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.text);

  final String text;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(20, 24, 20, 12),
        child: Text(context.tr(text), style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
      );
}

Widget _asyncError(Object error, VoidCallback onRetry) => Padding(
      padding: const EdgeInsets.fromLTRB(20, 60, 20, 24),
      child: ErrorView(error: error, onRetry: onRetry),
    );

Widget get _loading => const Padding(padding: EdgeInsets.symmetric(vertical: 120), child: Center(child: CircularProgressIndicator()));

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
      data: (data) {
        final lease = data.activeLeases.isEmpty ? null : data.activeLeases.first;
        final paidUp = data.outstandingInvoiceCount == 0;

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _Header(
              label: 'Outstanding balance',
              amount: data.outstandingBalance,
              pillGood: paidUp,
              pill: paidUp
                  ? context.tr('All paid up')
                  : context.tr(data.outstandingInvoiceCount == 1 ? ':count unpaid invoice' : ':count unpaid invoices', {'count': data.outstandingInvoiceCount}),
              child: null,
            ),
            _StatRow([
              _Stat(LucideIcons.house, 'Rent', lease == null ? '—' : formatMoney(lease.rentAmount), Theme.of(context).colorScheme.primary),
              _Stat(LucideIcons.receiptText, 'Unpaid', '${data.outstandingInvoiceCount}', AppColors.goldDeep),
              _Stat(LucideIcons.calendarDays, 'Lease ends', lease?.endDate == null ? '—' : formatDate(lease!.endDate), const Color(0xFF0F766E)),
            ]),
            const _SectionTitle('Quick access'),
            const _TileGrid(tiles: [
              _Tile(LucideIcons.keyRound, 'My Leases', 'Your home', '/tenant/leases'),
              _Tile(LucideIcons.receiptText, 'Invoices', 'Bills & receipts', '/tenant/invoices'),
              _Tile(LucideIcons.wrench, 'Maintenance', 'Report & track', '/tenant/maintenance'),
              _Tile(LucideIcons.folderOpen, 'Documents', 'Your files', '/tenant/more/documents'),
            ]),
          ],
        );
      },
    );
  }
}

class _LandlordDashboard extends ConsumerWidget {
  const _LandlordDashboard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboard = ref.watch(landlordDashboardProvider);
    final profile = ref.watch(authProvider).profile;

    return dashboard.when(
      loading: () => _loading,
      error: (error, _) => _asyncError(error, () => ref.invalidate(landlordDashboardProvider)),
      data: (data) {
        final total = data.unitCounts['total'] ?? 0;
        final occupied = data.unitCounts['occupied'] ?? 0;
        final vacant = data.unitCounts['vacant'] ?? 0;
        bool can(String permission) => profile?.can(permission) ?? false;

        // Only what the company's Landlord role allows.
        final tiles = [
          if (can('properties.viewAny')) const _Tile(LucideIcons.building2, 'Properties', 'Units & tenants', '/landlord/properties'),
          if (can('tenants.viewAny')) const _Tile(LucideIcons.users, 'Tenants', 'Who rents from you', '/landlord/tenants'),
          if (can('invoices.viewAny')) const _Tile(LucideIcons.receiptText, 'Invoices', 'Who still owes', '/landlord/more/invoices'),
          if (can('reports.view')) const _Tile(LucideIcons.chartLine, 'Profit & Loss', 'Income vs expenses', '/landlord/reports/profit-loss'),
          if (can('properties.viewAny')) const _Tile(LucideIcons.doorOpen, 'Units', 'Rented & free', '/landlord/reports/units'),
          if (can('buyers.viewAny')) const _Tile(LucideIcons.handCoins, 'Buyers', 'Unit purchases', '/landlord/more/buyers'),
          if (can('expenses.viewAny')) const _Tile(LucideIcons.banknote, 'Expenses', 'Where it went', '/landlord/more/expenses'),
          if (can('maintenance.viewAny')) const _Tile(LucideIcons.wrench, 'Maintenance', 'Open requests', '/landlord/more/maintenance'),
          if (can('documents.viewAny')) const _Tile(LucideIcons.folderOpen, 'Documents', 'Contracts & files', '/landlord/more/documents'),
        ];

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _Header(
              label: 'Net income',
              amount: data.netAmount,
              pillGood: vacant == 0,
              pill: context.tr(':occupied of :total units', {'occupied': occupied, 'total': total}) +
                  (vacant > 0 ? ' · ${context.tr(':count vacant', {'count': vacant})}' : ''),
              child: null,
            ),
            _StatRow([
              _Stat(LucideIcons.trendingUp, 'Rent collected', formatMoney(data.totalCollected), const Color(0xFF16A34A)),
              _Stat(LucideIcons.trendingDown, 'Expenses', formatMoney(data.totalExpenses), const Color(0xFFDC2626)),
              _Stat(LucideIcons.doorOpen, 'Occupancy', total == 0 ? '—' : '${(occupied / total * 100).round()}%', AppColors.goldDeep),
            ]),
            if (can('reports.view')) const _IncomeChart(),
            if (tiles.isNotEmpty) ...[
              const _SectionTitle('Quick access'),
              _TileGrid(tiles: tiles),
            ],
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
            _Header(
              label: 'Paid so far',
              amount: data.totalSpent,
              pillGood: data.outstandingBalance <= 0,
              pill: context.tr(':amount remaining', {'amount': formatMoney(data.outstandingBalance)}),
              child: null,
            ),
            _StatRow([
              _Stat(LucideIcons.house, 'Properties', '${data.totalPurchases}', Theme.of(context).colorScheme.primary),
              _Stat(LucideIcons.receiptText, 'Unpaid', '${data.outstandingInvoiceCount}', AppColors.goldDeep),
              _Stat(LucideIcons.circleCheck, 'Progress', owed == 0 ? '—' : '${(data.totalSpent / owed * 100).round()}%', const Color(0xFF16A34A)),
            ]),
            const _SectionTitle('Quick access'),
            _TileGrid(tiles: [
              _Tile(LucideIcons.building, 'My Purchases', context.tr(data.totalPurchases == 1 ? ':count property' : ':count properties', {'count': data.totalPurchases}), '/buyer/sales'),
              _Tile(LucideIcons.receiptText, 'Invoices', context.tr(':count unpaid', {'count': data.outstandingInvoiceCount}), '/buyer/invoices'),
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
    final layout = ref.watch(staffLayoutProvider);

    // Tiles for what this role can open - any role, including custom ones.
    final tiles = [
      for (final feature in layout.all) _Tile(feature.icon, feature.label, feature.subtitle, layout.routeOf(feature)),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _Header(
          label: 'Back office',
          child: Text(
            companyName ?? context.tr('Your workspace'),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -0.8),
          ),
        ),
        if (tiles.isNotEmpty) ...[
          const _SectionTitle('Manage'),
          _TileGrid(tiles: tiles),
        ] else
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
            child: Card(
              child: ListTile(
                leading: const Icon(LucideIcons.info),
                title: Text(context.tr('Your role has no app features yet.')),
                subtitle: Text(context.tr('Ask an administrator to give your role the permissions it needs.')),
              ),
            ),
          ),
      ],
    );
  }
}
