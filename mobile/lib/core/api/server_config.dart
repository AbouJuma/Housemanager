import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// The live backend - what release builds (the APK users install) use.
const liveApiBaseUrl = 'https://housemanager.ecofieldgroup.com/api/v1';

/// A backend on the developer's PC (`php artisan serve`) - what debug
/// builds use. The Android emulator reaches the PC through the special
/// 10.0.2.2 alias; desktop, web and the iOS simulator use 127.0.0.1.
String localApiBaseUrl() {
  if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) return 'http://10.0.2.2:8000/api/v1';
  return 'http://127.0.0.1:8000/api/v1';
}

/// Chosen by the build, never by the user: release -> live, debug ->
/// local. To debug on a physical phone against your PC, pass its Wi-Fi
/// address at build time:
///   flutter run --dart-define=API_BASE_URL=http://192.168.x.x:8000/api/v1
String apiBaseUrl() {
  const fromBuild = String.fromEnvironment('API_BASE_URL');
  if (fromBuild.isNotEmpty) return fromBuild;
  return kReleaseMode ? liveApiBaseUrl : localApiBaseUrl();
}

final apiBaseUrlProvider = Provider<String>((ref) => apiBaseUrl());

/// Turns a no-response DioException into something people can act on
/// (the screen shows a friendlier summary - see core/widgets/error_view.dart).
String describeConnectionError(DioException e, String baseUrl) {
  final detail = '${e.error ?? e.message ?? ''}'.toLowerCase();

  if (e.response != null) return 'The server answered with an error (${e.response!.statusCode}).';
  if (detail.contains('cleartext')) {
    return 'This phone blocked the connection to the server. Please install the latest app build.';
  }
  if (e.type == DioExceptionType.connectionTimeout || e.type == DioExceptionType.receiveTimeout) {
    return 'The server took too long to answer. Check your internet connection and try again.';
  }
  return "Couldn't reach the server. Check your internet connection and try again.";
}
