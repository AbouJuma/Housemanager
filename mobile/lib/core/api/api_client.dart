import 'package:dio/dio.dart';

import 'server_config.dart';
import 'token_storage.dart';

/// Thrown for any non-2xx API response, carrying the status code and the
/// Laravel-shaped error payload (`message` and, for a 422, `errors`) so
/// UI code can show something meaningful instead of a generic failure.
class ApiException implements Exception {
  final int? statusCode;
  final String message;
  final Map<String, dynamic>? errors;

  ApiException(this.statusCode, this.message, [this.errors]);

  @override
  String toString() => message;
}

/// One shared Dio instance for the whole app. The bearer token is
/// attached per-request from secure storage rather than baked in once,
/// since it changes across login/logout without recreating the client.
class ApiClient {
  ApiClient(this._tokenStorage, this.baseUrl, {String language = 'en'})
      : dio = Dio(BaseOptions(
          baseUrl: baseUrl,
          connectTimeout: const Duration(seconds: 15),
          // A busy dev server (php artisan serve handles one request at a
          // time) can be slow - 30s before giving up.
          receiveTimeout: const Duration(seconds: 30),
          // The server answers (validation messages etc.) in this language.
          headers: {'Accept': 'application/json', 'Accept-Language': language},
        )) {
    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _tokenStorage.readToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) {
        handler.next(_toApiException(error));
      },
    ));
  }

  final Dio dio;
  final TokenStorage _tokenStorage;

  /// The API base URL this client talks to - see server_config.dart.
  final String baseUrl;

  DioException _toApiException(DioException error) {
    final response = error.response;
    if (response == null) {
      return error.copyWith(
        error: ApiException(null, describeConnectionError(error, baseUrl)),
      );
    }

    final data = response.data;
    final errors = data is Map<String, dynamic> ? data['errors'] as Map<String, dynamic>? : null;

    // A 422's top-level `message` is Laravel's generic "The given data
    // was invalid." boilerplate - the actual reason lives in `errors`,
    // keyed by field (or, for a validation failure not tied to any
    // input field - e.g. AuthController rejecting a SaaS-owner login -
    // under 'email' anyway, since ValidationException::withMessages()
    // always needs a field key). Preferring the first errors entry
    // means every form in the app shows the real reason instead of that
    // boilerplate.
    final fieldErrors = errors?.values.whereType<List>().expand((v) => v).whereType<String>();
    final message = (fieldErrors != null && fieldErrors.isNotEmpty ? fieldErrors.first : null) ??
        (data is Map<String, dynamic> ? data['message'] as String? : null) ??
        'Something went wrong (${response.statusCode}).';

    return error.copyWith(error: ApiException(response.statusCode, message, errors));
  }
}
