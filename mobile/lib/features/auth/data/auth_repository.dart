import 'package:dio/dio.dart';

import '../../../core/api/api_client.dart';
import '../../../core/api/token_storage.dart';
import '../../../core/branding/branding.dart';

/// The signed-in person's own account, whichever role they are.
/// [permissions] is filled for staff only: the app shows the tabs and
/// actions their role allows, so roles a company creates later work
/// without an app update (App\Http\Controllers\Api\AccountController).
class AuthenticatedProfile {
  final String name;
  final String? email;
  final String? username;
  final String? phone;
  final List<String> permissions;

  AuthenticatedProfile({required this.name, this.email, this.username, this.phone, this.permissions = const []});

  bool can(String permission) => permissions.contains(permission);

  /// Any of [permissions].
  bool canAny(Iterable<String> permissions) => permissions.any(can);

  factory AuthenticatedProfile.fromJson(Map<String, dynamic> json, {List<dynamic>? permissions}) => AuthenticatedProfile(
        name: (json['name'] as String?) ?? '',
        email: json['email'] as String?,
        username: json['username'] as String?,
        phone: json['phone'] as String?,
        permissions: [...?(permissions ?? json['permissions'] as List<dynamic>?)?.whereType<String>()],
      );

  Map<String, dynamic> toJson() => {
        'name': name,
        'email': email,
        'username': username,
        'phone': phone,
        'permissions': permissions,
      };
}

class LoginResult {
  final String token;
  final UserRole role;
  final AuthenticatedProfile profile;
  final Branding branding;

  LoginResult(this.token, this.role, this.profile, this.branding);
}

/// Sign in/out and "my account" calls - see routes/api.php in each of the
/// Users/TenantPortal/LandlordPortal/BuyerPortal modules on the backend.
class AuthRepository {
  AuthRepository(this._apiClient, this._tokenStorage);

  final ApiClient _apiClient;
  final TokenStorage _tokenStorage;

  static String _base(UserRole role) => role == UserRole.staff ? '/auth' : '/portal/${role.apiSegment}';

  /// Signs in as [role]; if those details aren't a [role] account, tries
  /// the other roles - so picking the wrong role on the login screen
  /// still works. Connection errors are not retried as another role.
  Future<LoginResult> login({required UserRole role, required String email, required String password}) async {
    final order = [role, ...UserRole.values.where((r) => r != role)];
    ApiException? firstError;

    for (final candidate in order) {
      try {
        return await _loginAs(candidate, email, password);
      } on ApiException catch (e) {
        firstError ??= e;
        // Only "these details don't match" moves on to the next role.
        if (e.statusCode != 422) rethrow;
      }
    }

    throw firstError!;
  }

  Future<LoginResult> _loginAs(UserRole role, String email, String password) async {
    try {
      final response = await _apiClient.dio.post('${_base(role)}/login', data: {'email': email, 'password': password});

      final data = response.data as Map<String, dynamic>;
      final token = data['token'] as String;
      // Each role nests the profile under its own key ('user'/'tenant'/
      // 'landlord'/'buyer') - see e.g. App\Modules\TenantPortal\Http\
      // Controllers\Api\V1\AuthController::login().
      final profileKey = role == UserRole.staff ? 'user' : role.apiSegment;
      final profile = AuthenticatedProfile.fromJson(
        data[profileKey] as Map<String, dynamic>,
        permissions: data['permissions'] as List<dynamic>?,
      );

      await _tokenStorage.save(token, role, profile: profile.toJson());

      // Company colors/logo - see App\Modules\Companies\Support\BrandTheme::toArray().
      final branding = Branding.fromJson(data['branding'] as Map<String, dynamic>?);

      return LoginResult(token, role, profile, branding);
    } on DioException catch (e) {
      throw e.error is ApiException ? e.error as ApiException : ApiException(null, 'Login failed.');
    }
  }

  /// The latest profile (and staff permissions) from the server.
  Future<(AuthenticatedProfile, Branding)> fetchAccount(UserRole role) async {
    final response = await _apiClient.dio.get(role == UserRole.staff ? '/auth/me' : '${_base(role)}/profile');
    return _readAccount(response.data as Map<String, dynamic>);
  }

  Future<(AuthenticatedProfile, Branding)> updateAccount(UserRole role, Map<String, dynamic> data) async {
    final response = await _apiClient.dio.put('${_base(role)}/profile', data: data);
    return _readAccount(response.data as Map<String, dynamic>);
  }

  /// Returns the server's confirmation message.
  Future<String> changePassword(UserRole role, {required String current, required String password, required String confirmation}) async {
    final response = await _apiClient.dio.put('${_base(role)}/password', data: {
      'current_password': current,
      'password': password,
      'password_confirmation': confirmation,
    });
    return (response.data as Map<String, dynamic>)['message'] as String? ?? 'Your password has been changed.';
  }

  Future<(AuthenticatedProfile, Branding)> _readAccount(Map<String, dynamic> data) async {
    final profile = AuthenticatedProfile.fromJson(
      data['profile'] as Map<String, dynamic>,
      permissions: data['permissions'] as List<dynamic>?,
    );
    await _tokenStorage.saveProfile(profile.toJson());
    return (profile, Branding.fromJson(data['branding'] as Map<String, dynamic>?));
  }

  Future<void> logout(UserRole role) async {
    try {
      await _apiClient.dio.post('${_base(role)}/logout');
    } catch (_) {
      // Token might already be invalid/expired server-side - still clear
      // local state regardless, the user is logging out either way.
    }
    await _tokenStorage.clear();
  }
}
