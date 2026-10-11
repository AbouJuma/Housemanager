import 'package:intl/intl.dart';

final _money = NumberFormat.currency(symbol: '', decimalDigits: 2);

String formatMoney(double value) => _money.format(value).trim();

String formatDate(String? isoDate) {
  if (isoDate == null) return '—';
  try {
    final date = DateTime.parse(isoDate);
    return DateFormat.yMMMd().format(date);
  } catch (_) {
    return isoDate;
  }
}

/// Date and time in the phone's own timezone (the server sends UTC).
String formatDateTime(String? isoDateTime) {
  if (isoDateTime == null) return '—';
  try {
    return DateFormat.yMMMd().add_jm().format(DateTime.parse(isoDateTime).toLocal());
  } catch (_) {
    return isoDateTime;
  }
}
