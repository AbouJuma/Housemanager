import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Which of the four API auth flows the current session belongs to -
/// determines which /portal/{role}/... prefix (or /auth/... for staff)
/// a repository should call.
enum UserRole { staff, tenant, landlord, buyer }

extension UserRoleX on UserRole {
  String get apiSegment => switch (this) {
        UserRole.staff => 'staff',
        UserRole.tenant => 'tenant',
        UserRole.landlord => 'landlord',
        UserRole.buyer => 'buyer',
      };

  static UserRole fromApiSegment(String value) => switch (value) {
        'tenant' => UserRole.tenant,
        'landlord' => UserRole.landlord,
        'buyer' => UserRole.buyer,
        _ => UserRole.staff,
      };
}

/// Persists the Sanctum bearer token, role, and profile (name/email)
/// across app restarts. Secure storage (Keychain/Keystore-backed), not
/// SharedPreferences, since a leaked token is a real account takeover
/// risk. The profile is stored alongside the token specifically so a
/// restart can show "Welcome, {name}" immediately without a network
/// round trip - restoring the token alone left the name blank until
/// the next explicit login.
class TokenStorage {
  const TokenStorage(this._storage);

  final FlutterSecureStorage _storage;

  static const _tokenKey = 'auth_token';
  static const _roleKey = 'auth_role';
  static const _profileKey = 'auth_profile';

  Future<void> save(String token, UserRole role, {required Map<String, dynamic> profile}) async {
    await _storage.write(key: _tokenKey, value: token);
    await _storage.write(key: _roleKey, value: role.apiSegment);
    await saveProfile(profile);
  }

  /// Name, email, username, phone and (staff) permissions.
  Future<void> saveProfile(Map<String, dynamic> profile) => _storage.write(key: _profileKey, value: jsonEncode(profile));

  Future<Map<String, dynamic>?> readProfileJson() async {
    final raw = await _storage.read(key: _profileKey);
    if (raw == null) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  Future<String?> readToken() => _storage.read(key: _tokenKey);

  Future<UserRole?> readRole() async {
    final value = await _storage.read(key: _roleKey);
    return value == null ? null : UserRoleX.fromApiSegment(value);
  }


  Future<void> clear() async {
    await _storage.delete(key: _tokenKey);
    await _storage.delete(key: _roleKey);
    await _storage.delete(key: _profileKey);
  }
}
