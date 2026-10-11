/// Shared model classes matching this app's backend JsonResource shapes
/// (see e.g. App\Modules\Leases\Http\Resources\LeaseResource). Kept
/// deliberately plain - only the fields each screen actually reads, not
/// a full mirror of every backend field.
library;

import '../api/json_helpers.dart';

class UnitRef {
  final String uuid;
  final String unitNumber;
  final String? buildingName;
  final String? propertyName;

  UnitRef({required this.uuid, required this.unitNumber, this.buildingName, this.propertyName});

  factory UnitRef.fromJson(Map<String, dynamic> json) {
    final building = json['building'] as Map<String, dynamic>?;
    final property = building?['property'] as Map<String, dynamic>?;
    return UnitRef(
      uuid: json['uuid'] as String,
      unitNumber: json['unit_number'] as String,
      buildingName: building?['name'] as String?,
      propertyName: property?['name'] as String?,
    );
  }

  String get label => [unitNumber, buildingName, propertyName].whereType<String>().join(' · ');
}

class Lease {
  final String uuid;
  final String status;
  final String? startDate;
  final String? endDate;
  final String billingCycle;
  final double rentAmount;
  final double? depositAmount;
  final String? terminationReason;
  final String? notes;
  final UnitRef? unit;
  final String? tenantUuid;
  final String? tenantName;

  Lease({
    required this.uuid,
    required this.status,
    this.startDate,
    this.endDate,
    this.billingCycle = 'monthly',
    required this.rentAmount,
    this.depositAmount,
    this.terminationReason,
    this.notes,
    this.unit,
    this.tenantUuid,
    this.tenantName,
  });

  factory Lease.fromJson(Map<String, dynamic> json) {
    final tenant = json['tenant'] as Map<String, dynamic>?;

    return Lease(
      uuid: json['uuid'] as String,
      status: json['status'] as String,
      startDate: json['start_date'] as String?,
      endDate: json['end_date'] as String?,
      billingCycle: json['billing_cycle'] as String? ?? 'monthly',
      rentAmount: asDouble(json['rent_amount']),
      depositAmount: asDoubleOrNull(json['deposit_amount']),
      terminationReason: json['termination_reason'] as String?,
      notes: json['notes'] as String?,
      unit: json['unit'] != null ? UnitRef.fromJson(json['unit'] as Map<String, dynamic>) : null,
      tenantUuid: tenant?['uuid'] as String?,
      tenantName: tenant?['name'] as String?,
    );
  }
}

class Invoice {
  final String uuid;
  final String invoiceNumber;
  final String status;
  final String? dueDate;
  final double totalAmount;
  final double amountPaid;
  final double balanceDue;
  final UnitRef? unit;

  Invoice({
    required this.uuid,
    required this.invoiceNumber,
    required this.status,
    this.dueDate,
    required this.totalAmount,
    required this.amountPaid,
    required this.balanceDue,
    this.unit,
  });

  factory Invoice.fromJson(Map<String, dynamic> json) {
    final lease = json['lease'] as Map<String, dynamic>?;
    final sale = json['sale'] as Map<String, dynamic>?;
    final unitJson = (lease?['unit'] ?? sale?['unit']) as Map<String, dynamic>?;

    return Invoice(
      uuid: json['uuid'] as String,
      invoiceNumber: json['invoice_number'] as String,
      status: json['status'] as String,
      dueDate: json['due_date'] as String?,
      totalAmount: asDouble(json['total_amount']),
      amountPaid: asDouble(json['amount_paid']),
      balanceDue: asDouble(json['balance_due']),
      unit: unitJson != null ? UnitRef.fromJson(unitJson) : null,
    );
  }
}

class PropertyItem {
  final String uuid;
  final String name;
  final String type;
  final String status;
  final int? buildingsCount;
  final String? addressLine1;
  final String? city;
  // Only present on the single-property detail call (the API only embeds
  // this when the controller eager-loads buildings.units - see
  // PropertyResource::toArray()'s whenLoaded('buildings', ...)), null on
  // every list screen.
  final List<PropertyBuildingDetail>? buildings;

  PropertyItem({
    required this.uuid,
    required this.name,
    required this.type,
    required this.status,
    this.buildingsCount,
    this.addressLine1,
    this.city,
    this.buildings,
  });

  factory PropertyItem.fromJson(Map<String, dynamic> json) {
    final address = json['address'] as Map<String, dynamic>?;

    return PropertyItem(
      uuid: json['uuid'] as String,
      name: json['name'] as String,
      type: json['type'] as String,
      status: json['status'] as String,
      buildingsCount: asIntOrNull(json['buildings_count']),
      addressLine1: address?['line1'] as String?,
      city: address?['city'] as String?,
      buildings: (json['buildings'] as List<dynamic>?)
          ?.map((e) => PropertyBuildingDetail.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class PropertyBuildingDetail {
  final String uuid;
  final String name;
  final List<UnitDetail> units;

  PropertyBuildingDetail({required this.uuid, required this.name, required this.units});

  factory PropertyBuildingDetail.fromJson(Map<String, dynamic> json) => PropertyBuildingDetail(
        uuid: json['uuid'] as String,
        name: json['name'] as String,
        units: (json['units'] as List<dynamic>? ?? [])
            .map((e) => UnitDetail.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class UnitDetail {
  final String uuid;
  final String unitNumber;
  final String type;
  final String status;
  final double rentAmount;
  final String? tenantName;
  final String? tenantPhone;
  // A unit is either rented (tenant, above) or sold (this) - rarely both.
  final UnitSaleSummary? sale;

  UnitDetail({
    required this.uuid,
    required this.unitNumber,
    required this.type,
    required this.status,
    required this.rentAmount,
    this.tenantName,
    this.tenantPhone,
    this.sale,
  });

  factory UnitDetail.fromJson(Map<String, dynamic> json) {
    final tenant = json['tenant'] as Map<String, dynamic>?;
    final sale = json['sale'] as Map<String, dynamic>?;

    return UnitDetail(
      uuid: json['uuid'] as String,
      unitNumber: json['unit_number'] as String,
      type: json['type'] as String,
      status: json['status'] as String,
      rentAmount: asDouble(json['rent_amount']),
      tenantName: tenant?['name'] as String?,
      tenantPhone: tenant?['phone'] as String?,
      sale: sale != null ? UnitSaleSummary.fromJson(sale) : null,
    );
  }
}

/// A sold unit's buyer and payment progress - including a partial
/// payment, via totalPaid/remainingBalance, same figures the buyer's own
/// payment notifications and portal already show (Sale's own accessors
/// on the backend), so this never disagrees with what the buyer sees.
class UnitSaleSummary {
  final String uuid;
  final String status;
  final String? buyerName;
  final String? buyerPhone;
  final double totalPrice;
  final double totalPaid;
  final double remainingBalance;
  final int completionPercentage;

  UnitSaleSummary({
    required this.uuid,
    required this.status,
    this.buyerName,
    this.buyerPhone,
    required this.totalPrice,
    required this.totalPaid,
    required this.remainingBalance,
    required this.completionPercentage,
  });

  bool get isFullyPaid => remainingBalance <= 0;
  bool get isPartiallyPaid => totalPaid > 0 && remainingBalance > 0;

  factory UnitSaleSummary.fromJson(Map<String, dynamic> json) {
    final buyer = json['buyer'] as Map<String, dynamic>?;

    return UnitSaleSummary(
      uuid: json['uuid'] as String,
      status: json['status'] as String,
      buyerName: buyer?['name'] as String?,
      buyerPhone: buyer?['phone'] as String?,
      totalPrice: asDouble(json['total_price']),
      totalPaid: asDouble(json['total_paid']),
      remainingBalance: asDouble(json['remaining_balance']),
      completionPercentage: asInt(json['completion_percentage']),
    );
  }
}

class Expense {
  final String uuid;
  final String description;
  final double amount;
  final String? expenseDate;
  final String paymentStatus;
  final String? propertyName;

  Expense({
    required this.uuid,
    required this.description,
    required this.amount,
    this.expenseDate,
    required this.paymentStatus,
    this.propertyName,
  });

  factory Expense.fromJson(Map<String, dynamic> json) => Expense(
        uuid: json['uuid'] as String,
        description: json['description'] as String,
        amount: asDouble(json['amount']),
        expenseDate: json['expense_date'] as String?,
        paymentStatus: json['payment_status'] as String? ?? 'unknown',
        propertyName: (json['property'] as Map<String, dynamic>?)?['name'] as String?,
      );
}

class MaintenanceRequestItem {
  final String uuid;
  final String title;
  final String status;
  final String priority;
  final String? reportedDate;
  final String? propertyName;

  MaintenanceRequestItem({
    required this.uuid,
    required this.title,
    required this.status,
    required this.priority,
    this.reportedDate,
    this.propertyName,
  });

  factory MaintenanceRequestItem.fromJson(Map<String, dynamic> json) => MaintenanceRequestItem(
        uuid: json['uuid'] as String,
        title: json['title'] as String,
        status: json['status'] as String,
        priority: json['priority'] as String,
        reportedDate: json['reported_date'] as String?,
        propertyName: (json['property'] as Map<String, dynamic>?)?['name'] as String?,
      );
}

class DocumentItem {
  final String uuid;
  final String title;
  final String? category;
  final String? fileUrl;

  DocumentItem({required this.uuid, required this.title, this.category, this.fileUrl});

  factory DocumentItem.fromJson(Map<String, dynamic> json) => DocumentItem(
        uuid: json['uuid'] as String,
        title: json['title'] as String,
        category: json['category'] as String?,
        fileUrl: json['file_url'] as String?,
      );
}

class Installment {
  final String uuid;
  final int installmentNumber;
  final String? dueDate;
  final double amount;
  final double paidAmount;
  final double remainingAmount;
  final String status;

  Installment({
    required this.uuid,
    required this.installmentNumber,
    this.dueDate,
    required this.amount,
    required this.paidAmount,
    required this.remainingAmount,
    required this.status,
  });

  factory Installment.fromJson(Map<String, dynamic> json) => Installment(
        uuid: json['uuid'] as String,
        installmentNumber: asInt(json['installment_number']),
        dueDate: json['due_date'] as String?,
        amount: asDouble(json['amount']),
        paidAmount: asDouble(json['paid_amount']),
        remainingAmount: asDouble(json['remaining_amount']),
        status: json['status'] as String,
      );
}

/// Full tenant record for staff CRUD - richer than the tiny `tenant`
/// summary embedded in LeaseResource, matching every field
/// TenantResource actually exposes.
class StaffTenant {
  final String uuid;
  final String name;
  final String type;
  final String status;
  final String? email;
  final String? phone;
  final String? notes;

  StaffTenant({
    required this.uuid,
    required this.name,
    required this.type,
    required this.status,
    this.email,
    this.phone,
    this.notes,
  });

  factory StaffTenant.fromJson(Map<String, dynamic> json) => StaffTenant(
        uuid: json['uuid'] as String,
        name: json['name'] as String,
        type: json['type'] as String,
        status: json['status'] as String,
        email: json['email'] as String?,
        phone: json['phone'] as String?,
        notes: json['notes'] as String?,
      );
}

/// Lightweight (uuid, name/number) summaries for the cascading
/// Property -> Building -> Unit picker used when creating a lease -
/// there's no flat "all vacant units" endpoint (Properties & Units
/// full CRUD is a later phase), so lease creation walks the same
/// nesting the backend already exposes.
class PropertySummary {
  final String uuid;
  final String name;

  PropertySummary({required this.uuid, required this.name});

  factory PropertySummary.fromJson(Map<String, dynamic> json) =>
      PropertySummary(uuid: json['uuid'] as String, name: json['name'] as String);
}

class BuildingSummary {
  final String uuid;
  final String name;

  BuildingSummary({required this.uuid, required this.name});

  factory BuildingSummary.fromJson(Map<String, dynamic> json) =>
      BuildingSummary(uuid: json['uuid'] as String, name: json['name'] as String);
}

class UnitSummary {
  final String uuid;
  final String unitNumber;
  final String status;

  /// The unit's own monthly rate - prefills a new lease's rent.
  final double? rentAmount;

  UnitSummary({required this.uuid, required this.unitNumber, required this.status, this.rentAmount});

  factory UnitSummary.fromJson(Map<String, dynamic> json) => UnitSummary(
        uuid: json['uuid'] as String,
        unitNumber: json['unit_number'] as String,
        status: json['status'] as String,
        // decimal columns arrive as strings ("500.00") from Laravel.
        rentAmount: double.tryParse('${json['rent_amount'] ?? ''}'),
      );
}

class Sale {
  final String uuid;
  final String status;
  final double totalPrice;
  final double totalPaid;
  final double remainingBalance;
  final int completionPercentage;
  final UnitRef? unit;
  final List<Installment> installments;

  Sale({
    required this.uuid,
    required this.status,
    required this.totalPrice,
    required this.totalPaid,
    required this.remainingBalance,
    required this.completionPercentage,
    this.unit,
    this.installments = const [],
  });

  factory Sale.fromJson(Map<String, dynamic> json) => Sale(
        uuid: json['uuid'] as String,
        status: json['status'] as String,
        totalPrice: asDouble(json['total_price']),
        totalPaid: asDouble(json['total_paid']),
        remainingBalance: asDouble(json['remaining_balance']),
        completionPercentage: asInt(json['completion_percentage']),
        unit: json['unit'] != null ? UnitRef.fromJson(json['unit'] as Map<String, dynamic>) : null,
        installments: (json['installments'] as List<dynamic>? ?? [])
            .map((e) => Installment.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}
