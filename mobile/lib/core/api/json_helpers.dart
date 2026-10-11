/// Laravel's JsonResource wraps output in `{"data": ...}` ONLY when a
/// Resource (or ResourceCollection) is returned directly from a
/// controller - list/show endpoints in this app all do that. A Resource
/// embedded as a value inside a plain `response()->json([...])` array
/// (dashboards, login, pay) is NOT wrapped. These two helpers make that
/// distinction explicit at each call site instead of guessing.
library;

List<dynamic> unwrapList(dynamic body) => (body as Map<String, dynamic>)['data'] as List<dynamic>;

Map<String, dynamic> unwrapObject(dynamic body) => (body as Map<String, dynamic>)['data'] as Map<String, dynamic>;

/// Laravel serializes `decimal` columns as strings ("500.00") but
/// computed values (sums, counts) as numbers - and either may be null.
/// These accept all three, so a model never crashes with "type 'String'
/// is not a subtype of type 'num'" because the backend picked the other
/// representation.
double asDouble(dynamic value) => asDoubleOrNull(value) ?? 0;

double? asDoubleOrNull(dynamic value) => switch (value) {
      num n => n.toDouble(),
      String s => double.tryParse(s.replaceAll(',', '')),
      _ => null,
    };

int asInt(dynamic value) => asIntOrNull(value) ?? 0;

int? asIntOrNull(dynamic value) => switch (value) {
      int n => n,
      num n => n.round(),
      String s => int.tryParse(s) ?? double.tryParse(s)?.round(),
      _ => null,
    };
