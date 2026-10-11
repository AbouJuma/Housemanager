import 'package:flutter/services.dart';

/// Groups digits with thousand separators as the user types
/// ("1250000.5" -> "1,250,000.5"), allowing up to two decimals.
/// Read the value back with [parseMoney], which strips the commas.
class MoneyInputFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    final formatted = formatMoneyInput(newValue.text);
    if (formatted == null) return oldValue;

    // Keep the caret after the same number of digits it was after.
    final digitsBeforeCaret = newValue.text
        .substring(0, newValue.selection.end.clamp(0, newValue.text.length))
        .replaceAll(RegExp(r'[^0-9.]'), '')
        .length;
    var offset = 0;
    var seen = 0;
    while (offset < formatted.length && seen < digitsBeforeCaret) {
      if (RegExp(r'[0-9.]').hasMatch(formatted[offset])) seen++;
      offset++;
    }

    return TextEditingValue(text: formatted, selection: TextSelection.collapsed(offset: offset));
  }
}

/// "1250000.50" / "1,250,000.5" -> "1,250,000.50"-style text, or null
/// when the input isn't a valid partial amount (so the keystroke is
/// rejected).
String? formatMoneyInput(String raw) {
  final cleaned = raw.replaceAll(',', '');
  if (cleaned.isEmpty) return '';
  if (!RegExp(r'^\d*\.?\d{0,2}$').hasMatch(cleaned)) return null;

  final parts = cleaned.split('.');
  final whole = parts[0].isEmpty ? '' : (parts[0].replaceFirst(RegExp(r'^0+(?=\d)'), ''));
  final grouped = whole.replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (_) => ',');

  return parts.length > 1 ? '${grouped.isEmpty ? '0' : grouped}.${parts[1]}' : grouped;
}

/// Parses a (possibly comma-grouped) amount; null when empty/invalid.
double? parseMoney(String? text) => double.tryParse((text ?? '').replaceAll(',', '').trim());
