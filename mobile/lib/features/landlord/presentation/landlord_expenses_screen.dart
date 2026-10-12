import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/json_helpers.dart';
import '../../../core/l10n/l10n.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/formatters.dart';
import '../../../core/widgets/money_input.dart';
import '../../../core/widgets/paginated_list_view.dart';
import '../../../core/widgets/status_chip.dart';
import '../../../core/widgets/toast.dart';
import '../../auth/providers/auth_provider.dart';
import '../data/landlord_providers.dart';

/// Expenses on the landlord's properties - filter by today / week /
/// month / year or any dates, see the total, add and edit.
class LandlordExpensesScreen extends ConsumerStatefulWidget {
  const LandlordExpensesScreen({super.key});

  @override
  ConsumerState<LandlordExpensesScreen> createState() => _LandlordExpensesScreenState();
}

class _LandlordExpensesScreenState extends ConsumerState<LandlordExpensesScreen> {
  int _refreshToken = 0;

  Future<void> _openForm([Map<String, dynamic>? expense]) async {
    final saved = await Navigator.of(context).push<bool>(
      MaterialPageRoute(builder: (_) => LandlordExpenseFormScreen(expense: expense)),
    );
    if (saved == true && mounted) setState(() => _refreshToken++);
  }

  @override
  Widget build(BuildContext context) {
    final repository = ref.watch(landlordRepositoryProvider);
    final canEdit = hasPermission(ref, 'expenses.update');

    return AppScaffold(
      title: 'Expenses',
      floatingActionButton: !hasPermission(ref, 'expenses.create')
          ? null
          : FloatingActionButton(tooltip: context.tr('Add expense'), onPressed: _openForm, child: const Icon(Icons.add)),
      body: Column(
        children: [
          ValueListenableBuilder<Map<String, dynamic>?>(
            valueListenable: repository.expenseSummary,
            builder: (context, summary, _) => summary == null
                ? const SizedBox.shrink()
                : Padding(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
                    child: Row(
                      children: [
                        Expanded(child: _Total(label: 'Total spent', value: asDouble(summary['total']))),
                        const SizedBox(width: 10),
                        Expanded(child: _Total(label: 'Still unpaid', value: asDouble(summary['unpaid']), alert: true)),
                      ],
                    ),
                  ),
          ),
          Expanded(
            child: PaginatedListView<Map<String, dynamic>>(
              key: ValueKey(_refreshToken),
              fetchPage: repository.expenseRowsPage,
              statusOptions: const ['unpaid', 'paid'],
              emptyLabel: 'No expenses yet.',
              itemBuilder: (context, expense) {
                final voided = expense['status'] == 'voided';
                return Card(
                  child: ListTile(
                    onTap: canEdit && !voided ? () => _openForm(expense) : null,
                    title: Text(expense['description'] as String? ?? '—',
                        style: TextStyle(decoration: voided ? TextDecoration.lineThrough : null)),
                    subtitle: Text(
                      [
                        (expense['property'] as Map?)?['name'],
                        formatDate(expense['expense_date'] as String?),
                        expense['vendor_display_name'],
                      ].whereType<String>().join(' · '),
                      style: TextStyle(color: context.mutedText),
                    ),
                    trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(formatMoney(asDouble(expense['amount'])), style: const TextStyle(fontWeight: FontWeight.w700)),
                        const SizedBox(height: 4),
                        StatusChip(voided ? 'voided' : (expense['payment_status'] as String? ?? '')),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _Total extends StatelessWidget {
  const _Total({required this.label, required this.value, this.alert = false});

  final String label;
  final double value;
  final bool alert;

  @override
  Widget build(BuildContext context) => Card(
        margin: EdgeInsets.zero,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(context.tr(label), style: TextStyle(color: context.mutedText, fontSize: 12)),
              const SizedBox(height: 2),
              Text(formatMoney(value),
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: alert && value > 0 ? StatusChip.colorFor(context, 'overdue') : null)),
            ],
          ),
        ),
      );
}

/// Add an expense, or edit one ([expense] = the list row). Once saved,
/// only the details can change - amount, date and payment are already
/// in the books (the same rule staff have).
class LandlordExpenseFormScreen extends ConsumerStatefulWidget {
  const LandlordExpenseFormScreen({super.key, this.expense});

  final Map<String, dynamic>? expense;

  @override
  ConsumerState<LandlordExpenseFormScreen> createState() => _LandlordExpenseFormScreenState();
}

class _LandlordExpenseFormScreenState extends ConsumerState<LandlordExpenseFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _description = TextEditingController(text: widget.expense?['description'] as String?);
  late final _vendor = TextEditingController(text: widget.expense?['vendor_name'] as String?);
  late final _reference = TextEditingController(text: widget.expense?['reference'] as String?);
  late final _notes = TextEditingController(text: widget.expense?['notes'] as String?);
  final _amount = TextEditingController();
  int? _propertyId;
  int? _categoryId;
  bool _paid = false;
  String _method = 'cash';
  DateTime _date = DateTime.now();
  bool _submitting = false;
  Map<String, String> _errors = const {};

  bool get _editing => widget.expense != null;

  @override
  void dispose() {
    for (final c in [_description, _vendor, _reference, _notes, _amount]) {
      c.dispose();
    }
    super.dispose();
  }

  String _iso(DateTime d) => '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _submitting = true;
      _errors = const {};
    });
    String? text(TextEditingController c) => c.text.trim().isEmpty ? null : c.text.trim();
    final repository = ref.read(landlordRepositoryProvider);

    try {
      if (_editing) {
        await repository.updateExpense(widget.expense!['uuid'] as String, {
          'property_id': _propertyId,
          'description': _description.text.trim(),
          'vendor_name': text(_vendor),
          'reference': text(_reference),
          'notes': text(_notes),
        });
      } else {
        await repository.createExpense({
          'property_id': _propertyId,
          'account_id': _categoryId,
          'description': _description.text.trim(),
          'amount': parseMoney(_amount.text),
          'expense_date': _iso(_date),
          'payment_status': _paid ? 'paid' : 'unpaid',
          if (_paid) 'payment_method': _method,
          if (_paid) 'paid_on': _iso(_date),
          'vendor_name': text(_vendor),
          'reference': text(_reference),
          'notes': text(_notes),
        });
      }
      if (!mounted) return;
      showToast(context, context.tr(_editing ? 'Expense updated.' : 'Expense recorded.'), type: ToastType.success);
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
    final options = ref.watch(landlordExpenseOptionsProvider);

    return AppScaffold(
      title: _editing ? 'Edit expense' : 'Add expense',
      body: options.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordExpenseOptionsProvider)),
        data: (data) {
          final properties = (data['properties'] as List<dynamic>).cast<Map<String, dynamic>>();
          final categories = (data['categories'] as List<dynamic>).cast<Map<String, dynamic>>();
          final methods = (data['payment_methods'] as List<dynamic>).cast<String>();
          // Pre-select the expense's own property when editing.
          _propertyId ??= properties
              .where((p) => p['uuid'] == (widget.expense?['property'] as Map?)?['uuid'])
              .map((p) => asInt(p['id']))
              .firstOrNull ?? (properties.length == 1 ? asInt(properties.first['id']) : null);
          String? required(Object? v) => (v == null || (v is String && v.trim().isEmpty)) ? context.tr('This field is required.') : null;
          String label(String v) => context.tr(v.replaceAll('_', ' ')[0].toUpperCase() + v.replaceAll('_', ' ').substring(1));

          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                DropdownButtonFormField<int>(
                  initialValue: _propertyId,
                  isExpanded: true,
                  decoration: InputDecoration(labelText: context.tr('Property'), errorText: _errors['property_id']),
                  items: [for (final p in properties) DropdownMenuItem(value: asInt(p['id']), child: Text(p['name'] as String))],
                  onChanged: (v) => setState(() => _propertyId = v),
                  validator: required,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _description,
                  decoration: InputDecoration(labelText: context.tr('Description'), hintText: context.tr('e.g. Plumbing repair'), errorText: _errors['description']),
                  validator: required,
                ),
                const SizedBox(height: 16),
                if (_editing)
                  Card(
                    child: ListTile(
                      leading: const Icon(Icons.lock_outline),
                      title: Text('${formatMoney(asDouble(widget.expense!['amount']))} · ${formatDate(widget.expense!['expense_date'] as String?)}'),
                      subtitle: Text(context.tr('Amount, date and payment are already in the books and can\'t be changed here - ask the office to void it if it is wrong.')),
                    ),
                  )
                else ...[
                  TextFormField(
                    controller: _amount,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    inputFormatters: [MoneyInputFormatter()],
                    decoration: InputDecoration(labelText: context.tr('Amount'), errorText: _errors['amount']),
                    validator: (v) => (parseMoney(v ?? '') ?? 0) <= 0 ? context.tr('This field is required.') : null,
                  ),
                  const SizedBox(height: 16),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(Icons.event),
                    title: Text(context.tr('Date')),
                    subtitle: Text(formatDate(_iso(_date))),
                    onTap: () async {
                      final picked = await showDatePicker(context: context, initialDate: _date, firstDate: DateTime(2020), lastDate: DateTime.now());
                      if (picked != null) setState(() => _date = picked);
                    },
                  ),
                  DropdownButtonFormField<int>(
                    initialValue: _categoryId,
                    isExpanded: true,
                    decoration: InputDecoration(labelText: context.tr('Category'), errorText: _errors['account_id']),
                    items: [for (final c in categories) DropdownMenuItem(value: asInt(c['id']), child: Text(c['name'] as String))],
                    onChanged: (v) => setState(() => _categoryId = v),
                    validator: required,
                  ),
                  const SizedBox(height: 8),
                  SwitchListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(context.tr('Already paid')),
                    value: _paid,
                    onChanged: (v) => setState(() => _paid = v),
                  ),
                  if (_paid)
                    DropdownButtonFormField<String>(
                      initialValue: _method,
                      isExpanded: true,
                      decoration: InputDecoration(labelText: context.tr('Paid with')),
                      items: [for (final m in methods) DropdownMenuItem(value: m, child: Text(label(m)))],
                      onChanged: (v) => setState(() => _method = v ?? _method),
                    ),
                ],
                const SizedBox(height: 16),
                TextFormField(controller: _vendor, decoration: InputDecoration(labelText: context.tr('Paid to (optional)'))),
                const SizedBox(height: 16),
                TextFormField(controller: _reference, decoration: InputDecoration(labelText: context.tr('Reference (optional)'))),
                const SizedBox(height: 16),
                TextFormField(controller: _notes, maxLines: 2, decoration: InputDecoration(labelText: context.tr('Notes'))),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: _submitting ? null : _submit,
                  child: _submitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                      : Text(context.tr(_editing ? 'Save changes' : 'Add expense')),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
