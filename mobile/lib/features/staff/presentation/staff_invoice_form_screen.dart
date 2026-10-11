import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/widgets/toast.dart';

import '../../../core/widgets/money_input.dart';

import '../../../core/models/models.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/formatters.dart';
import '../data/staff_providers.dart';

class _LineItem {
  final _descriptionController = TextEditingController();
  final _quantityController = TextEditingController(text: '1');
  final _unitPriceController = TextEditingController();

  void dispose() {
    _descriptionController.dispose();
    _quantityController.dispose();
    _unitPriceController.dispose();
  }
}

class StaffInvoiceFormScreen extends ConsumerStatefulWidget {
  const StaffInvoiceFormScreen({super.key});

  @override
  ConsumerState<StaffInvoiceFormScreen> createState() => _StaffInvoiceFormScreenState();
}

class _StaffInvoiceFormScreenState extends ConsumerState<StaffInvoiceFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _notesController = TextEditingController();
  final List<_LineItem> _items = [_LineItem()];

  Lease? _selectedLease;
  DateTime _issueDate = DateTime.now();
  DateTime? _dueDate;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _notesController.dispose();
    for (final item in _items) {
      item.dispose();
    }
    super.dispose();
  }

  Future<void> _pickDate({required bool isIssue}) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: (isIssue ? _issueDate : _dueDate) ?? DateTime.now(),
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked == null) return;
    setState(() {
      if (isIssue) {
        _issueDate = picked;
      } else {
        _dueDate = picked;
      }
    });
  }

  double get _total => _items.fold(0, (sum, item) {
        final quantity = double.tryParse(item._quantityController.text) ?? 1;
        final unitPrice = parseMoney(item._unitPriceController.text) ?? 0;
        return sum + quantity * unitPrice;
      });

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedLease == null || _dueDate == null) {
      setState(() => _error = 'Select a lease and a due date.');
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await ref.read(staffRepositoryProvider).createInvoice({
        'lease_id': _selectedLease!.uuid,
        'issue_date': _issueDate.toIso8601String().split('T').first,
        'due_date': _dueDate!.toIso8601String().split('T').first,
        if (_notesController.text.trim().isNotEmpty) 'notes': _notesController.text.trim(),
        'items': _items
            .map((item) => {
                  'description': item._descriptionController.text.trim(),
                  'quantity': double.tryParse(item._quantityController.text) ?? 1,
                  'unit_price': parseMoney(item._unitPriceController.text) ?? 0,
                })
            .toList(),
      });
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
      title: 'New Invoice',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _leasePicker(),
            const SizedBox(height: 16),
            _datePickerTile(label: 'Issue date', value: _issueDate, onTap: () => _pickDate(isIssue: true)),
            const SizedBox(height: 16),
            _datePickerTile(label: 'Due date', value: _dueDate, onTap: () => _pickDate(isIssue: false)),
            const SizedBox(height: 24),
            Text('Line items', style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 8),
            ..._items.asMap().entries.map((entry) => _lineItemCard(entry.key, entry.value)),
            TextButton.icon(
              onPressed: () => setState(() => _items.add(_LineItem())),
              icon: const Icon(Icons.add),
              label: Text(context.tr('Add line item')),
            ),
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: Text('Total: ${formatMoney(_total)}', style: Theme.of(context).textTheme.titleMedium),
            ),
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
                  : Text(context.tr('Create invoice')),
            ),
          ],
        ),
      ),
    );
  }

  Widget _leasePicker() => Autocomplete<Lease>(
        displayStringForOption: (lease) => '${lease.tenantName ?? 'Tenant'} — ${lease.unit?.label ?? 'Unit'}',
        optionsBuilder: (textEditingValue) async {
          if (textEditingValue.text.trim().isEmpty) return const Iterable.empty();
          return ref.read(staffRepositoryProvider).searchLeases(textEditingValue.text.trim());
        },
        onSelected: (lease) => setState(() => _selectedLease = lease),
        fieldViewBuilder: (context, controller, focusNode, onSubmitted) {
          if (_selectedLease != null && controller.text.isEmpty) {
            controller.text = '${_selectedLease!.tenantName ?? 'Tenant'} — ${_selectedLease!.unit?.label ?? 'Unit'}';
          }
          return TextField(
            controller: controller,
            focusNode: focusNode,
            decoration: InputDecoration(labelText: context.tr('Lease'), hintText: context.tr('Search by tenant name or unit number')),
          );
        },
      );

  Widget _datePickerTile({required String label, required DateTime? value, required VoidCallback onTap}) => InkWell(
        onTap: onTap,
        child: InputDecorator(
          decoration: InputDecoration(labelText: label),
          child: Text(value != null ? formatDate(value.toIso8601String()) : 'Select date'),
        ),
      );

  Widget _lineItemCard(int index, _LineItem item) => Card(
        margin: const EdgeInsets.only(bottom: 10),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text('Item ${index + 1}', style: const TextStyle(fontWeight: FontWeight.w600)),
                  ),
                  if (_items.length > 1)
                    IconButton(
                      icon: const Icon(Icons.delete_outline),
                      onPressed: () => setState(() => _items.removeAt(index)),
                    ),
                ],
              ),
              TextFormField(
                controller: item._descriptionController,
                decoration: InputDecoration(labelText: context.tr('Description')),
                validator: (value) => (value == null || value.isEmpty) ? 'Required' : null,
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: item._quantityController,
                      decoration: InputDecoration(labelText: context.tr('Quantity')),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      onChanged: (_) => setState(() {}),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextFormField(
                      controller: item._unitPriceController,
                      decoration: InputDecoration(labelText: context.tr('Unit price')),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      inputFormatters: [MoneyInputFormatter()],
                      validator: (value) => parseMoney(value) == null ? 'Required' : null,
                      onChanged: (_) => setState(() {}),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      );
}
