import '../../../core/api/api_client.dart';
import '../../../core/api/json_helpers.dart';

class RentRollRow {
  final String property;
  final String building;
  final String unit;
  final String status;
  final String? tenant;
  final String? leaseStart;
  final String? leaseEnd;
  final double rentAmount;

  RentRollRow({
    required this.property,
    required this.building,
    required this.unit,
    required this.status,
    this.tenant,
    this.leaseStart,
    this.leaseEnd,
    required this.rentAmount,
  });

  factory RentRollRow.fromJson(Map<String, dynamic> json) => RentRollRow(
        property: json['property'] as String,
        building: json['building'] as String,
        unit: json['unit'] as String,
        status: json['status'] as String,
        tenant: json['tenant'] as String?,
        leaseStart: json['lease_start'] as String?,
        leaseEnd: json['lease_end'] as String?,
        rentAmount: asDouble(json['rent_amount']),
      );
}

class OccupancyRow {
  final String property;
  final int totalUnits;
  final int occupied;
  final int vacant;
  final double occupancyRate;

  OccupancyRow({
    required this.property,
    required this.totalUnits,
    required this.occupied,
    required this.vacant,
    required this.occupancyRate,
  });

  factory OccupancyRow.fromJson(Map<String, dynamic> json) => OccupancyRow(
        property: json['property'] as String,
        totalUnits: asInt(json['total_units']),
        occupied: asInt(json['occupied']),
        vacant: asInt(json['vacant']),
        occupancyRate: asDouble(json['occupancy_rate']),
      );
}

/// Mirrors App\Modules\Reports\Http\Controllers\Api\V1\ReportController -
/// same two reports (of the web's six) surfaced on mobile for now: Rent
/// Roll and Occupancy, the ones staff/landlords check most day-to-day.
class ReportsRepository {
  ReportsRepository(this._apiClient);

  final ApiClient _apiClient;

  /// [status]: one of the unit statuses (vacant, occupied, …), or null for all.
  Future<List<RentRollRow>> rentRoll({String? status}) async {
    final response = await _apiClient.dio.get('/reports/rent-roll', queryParameters: {'status': ?status});
    return unwrapList(response.data).map((e) => RentRollRow.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// [status]: one of the property statuses (active, inactive, …), or null for all.
  Future<List<OccupancyRow>> occupancy({String? status}) async {
    final response = await _apiClient.dio.get('/reports/occupancy', queryParameters: {'status': ?status});
    return unwrapList(response.data).map((e) => OccupancyRow.fromJson(e as Map<String, dynamic>)).toList();
  }
}
