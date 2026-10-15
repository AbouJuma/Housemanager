/// Rent periods - the app's copy of App\Modules\Leases\Support\RentPeriods,
/// so forms can show the right figures before saving. A unit's price is
/// quoted per period; a lease bills it converted to its own cycle.
class RentPeriods {
  static const months = {'monthly': 1, 'quarterly': 3, 'semi_annual': 6, 'annual': 12};

  static const cycles = ['monthly', 'quarterly', 'semi_annual', 'annual'];

  /// "Monthly" etc. for a billing-cycle picker.
  static const labels = {'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi_annual': 'Semi annual', 'annual': 'Annual'};

  /// "month" etc. for "500,000 / month".
  static const perLabels = {'monthly': 'month', 'quarterly': 'quarter', 'semi_annual': 'half year', 'annual': 'year'};

  static int monthsIn(String? period) => months[period] ?? 1;

  /// [amount] quoted per [from], expressed per [to] (rounded to cents).
  static double convert(double amount, String? from, String? to) =>
      (amount / monthsIn(from) * monthsIn(to) * 100).roundToDouble() / 100;

  /// Last day of [periods] billing periods starting on [start] - whole
  /// months added (kept within the month's last day), minus one day.
  static DateTime endDate(DateTime start, String? cycle, int periods) {
    final total = monthsIn(cycle) * (periods < 1 ? 1 : periods);
    final firstOfTarget = DateTime(start.year, start.month + total, 1);
    final lastDay = DateTime(firstOfTarget.year, firstOfTarget.month + 1, 0).day;
    final sameDay = DateTime(firstOfTarget.year, firstOfTarget.month, start.day > lastDay ? lastDay : start.day);
    return sameDay.subtract(const Duration(days: 1));
  }

  static String iso(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
}
