import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/l10n/l10n.dart';
import '../../../core/models/models.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/money_input.dart';
import '../../../core/widgets/toast.dart';
import '../data/landlord_providers.dart';

/// Ask for a new building's name and add it to [propertyUuid]; returns
/// true when one was added.
Future<bool> addBuilding(
  BuildContext context,
  WidgetRef ref,
  String propertyUuid,
) async {
  final name = TextEditingController();
  final floors = TextEditingController();
  final formKey = GlobalKey<FormState>();

  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      title: Text(dialogContext.tr('Add building')),
      content: Form(
        key: formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextFormField(
              controller: name,
              autofocus: true,
              decoration: InputDecoration(
                labelText: dialogContext.tr('Building name'),
                hintText: dialogContext.tr('e.g. Block A'),
              ),
              validator: (v) => (v == null || v.trim().isEmpty)
                  ? dialogContext.tr('This field is required.')
                  : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: floors,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: dialogContext.tr('Floors (optional)'),
              ),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(dialogContext).pop(false),
          child: Text(dialogContext.tr('Cancel')),
        ),
        FilledButton(
          onPressed: () {
            if (formKey.currentState!.validate()) {
              Navigator.of(dialogContext).pop(true);
            }
          },
          child: Text(dialogContext.tr('Add building')),
        ),
      ],
    ),
  );
  if (confirmed != true) return false;

  try {
    await ref.read(landlordRepositoryProvider).createBuilding(propertyUuid, {
      'name': name.text.trim(),
      'floor_count': int.tryParse(floors.text.trim()),
    });
    if (context.mounted) {
      showToast(
        context,
        context.tr('Building added.'),
        type: ToastType.success,
      );
    }
    return true;
  } catch (e) {
    if (context.mounted) {
      showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    }
    return false;
  }
}

/// Add a unit to [buildingUuid] (unit == null) or edit [unit]. A rented
/// unit's availability follows its lease, so it can't be changed here.
class LandlordUnitFormScreen extends ConsumerStatefulWidget {
  const LandlordUnitFormScreen({
    super.key,
    required this.buildingUuid,
    this.unit,
  });

  final String buildingUuid;
  final UnitDetail? unit;

  @override
  ConsumerState<LandlordUnitFormScreen> createState() =>
      _LandlordUnitFormScreenState();
}

class _LandlordUnitFormScreenState
    extends ConsumerState<LandlordUnitFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _number = TextEditingController(text: widget.unit?.unitNumber);
  late final _rent = TextEditingController(
    text: widget.unit == null || widget.unit!.rentAmount == 0
        ? ''
        : formatMoneyInput(widget.unit!.rentAmount.toStringAsFixed(0)),
  );
  late final _bedrooms = TextEditingController(
    text: widget.unit?.bedrooms?.toString(),
  );
  late final _bathrooms = TextEditingController(
    text: widget.unit?.bathrooms?.toString(),
  );
  late String? _type = widget.unit?.type;
  late String _status = widget.unit?.status ?? 'vacant';
  bool _submitting = false;
  Map<String, String> _errors = const {};

  bool get _editing => widget.unit != null;
  bool get _rented => widget.unit?.status == 'occupied';

  @override
  void dispose() {
    for (final c in [_number, _rent, _bedrooms, _bathrooms]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _submit({bool addAnother = false}) async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _submitting = true;
      _errors = const {};
    });
    final data = {
      'unit_number': _number.text.trim(),
      'type': _type,
      if (!_rented) 'status': _status,
      'rent_amount': parseMoney(_rent.text),
      'bedrooms': int.tryParse(_bedrooms.text.trim()),
      'bathrooms': int.tryParse(_bathrooms.text.trim()),
    };
    final repository = ref.read(landlordRepositoryProvider);

    try {
      if (_editing) {
        await repository.updateUnit(widget.unit!.uuid, data);
      } else {
        await repository.createUnit(widget.buildingUuid, data);
      }
      if (!mounted) return;
      showToast(
        context,
        context.tr(_editing ? 'Unit updated.' : 'Unit added.'),
        type: ToastType.success,
      );
      if (addAnother) {
        setState(() {
          _number.clear();
          _submitting = false;
        });
        return;
      }
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
    final options = ref.watch(landlordPropertyOptionsProvider);
    String label(String v) => context.tr(
      v.replaceAll('_', ' ')[0].toUpperCase() +
          v.replaceAll('_', ' ').substring(1),
    );

    return AppScaffold(
      title: _editing ? 'Edit unit' : 'Add unit',
      body: options.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(
          error: error,
          onRetry: () => ref.invalidate(landlordPropertyOptionsProvider),
        ),
        data: (data) {
          final types = (data['unit_types'] as List<dynamic>).cast<String>();
          final statuses = (data['unit_statuses'] as List<dynamic>)
              .cast<String>();

          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                TextFormField(
                  controller: _number,
                  decoration: InputDecoration(
                    labelText: context.tr('Unit number'),
                    hintText: 'A1',
                    errorText: _errors['unit_number'],
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? context.tr('This field is required.')
                      : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  initialValue: types.contains(_type) ? _type : null,
                  isExpanded: true,
                  decoration: InputDecoration(
                    labelText: context.tr('Type'),
                    errorText: _errors['type'],
                  ),
                  items: [
                    for (final t in types)
                      DropdownMenuItem(value: t, child: Text(label(t))),
                  ],
                  onChanged: (v) => setState(() => _type = v),
                  validator: (v) =>
                      v == null ? context.tr('This field is required.') : null,
                ),
                const SizedBox(height: 16),
                if (_rented)
                  Card(
                    child: ListTile(
                      leading: const Icon(Icons.person),
                      title: Text(context.tr('Rented (follows its lease)')),
                    ),
                  )
                else
                  DropdownButtonFormField<String>(
                    initialValue: statuses.contains(_status)
                        ? _status
                        : statuses.first,
                    isExpanded: true,
                    decoration: InputDecoration(
                      labelText: context.tr('Availability'),
                      errorText: _errors['status'],
                    ),
                    items: [
                      for (final s in statuses)
                        DropdownMenuItem(value: s, child: Text(label(s))),
                    ],
                    onChanged: (v) => setState(() => _status = v ?? _status),
                  ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _rent,
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  inputFormatters: [MoneyInputFormatter()],
                  decoration: InputDecoration(
                    labelText: context.tr('Monthly rent'),
                    errorText: _errors['rent_amount'],
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _bedrooms,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          labelText: context.tr('Bedrooms'),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _bathrooms,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          labelText: context.tr('Bathrooms'),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: _submitting ? null : _submit,
                  child: _submitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text(
                          context.tr(_editing ? 'Save changes' : 'Add unit'),
                        ),
                ),
                if (!_editing) ...[
                  const SizedBox(height: 8),
                  OutlinedButton(
                    onPressed: _submitting
                        ? null
                        : () => _submit(addAnother: true),
                    child: Text(context.tr('Save & add another')),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
