import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/models/models.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/status_chip.dart';
import '../data/landlord_providers.dart';
import 'landlord_lease_form_screen.dart';
import 'landlord_unit_form_screen.dart';

class LandlordPropertyDetailScreen extends ConsumerWidget {
  const LandlordPropertyDetailScreen({super.key, required this.uuid});

  final String uuid;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final property = ref.watch(landlordPropertyProvider(uuid));

    return AppScaffold(
      title: 'Property',
      actions: [
        if (hasPermission(ref, 'properties.update'))
          IconButton(
            icon: const Icon(Icons.edit_outlined),
            tooltip: 'Edit',
            onPressed: () async {
              final changed = await context.push<bool>('/landlord/properties/$uuid/edit');
              if (changed == true) ref.invalidate(landlordPropertyProvider(uuid));
            },
          ),
      ],
      body: property.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordPropertyProvider(uuid))),
        data: (property) {
          final buildings = property.buildings ?? [];
          final units = buildings.expand((b) => b.units).toList();
          final occupied = units.where((u) => u.tenantName != null).length;
          final sold = units.where((u) => u.sale != null).length;
          final canAdd = hasPermission(ref, 'units.create');
          final canEditUnits = hasPermission(ref, 'units.update');
          final canLease = hasPermission(ref, 'leases.create');
          Future<void> openUnit(String buildingUuid, UnitDetail? unit) async {
            await Navigator.of(context).push(MaterialPageRoute<bool>(
              builder: (_) => LandlordUnitFormScreen(buildingUuid: buildingUuid, unit: unit),
            ));
            // Also after "Save & add another".
            ref.invalidate(landlordPropertyProvider(uuid));
          }

          // A free unit can be rented out (or edited); others just edited.
          Future<void> onUnit(String buildingUuid, UnitDetail unit) async {
            final free = unit.status == 'vacant';
            if (free && canLease) {
              final choice = await showModalBottomSheet<String>(
                context: context,
                showDragHandle: true,
                builder: (sheet) => SafeArea(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      ListTile(leading: const Icon(Icons.key_outlined), title: Text(sheet.tr('Rent out')), onTap: () => Navigator.of(sheet).pop('lease')),
                      if (canEditUnits) ListTile(leading: const Icon(Icons.edit_outlined), title: Text(sheet.tr('Edit unit')), onTap: () => Navigator.of(sheet).pop('edit')),
                    ],
                  ),
                ),
              );
              if (choice == 'lease' && context.mounted) {
                final created = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => LandlordLeaseFormScreen(unitUuid: unit.uuid)));
                if (created == true) ref.invalidate(landlordPropertyProvider(uuid));
                return;
              }
              if (choice != 'edit') return;
            }
            if (canEditUnits) await openUnit(buildingUuid, unit);
          }

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(property.name, style: Theme.of(context).textTheme.titleMedium),
                          ),
                          StatusChip(property.status),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        [property.type, if (property.addressLine1 != null) property.addressLine1!, if (property.city != null) property.city!]
                            .join(' · '),
                        style: TextStyle(color: context.mutedText),
                      ),
                      if (units.isNotEmpty) ...[
                        const Divider(height: 24),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceAround,
                          children: [
                            _StatColumn(label: 'Buildings', value: '${buildings.length}'),
                            _StatColumn(label: 'Units', value: '${units.length}'),
                            _StatColumn(label: 'Rented', value: '$occupied'),
                            _StatColumn(label: 'Free', value: '${units.where((u) => u.tenantName == null && u.sale == null).length}'),
                            if (sold > 0) _StatColumn(label: 'Sold', value: '$sold'),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              if (canAdd)
                Align(
                  alignment: Alignment.centerLeft,
                  child: OutlinedButton.icon(
                    icon: const Icon(Icons.add_business_outlined),
                    label: Text(context.tr('Add building')),
                    onPressed: () async {
                      if (await addBuilding(context, ref, uuid)) ref.invalidate(landlordPropertyProvider(uuid));
                    },
                  ),
                ),
              const SizedBox(height: 12),
              if (buildings.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 24),
                  child: Center(child: Text(context.tr('No buildings or units on file yet.'), style: TextStyle(color: context.mutedText))),
                )
              else
                ...buildings.map((building) => _BuildingSection(
                      building: building,
                      onAddUnit: canAdd ? () => openUnit(building.uuid, null) : null,
                      onEditUnit: canEditUnits || canLease ? (unit) => onUnit(building.uuid, unit) : null,
                    )),
            ],
          );
        },
      ),
    );
  }
}

class _StatColumn extends StatelessWidget {
  const _StatColumn({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Column(
        children: [
          Text(value, style: Theme.of(context).textTheme.titleMedium),
          Text(context.tr(label), style: TextStyle(color: context.mutedText, fontSize: 12)),
        ],
      );
}

class _BuildingSection extends StatelessWidget {
  const _BuildingSection({required this.building, this.onAddUnit, this.onEditUnit});

  final PropertyBuildingDetail building;
  final VoidCallback? onAddUnit;
  final ValueChanged<UnitDetail>? onEditUnit;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text(building.name, style: Theme.of(context).textTheme.titleSmall)),
              if (onAddUnit != null) TextButton.icon(onPressed: onAddUnit, icon: const Icon(Icons.add, size: 18), label: Text(context.tr('Add unit'))),
            ],
          ),
          const SizedBox(height: 8),
          if (building.units.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(context.tr('No units in this building yet.'), style: TextStyle(color: context.mutedText)),
            )
          else
            ...building.units.map((unit) => _UnitTile(unit: unit, onTap: onEditUnit == null ? null : () => onEditUnit!(unit))),
        ],
      ),
    );
  }
}

class _UnitTile extends StatelessWidget {
  const _UnitTile({required this.unit, this.onTap});

  final UnitDetail unit;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final occupied = unit.tenantName != null;
    final sale = unit.sale;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ListTile(
              contentPadding: EdgeInsets.zero,
              onTap: onTap,
              leading: CircleAvatar(
                backgroundColor: occupied ? Colors.green.shade100 : Theme.of(context).colorScheme.surfaceContainerHighest,
                child: Icon(occupied ? Icons.person : Icons.meeting_room_outlined, color: occupied ? Colors.green.shade700 : context.mutedText),
              ),
              title: Text('${unit.unitNumber} · ${formatMoney(unit.rentAmount)}'),
              subtitle: Text(occupied ? '${unit.tenantName}${unit.tenantPhone != null ? ' · ${unit.tenantPhone}' : ''}' : context.tr('Free')),
              trailing: StatusChip(unit.status),
            ),
            if (sale != null) ...[
              const Divider(height: 1),
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: _SalePaymentSummary(sale: sale),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

/// Buyer + payment progress for a sold unit - a partial payment shows as
/// "X remaining", not just "not fully paid", so a landlord can see how
/// much of the purchase is actually outstanding.
class _SalePaymentSummary extends StatelessWidget {
  const _SalePaymentSummary({required this.sale});

  final UnitSaleSummary sale;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(Icons.sell_outlined, size: 16, color: context.mutedText),
            const SizedBox(width: 6),
            Expanded(
              child: Text(
                sale.buyerName ?? 'Buyer',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            if (sale.buyerPhone != null) Text(sale.buyerPhone!, style: TextStyle(color: context.mutedText, fontSize: 12)),
          ],
        ),
        const SizedBox(height: 6),
        ClipRRect(
          borderRadius: BorderRadius.circular(3),
          child: LinearProgressIndicator(
            value: sale.totalPrice > 0 ? (sale.totalPaid / sale.totalPrice).clamp(0, 1).toDouble() : 0,
            minHeight: 5,
            backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          sale.isFullyPaid
              ? 'Paid in full · ${formatMoney(sale.totalPrice)}'
              : '${formatMoney(sale.totalPaid)} paid of ${formatMoney(sale.totalPrice)} — ${formatMoney(sale.remainingBalance)} remaining'
                  '${sale.isPartiallyPaid ? ' (partial payment)' : ''}',
          style: TextStyle(
            fontSize: 12,
            color: sale.isFullyPaid ? Colors.green.shade700 : Colors.orange.shade800,
          ),
        ),
      ],
    );
  }
}
