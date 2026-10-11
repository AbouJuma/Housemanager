import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/widgets/toast.dart';

import '../../../core/widgets/money_input.dart';

import '../../../core/theme/app_theme.dart';

import '../../../core/models/models.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../data/staff_providers.dart';
import 'staff_lease_detail_screen.dart';

const _billingCycles = ['monthly', 'quarterly', 'semi_annual', 'annual'];
const _leaseStatuses = ['active', 'expired', 'terminated'];

/// One screen for both create (uuid == null) and edit. The backend
/// deliberately doesn't allow changing tenant/unit on an existing lease
/// (see UpdateLeaseRequest's comment - a re-assignment means ending this
/// lease and starting a new one), so those pickers only appear when
/// creating; edit mode instead exposes status/termination fields that
/// only make sense once a lease already exists.
class StaffLeaseFormScreen extends ConsumerStatefulWidget {
  const StaffLeaseFormScreen({super.key, this.uuid});

  final String? uuid;

  @override
  ConsumerState<StaffLeaseFormScreen> createState() => _StaffLeaseFormScreenState();
}

class _StaffLeaseFormScreenState extends ConsumerState<StaffLeaseFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _rentController = TextEditingController();
  final _depositController = TextEditingController();
  final _notesController = TextEditingController();
  final _terminationReasonController = TextEditingController();

  StaffTenant? _selectedTenant;
  PropertySummary? _selectedProperty;
  BuildingSummary? _selectedBuilding;
  UnitSummary? _selectedUnit;
  DateTime? _startDate;
  DateTime? _endDate;
  String _billingCycle = 'monthly';
  String _status = 'active';

  List<PropertySummary> _properties = [];
  List<BuildingSummary> _buildings = [];
  List<UnitSummary> _units = [];

  bool _loading = false;
  bool _submitting = false;
  String? _error;

  bool get _isEditing => widget.uuid != null;

  @override
  void initState() {
    super.initState();
    if (_isEditing) {
      _loadExisting();
    } else {
      _loadProperties();
    }
  }

  @override
  void dispose() {
    _rentController.dispose();
    _depositController.dispose();
    _notesController.dispose();
    _terminationReasonController.dispose();
    super.dispose();
  }

  Future<void> _loadProperties() async {
    final properties = await ref.read(staffRepositoryProvider).properties();
    if (mounted) setState(() => _properties = properties);
  }

  Future<void> _loadExisting() async {
    setState(() => _loading = true);
    try {
      final lease = await ref.read(staffRepositoryProvider).lease(widget.uuid!);
      _rentController.text = formatMoneyInput(lease.rentAmount.toStringAsFixed(2)) ?? '';
      _depositController.text = formatMoneyInput(lease.depositAmount?.toStringAsFixed(2) ?? '') ?? '';
      _notesController.text = lease.notes ?? '';
      _terminationReasonController.text = lease.terminationReason ?? '';
      _billingCycle = lease.billingCycle;
      _status = lease.status;
      _startDate = lease.startDate != null ? DateTime.tryParse(lease.startDate!) : null;
      _endDate = lease.endDate != null ? DateTime.tryParse(lease.endDate!) : null;
      _selectedTenant = lease.tenantName != null
          ? StaffTenant(uuid: lease.tenantUuid ?? '', name: lease.tenantName!, type: '', status: '')
          : null;
      _selectedUnit = lease.unit != null
          ? UnitSummary(uuid: lease.unit!.uuid, unitNumber: lease.unit!.unitNumber, status: 'occupied')
          : null;
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _onPropertyChanged(PropertySummary? property) async {
    setState(() {
      _selectedProperty = property;
      _selectedBuilding = null;
      _selectedUnit = null;
      _buildings = [];
      _units = [];
    });
    if (property == null) return;
    final buildings = await ref.read(staffRepositoryProvider).buildings(property.uuid);
    if (mounted) setState(() => _buildings = buildings);
  }

  Future<void> _onBuildingChanged(BuildingSummary? building) async {
    setState(() {
      _selectedBuilding = building;
      _selectedUnit = null;
      _units = [];
    });
    if (building == null) return;
    final units = await ref.read(staffRepositoryProvider).units(building.uuid);
    if (mounted) setState(() => _units = units);
  }

  Future<void> _pickDate({required bool isStart}) async {
    final initial = (isStart ? _startDate : _endDate) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked == null) return;
    setState(() {
      if (isStart) {
        _startDate = picked;
      } else {
        _endDate = picked;
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_isEditing && (_selectedTenant == null || _selectedUnit == null || _startDate == null || _endDate == null)) {
      setState(() => _error = 'Select a tenant, unit, start date and end date.');
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    final data = _isEditing
        ? {
            'end_date': _endDate!.toIso8601String().split('T').first,
            'billing_cycle': _billingCycle,
            'rent_amount': parseMoney(_rentController.text) ?? 0,
            if (_depositController.text.trim().isNotEmpty) 'deposit_amount': parseMoney(_depositController.text),
            'status': _status,
            if (_terminationReasonController.text.trim().isNotEmpty)
              'termination_reason': _terminationReasonController.text.trim(),
            if (_notesController.text.trim().isNotEmpty) 'notes': _notesController.text.trim(),
          }
        : {
            'tenant_id': _selectedTenant!.uuid,
            'unit_id': _selectedUnit!.uuid,
            'start_date': _startDate!.toIso8601String().split('T').first,
            'end_date': _endDate!.toIso8601String().split('T').first,
            'billing_cycle': _billingCycle,
            'rent_amount': parseMoney(_rentController.text) ?? 0,
            if (_depositController.text.trim().isNotEmpty) 'deposit_amount': parseMoney(_depositController.text),
            if (_notesController.text.trim().isNotEmpty) 'notes': _notesController.text.trim(),
          };

    try {
      final repository = ref.read(staffRepositoryProvider);
      if (_isEditing) {
        await repository.updateLease(widget.uuid!, data);
        ref.invalidate(staffLeaseProvider(widget.uuid!));
      } else {
        await repository.createLease(data);
      }
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      // Any failure - validation (422), connection, server - as a
      // readable message, never a raw exception.
      final message = friendlyErrorMessage(e);
      setState(() => _error = message);
      if (mounted) showToast(context, message, type: ToastType.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: _isEditing ? 'Edit Lease' : 'New Lease',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (!_isEditing) ...[
                    _tenantPicker(),
                    const SizedBox(height: 16),
                    _propertyPicker(),
                    const SizedBox(height: 16),
                    if (_selectedProperty != null) _buildingPicker(),
                    if (_selectedProperty != null) const SizedBox(height: 16),
                    if (_selectedBuilding != null) _unitPicker(),
                    if (_selectedBuilding != null) const SizedBox(height: 16),
                    _datePickerTile(label: 'Start date', value: _startDate, onTap: () => _pickDate(isStart: true)),
                    const SizedBox(height: 16),
                  ] else ...[
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(_selectedUnit?.unitNumber ?? 'Unit', style: Theme.of(context).textTheme.titleSmall),
                            Text(_selectedTenant?.name ?? 'Tenant', style: TextStyle(color: context.mutedText)),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                  _datePickerTile(label: 'End date', value: _endDate, onTap: () => _pickDate(isStart: false)),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: _billingCycle,
                    decoration: InputDecoration(labelText: context.tr('Billing cycle')),
                    items: _billingCycles.map((c) => DropdownMenuItem(value: c, child: Text(c.replaceAll('_', ' ')))).toList(),
                    onChanged: (value) => setState(() => _billingCycle = value!),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _rentController,
                    decoration: InputDecoration(labelText: context.tr('Rent amount')),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    inputFormatters: [MoneyInputFormatter()],
                    validator: (value) => parseMoney(value) == null ? 'Enter a valid amount' : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _depositController,
                    decoration: InputDecoration(labelText: context.tr('Deposit amount (optional)')),
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    inputFormatters: [MoneyInputFormatter()],
                  ),
                  if (_isEditing) ...[
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      initialValue: _status,
                      decoration: InputDecoration(labelText: context.tr('Status')),
                      items: _leaseStatuses.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
                      onChanged: (value) => setState(() => _status = value!),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _terminationReasonController,
                      decoration: InputDecoration(labelText: context.tr('Termination reason (optional)')),
                    ),
                  ],
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _notesController,
                    decoration: InputDecoration(labelText: context.tr('Notes')),
                    maxLines: 3,
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 16),
                    Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
                  ],
                  const SizedBox(height: 24),
                  FilledButton(
                    onPressed: _submitting ? null : _submit,
                    child: _submitting
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                        : Text(_isEditing ? 'Save changes' : 'Create lease'),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _tenantPicker() => Autocomplete<StaffTenant>(
        displayStringForOption: (tenant) => tenant.name,
        optionsBuilder: (textEditingValue) async {
          if (textEditingValue.text.trim().isEmpty) return const Iterable.empty();
          return ref.read(staffRepositoryProvider).searchTenants(textEditingValue.text.trim());
        },
        onSelected: (tenant) => setState(() => _selectedTenant = tenant),
        fieldViewBuilder: (context, controller, focusNode, onSubmitted) {
          if (_selectedTenant != null && controller.text.isEmpty) controller.text = _selectedTenant!.name;
          return TextField(
            controller: controller,
            focusNode: focusNode,
            decoration: InputDecoration(labelText: context.tr('Tenant'), hintText: context.tr('Search by name, email or phone')),
          );
        },
      );

  Widget _propertyPicker() => DropdownButtonFormField<PropertySummary>(
        initialValue: _selectedProperty,
        decoration: InputDecoration(labelText: context.tr('Property')),
        items: _properties.map((p) => DropdownMenuItem(value: p, child: Text(p.name))).toList(),
        onChanged: _onPropertyChanged,
      );

  Widget _buildingPicker() => DropdownButtonFormField<BuildingSummary>(
        initialValue: _selectedBuilding,
        decoration: InputDecoration(labelText: context.tr('Building')),
        items: _buildings.map((b) => DropdownMenuItem(value: b, child: Text(b.name))).toList(),
        onChanged: _onBuildingChanged,
      );

  Widget _unitPicker() => DropdownButtonFormField<UnitSummary>(
        initialValue: _selectedUnit,
        decoration: InputDecoration(labelText: context.tr('Unit (vacant only)')),
        items: _units.map((u) => DropdownMenuItem(value: u, child: Text(u.unitNumber))).toList(),
        onChanged: (value) => setState(() {
          _selectedUnit = value;
          // Start from the unit's own rate - still editable.
          if (value?.rentAmount != null) {
            _rentController.text = formatMoneyInput(value!.rentAmount!.toStringAsFixed(2)) ?? '';
          }
        }),
      );

  Widget _datePickerTile({required String label, required DateTime? value, required VoidCallback onTap}) => InkWell(
        onTap: onTap,
        child: InputDecorator(
          decoration: InputDecoration(labelText: label),
          child: Text(value != null ? formatDate(value.toIso8601String()) : 'Select date'),
        ),
      );
}
