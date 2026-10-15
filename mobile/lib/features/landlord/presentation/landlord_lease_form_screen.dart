import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/json_helpers.dart';
import '../../../core/l10n/l10n.dart';
import '../../../core/rent_periods.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/money_input.dart';
import '../../../core/widgets/toast.dart';
import '../data/landlord_providers.dart';

/// Rent out one of the landlord's free units: pick the tenant, billing
/// cycle, start date and number of periods - the rent per period (from
/// the unit's price) and the end date are worked out, and the total for
/// the whole lease is shown. Everything stays editable.
class LandlordLeaseFormScreen extends ConsumerStatefulWidget {
  const LandlordLeaseFormScreen({super.key, this.unitUuid});

  /// Unit to pre-select (from the property screen).
  final String? unitUuid;

  @override
  ConsumerState<LandlordLeaseFormScreen> createState() => _LandlordLeaseFormScreenState();
}

class _LandlordLeaseFormScreenState extends ConsumerState<LandlordLeaseFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _rent = TextEditingController();
  final _deposit = TextEditingController();
  final _periods = TextEditingController(text: '1');
  late String? _unitUuid = widget.unitUuid;
  String? _tenantUuid;
  String _cycle = 'monthly';
  DateTime? _start;
  DateTime? _end;
  bool _submitting = false;
  bool _rentFilled = false;
  Map<String, String> _errors = const {};

  @override
  void dispose() {
    for (final c in [_rent, _deposit, _periods]) {
      c.dispose();
    }
    super.dispose();
  }

  int get _periodCount => int.tryParse(_periods.text.trim()) ?? 1;

  void _applyRent(Map<String, dynamic>? unit) {
    final price = asDoubleOrNull(unit?['rent_amount']);
    if (unit == null || price == null || price <= 0) return;
    _rent.text = formatMoneyInput(RentPeriods.convert(price, unit['rent_period'] as String?, _cycle).toStringAsFixed(2)) ?? '';
  }

  void _applyEnd() {
    if (_start != null) _end = RentPeriods.endDate(_start!, _cycle, _periodCount);
  }

  Future<void> _pickDate({required bool start}) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: (start ? _start : _end) ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (picked == null) return;
    setState(() {
      if (start) {
        _start = picked;
        _applyEnd();
      } else {
        _end = picked;
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_start == null) {
      showToast(context, context.tr('Choose the start date.'), type: ToastType.error);
      return;
    }
    setState(() {
      _submitting = true;
      _errors = const {};
    });

    try {
      await ref.read(landlordRepositoryProvider).createLease({
        'unit_id': _unitUuid,
        'tenant_id': _tenantUuid,
        'billing_cycle': _cycle,
        'start_date': RentPeriods.iso(_start!),
        'end_date': _end == null ? null : RentPeriods.iso(_end!),
        'periods': _periodCount,
        'rent_amount': parseMoney(_rent.text),
        'deposit_amount': parseMoney(_deposit.text),
      });
      if (!mounted) return;
      showToast(context, context.tr('Lease created.'), type: ToastType.success);
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _errors = validationErrors(e));
      showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final options = ref.watch(landlordLeaseOptionsProvider);

    return AppScaffold(
      title: 'New lease',
      body: options.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordLeaseOptionsProvider)),
        data: (data) {
          final units = (data['units'] as List<dynamic>).cast<Map<String, dynamic>>();
          final tenants = (data['tenants'] as List<dynamic>).cast<Map<String, dynamic>>();
          final unit = units.where((u) => u['uuid'] == _unitUuid).firstOrNull;
          if (unit == null) _unitUuid = null;
          if (unit != null && !_rentFilled) {
            _applyRent(unit);
            _rentFilled = true;
          }
          String? required(Object? v) => (v == null || (v is String && v.trim().isEmpty)) ? context.tr('This field is required.') : null;
          final rent = parseMoney(_rent.text) ?? 0;
          final price = asDoubleOrNull(unit?['rent_amount']);

          if (units.isEmpty) {
            return Center(child: Padding(padding: const EdgeInsets.all(24), child: Text(context.tr('You have no free units right now.'), textAlign: TextAlign.center)));
          }

          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                DropdownButtonFormField<String>(
                  initialValue: _unitUuid,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: context.tr('Unit'), errorText: _errors['unit_id']),
                  items: [
                    for (final u in units)
                      DropdownMenuItem(
                        value: u['uuid'] as String,
                        child: Text('${u['unit_number']} · ${u['property'] ?? ''}', overflow: TextOverflow.ellipsis),
                      ),
                  ],
                  onChanged: (v) => setState(() {
                    _unitUuid = v;
                    _applyRent(units.where((u) => u['uuid'] == v).firstOrNull);
                  }),
                  validator: required,
                ),
                if (price != null && price > 0)
                  Padding(
                    padding: const EdgeInsets.only(top: 6, left: 4),
                    child: Text(
                      '${context.tr('Unit price')}: ${formatMoney(price)} / ${context.tr(RentPeriods.perLabels[unit!['rent_period']] ?? 'month')}',
                      style: TextStyle(color: context.mutedText, fontSize: 13),
                    ),
                  ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: _tenantUuid,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: context.tr('Tenant'), errorText: _errors['tenant_id']),
                  items: [for (final t in tenants) DropdownMenuItem(value: t['uuid'] as String, child: Text(t['name'] as String))],
                  onChanged: (v) => setState(() => _tenantUuid = v),
                  validator: required,
                ),
                if (tenants.isEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 6, left: 4),
                    child: Text(context.tr('Add the tenant first (Tenants tab).'), style: TextStyle(color: context.mutedText, fontSize: 13)),
                  ),
                const SizedBox(height: 16),
                Text(context.tr('Billing cycle'), style: Theme.of(context).textTheme.labelLarge),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: [
                    for (final c in RentPeriods.cycles)
                      ChoiceChip(
                        label: Text(context.tr(RentPeriods.labels[c]!)),
                        selected: _cycle == c,
                        onSelected: (_) => setState(() {
                          _cycle = c;
                          _applyRent(unit);
                          _applyEnd();
                        }),
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: InkWell(
                        onTap: () => _pickDate(start: true),
                        child: InputDecorator(
                          decoration: InputDecoration(labelText: context.tr('Start date'), errorText: _errors['start_date']),
                          child: Text(_start == null ? context.tr('Choose…') : formatDate(RentPeriods.iso(_start!))),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    SizedBox(
                      width: 110,
                      child: TextFormField(
                        controller: _periods,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(labelText: context.tr('Periods')),
                        onChanged: (_) => setState(_applyEnd),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                InkWell(
                  onTap: () => _pickDate(start: false),
                  child: InputDecorator(
                    decoration: InputDecoration(
                      labelText: context.tr('End date'),
                      helperText: context.tr('Filled in from the start date - you can change it.'),
                      errorText: _errors['end_date'],
                    ),
                    child: Text(_end == null ? '—' : formatDate(RentPeriods.iso(_end!))),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _rent,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [MoneyInputFormatter()],
                  onChanged: (_) => setState(() {}),
                  decoration: InputDecoration(
                    labelText: '${context.tr('Rent per')} ${context.tr(RentPeriods.perLabels[_cycle]!)}',
                    errorText: _errors['rent_amount'],
                  ),
                  validator: (v) => (parseMoney(v) ?? 0) <= 0 ? context.tr('This field is required.') : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _deposit,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [MoneyInputFormatter()],
                  decoration: InputDecoration(labelText: context.tr('Deposit amount (optional)')),
                ),
                if (rent > 0) ...[
                  const SizedBox(height: 16),
                  Card(
                    child: ListTile(
                      leading: const Icon(Icons.summarize_outlined),
                      title: Text(formatMoney(rent * _periodCount), style: const TextStyle(fontWeight: FontWeight.w800)),
                      subtitle: Text('${context.tr('Total for the lease')}: $_periodCount × ${formatMoney(rent)}'),
                    ),
                  ),
                ],
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: _submitting ? null : _submit,
                  child: _submitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                      : Text(context.tr('Create lease')),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
