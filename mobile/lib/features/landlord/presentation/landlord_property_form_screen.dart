import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/l10n/l10n.dart';
import '../../../core/widgets/app_scaffold.dart';
import '../../../core/widgets/error_view.dart';
import '../../../core/widgets/toast.dart';
import '../data/landlord_providers.dart';

/// Add (uuid == null) or edit one of the landlord's own properties.
/// Region / district pick from the Tanzania list; area accepts any text,
/// with suggestions.
class LandlordPropertyFormScreen extends ConsumerStatefulWidget {
  const LandlordPropertyFormScreen({super.key, this.uuid});

  final String? uuid;

  @override
  ConsumerState<LandlordPropertyFormScreen> createState() => _LandlordPropertyFormScreenState();
}

class _LandlordPropertyFormScreenState extends ConsumerState<LandlordPropertyFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _area = TextEditingController();
  final _address = TextEditingController();
  final _description = TextEditingController();
  final _totalArea = TextEditingController();
  String _type = 'residential';
  String _status = 'active';
  String? _region;
  String? _district;
  bool _loading = false;
  bool _submitting = false;
  Map<String, String> _fieldErrors = const {};

  bool get _isEditing => widget.uuid != null;

  @override
  void initState() {
    super.initState();
    if (_isEditing) _loadExisting();
  }

  Future<void> _loadExisting() async {
    setState(() => _loading = true);
    try {
      final property = await ref.read(landlordRepositoryProvider).propertyJson(widget.uuid!);
      final address = (property['address'] as Map<String, dynamic>?) ?? const {};
      _name.text = property['name'] as String? ?? '';
      _type = property['type'] as String? ?? _type;
      _status = property['status'] as String? ?? _status;
      _region = address['state'] as String?;
      _district = address['city'] as String?;
      _area.text = address['area'] as String? ?? '';
      _address.text = address['line1'] as String? ?? '';
      _description.text = property['description'] as String? ?? '';
      _totalArea.text = property['total_area']?.toString() ?? '';
    } catch (e) {
      if (mounted) showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    for (final c in [_name, _area, _address, _description, _totalArea]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _submitting = true;
      _fieldErrors = const {};
    });

    String? text(TextEditingController c) => c.text.trim().isEmpty ? null : c.text.trim();
    final data = {
      'name': _name.text.trim(),
      'type': _type,
      if (_isEditing) 'status': _status,
      'state': _region,
      'city': _district,
      'area': text(_area),
      'address_line1': text(_address),
      'description': text(_description),
      'total_area': text(_totalArea),
      'country_code': 'TZ',
    };

    try {
      final repository = ref.read(landlordRepositoryProvider);
      if (_isEditing) {
        await repository.updateProperty(widget.uuid!, data);
        ref.invalidate(landlordPropertyProvider(widget.uuid!));
      } else {
        await repository.createProperty(data);
      }
      if (!mounted) return;
      showToast(context, context.tr(_isEditing ? 'Property updated.' : 'Property added.'), type: ToastType.success);
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _fieldErrors = validationErrors(e));
      showToast(context, friendlyErrorMessage(e), type: ToastType.error);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final options = ref.watch(landlordPropertyOptionsProvider);

    return AppScaffold(
      title: _isEditing ? 'Edit property' : 'Add property',
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : options.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => ErrorView(error: error, onRetry: () => ref.invalidate(landlordPropertyOptionsProvider)),
              data: (options) => _form(context, options),
            ),
    );
  }

  Widget _form(BuildContext context, Map<String, dynamic> options) {
    final types = (options['types'] as List<dynamic>).cast<String>();
    final statuses = (options['statuses'] as List<dynamic>).cast<String>();
    final regions = (options['regions'] as Map<String, dynamic>).map((k, v) => MapEntry(k, (v as List<dynamic>).cast<String>()));
    final areas = (options['areas'] is Map ? options['areas'] as Map<String, dynamic> : const <String, dynamic>{})
        .map((k, v) => MapEntry(k, (v as List<dynamic>).cast<String>()));
    final districts = _region == null ? const <String>[] : (regions[_region] ?? const <String>[]);
    final areaSuggestions = _district == null ? const <String>[] : (areas[_district] ?? const <String>[]);

    String label(String value) => context.tr(value.replaceAll('_', ' ')[0].toUpperCase() + value.replaceAll('_', ' ').substring(1));

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextFormField(
            controller: _name,
            decoration: InputDecoration(labelText: context.tr('Property name'), errorText: _fieldErrors['name']),
            validator: (v) => (v == null || v.trim().isEmpty) ? context.tr('This field is required.') : null,
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            initialValue: types.contains(_type) ? _type : types.first,
            isExpanded: true,
            decoration: InputDecoration(labelText: context.tr('Type'), errorText: _fieldErrors['type']),
            items: [for (final t in types) DropdownMenuItem(value: t, child: Text(label(t)))],
            onChanged: (v) => setState(() => _type = v ?? _type),
          ),
          if (_isEditing) ...[
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: statuses.contains(_status) ? _status : statuses.first,
              isExpanded: true,
              decoration: InputDecoration(labelText: context.tr('Status')),
              items: [for (final s in statuses) DropdownMenuItem(value: s, child: Text(label(s)))],
              onChanged: (v) => setState(() => _status = v ?? _status),
            ),
          ],
          const SizedBox(height: 24),
          Text(context.tr('Location'), style: Theme.of(context).textTheme.titleSmall),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            initialValue: regions.containsKey(_region) ? _region : null,
            isExpanded: true,
            decoration: InputDecoration(labelText: context.tr('Region')),
            items: [for (final r in regions.keys) DropdownMenuItem(value: r, child: Text(r))],
            onChanged: (v) => setState(() {
              _region = v;
              _district = null;
            }),
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            key: ValueKey('district-$_region'),
            initialValue: districts.contains(_district) ? _district : null,
            isExpanded: true,
            decoration: InputDecoration(labelText: context.tr('District')),
            items: [for (final d in districts) DropdownMenuItem(value: d, child: Text(d))],
            onChanged: districts.isEmpty ? null : (v) => setState(() => _district = v),
          ),
          const SizedBox(height: 16),
          Autocomplete<String>(
            key: ValueKey('area-$_district'),
            initialValue: TextEditingValue(text: _area.text),
            optionsBuilder: (value) => areaSuggestions.where((a) => a.toLowerCase().contains(value.text.toLowerCase())),
            onSelected: (value) => _area.text = value,
            fieldViewBuilder: (context, controller, focusNode, onSubmit) => TextFormField(
              controller: controller,
              focusNode: focusNode,
              onChanged: (value) => _area.text = value,
              decoration: InputDecoration(labelText: context.tr('Area'), hintText: context.tr('e.g. Masaki')),
            ),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _address,
            decoration: InputDecoration(labelText: context.tr('Street / address')),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _totalArea,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            decoration: InputDecoration(labelText: context.tr('Total area (sqm)'), errorText: _fieldErrors['total_area']),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _description,
            maxLines: 3,
            decoration: InputDecoration(labelText: context.tr('Description')),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _submitting ? null : _submit,
            child: _submitting
                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                : Text(context.tr(_isEditing ? 'Save changes' : 'Add property')),
          ),
        ],
      ),
    );
  }
}
