import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/api/api_client.dart';
import '../../../core/api/token_storage.dart';
import '../../../core/branding/branding.dart';
import '../../../core/providers.dart';
import '../data/auth_repository.dart';

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.watch(apiClientProvider), ref.watch(tokenStorageProvider)),
);

enum AuthStatus { checking, signedOut, signedIn }

class AuthState {
  final AuthStatus status;
  final UserRole? role;
  final AuthenticatedProfile? profile;
  final String? error;

  const AuthState({required this.status, this.role, this.profile, this.error});

  const AuthState.checking() : this(status: AuthStatus.checking);
  const AuthState.signedOut({String? error}) : this(status: AuthStatus.signedOut, error: error);
  const AuthState.signedIn(UserRole role, AuthenticatedProfile profile)
      : this(status: AuthStatus.signedIn, role: role, profile: profile);
}

/// Holds the current session for the whole app - which role (if any) is
/// signed in, and their profile. Restores from secure storage on
/// startup so a token survives an app restart; the router (see
/// core/router/app_router.dart) reads this to decide whether to show
/// the login screen or the signed-in shell.
class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    _restore();
    return const AuthState.checking();
  }

  Future<void> _restore() async {
    final tokenStorage = ref.read(tokenStorageProvider);
    final token = await tokenStorage.readToken();
    final role = await tokenStorage.readRole();

    if (token == null || role == null) {
      state = const AuthState.signedOut();
      return;
    }

    // A stored token isn't proof it's still valid server-side (it may
    // have been revoked) - the first authenticated screen's own data
    // fetch will surface a 401 and this notifier's signOut() gets
    // called from the API error-handling in that case. Restoring
    // optimistically here avoids a network round trip just to show the
    // splash screen. Profile (name/email) is restored from the same
    // secure storage the token lives in - saved once at login - so the
    // "Welcome, {name}" header doesn't sit blank until the next login.
    final saved = await tokenStorage.readProfileJson();
    state = AuthState.signedIn(role, AuthenticatedProfile.fromJson(saved ?? const {}));

    // Then quietly pick up anything changed on the server since - a new
    // role or permissions (which tabs show), a renamed account, branding.
    _refreshAccount(role);
  }

  Future<void> _refreshAccount(UserRole role) async {
    try {
      final (profile, branding) = await ref.read(authRepositoryProvider).fetchAccount(role);
      if (state.status != AuthStatus.signedIn || state.role != role) return;
      await ref.read(brandingProvider.notifier).update(branding);
      state = AuthState.signedIn(role, profile);
    } on DioException catch (e) {
      // The saved token was revoked/expired - back to the login screen.
      final apiError = e.error;
      if (apiError is ApiException && apiError.statusCode == 401) {
        await ref.read(tokenStorageProvider).clear();
        state = const AuthState.signedOut();
      }
      // Offline etc.: keep the saved profile.
    } catch (_) {
      // Keep the saved profile.
    }
  }

  Future<void> login({required UserRole role, required String email, required String password}) async {
    try {
      // The repository falls back to the other roles when the chosen one
      // doesn't match, so sign in as whichever role actually did.
      final result = await ref.read(authRepositoryProvider).login(role: role, email: email, password: password);
      await ref.read(brandingProvider.notifier).update(result.branding);
      state = AuthState.signedIn(result.role, result.profile);
    } on ApiException catch (e) {
      state = AuthState.signedOut(error: e.message);
      rethrow;
    }
  }

  /// Saves name/username/email/phone from the Profile screen.
  Future<void> updateAccount(Map<String, dynamic> data) async {
    final role = state.role!;
    final (profile, _) = await ref.read(authRepositoryProvider).updateAccount(role, data);
    state = AuthState.signedIn(role, profile);
  }

  Future<String> changePassword({required String current, required String password, required String confirmation}) =>
      ref.read(authRepositoryProvider).changePassword(state.role!, current: current, password: password, confirmation: confirmation);

  Future<void> logout() async {
    final role = state.role;
    if (role != null) {
      await ref.read(authRepositoryProvider).logout(role);
    }
    state = const AuthState.signedOut();
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

/// Whether the signed-in staff member's role allows [permission] - used to
/// show only the actions they can actually perform (the server enforces
/// the same rules regardless).
bool hasPermission(WidgetRef ref, String permission) =>
    ref.watch(authProvider.select((s) => s.profile?.can(permission) ?? false));
