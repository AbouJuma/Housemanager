import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api/api_client.dart';
import 'api/server_config.dart';
import 'l10n/l10n.dart';
import 'api/token_storage.dart';

/// App-wide singletons. Kept in one file since every feature repository
/// depends on the same two (apiClient, tokenStorage) - avoids scattering
/// the same three lines of wiring across every feature folder.
final secureStorageProvider = Provider<FlutterSecureStorage>((ref) => const FlutterSecureStorage());

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage(ref.watch(secureStorageProvider)));

// The server is fixed by the build (debug -> local, release -> live);
// rebuilt when the language changes - the server answers in it.
final apiClientProvider = Provider<ApiClient>(
  (ref) => ApiClient(
    ref.watch(tokenStorageProvider),
    ref.watch(apiBaseUrlProvider),
    language: effectiveLanguage(ref.watch(localeProvider)),
  ),
);
