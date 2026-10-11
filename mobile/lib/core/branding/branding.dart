import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers.dart';

/// The signed-in company's look - colors and logo set by its admin in
/// the web app (Settings -> Branding) and sent with every login
/// response (see App\Modules\Companies\Support\BrandTheme::toArray()).
class Branding {
  const Branding({required this.primary, required this.accent, this.companyName, this.logoUrl});

  static const defaultPrimary = Color(0xFF1F5A94);
  static const defaultAccent = Color(0xFF5EEAD4);
  static const fallback = Branding(primary: defaultPrimary, accent: defaultAccent);

  final Color primary;
  final Color accent;
  final String? companyName;
  final String? logoUrl;

  factory Branding.fromJson(Map<String, dynamic>? json) {
    if (json == null) return fallback;
    return Branding(
      primary: parseHex(json['primary_color'] as String?) ?? defaultPrimary,
      accent: parseHex(json['accent_color'] as String?) ?? defaultAccent,
      companyName: json['company_name'] as String?,
      logoUrl: json['logo_url'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'primary_color': toHex(primary),
        'accent_color': toHex(accent),
        'company_name': companyName,
        'logo_url': logoUrl,
      };

  static Color? parseHex(String? value) {
    if (value == null || !RegExp(r'^#[0-9A-Fa-f]{6}$').hasMatch(value)) return null;
    return Color(int.parse('FF${value.substring(1)}', radix: 16));
  }

  static String toHex(Color color) =>
      '#${(color.toARGB32() & 0xFFFFFF).toRadixString(16).padLeft(6, '0').toUpperCase()}';
}

/// Current company branding - restored from secure storage on startup
/// (so the login screen and splash already wear the last company's
/// colors) and replaced on every login.
class BrandingNotifier extends Notifier<Branding> {
  static const _storageKey = 'branding';

  @override
  Branding build() {
    _restore();
    return Branding.fallback;
  }

  Future<void> _restore() async {
    final raw = await ref.read(secureStorageProvider).read(key: _storageKey);
    if (raw == null) return;
    try {
      state = Branding.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      // Corrupt/old value - keep the default look.
    }
  }

  Future<void> update(Branding branding) async {
    state = branding;
    await ref.read(secureStorageProvider).write(key: _storageKey, value: jsonEncode(branding.toJson()));
  }
}

final brandingProvider = NotifierProvider<BrandingNotifier, Branding>(BrandingNotifier.new);

/// Light / dark / follow-the-phone, chosen on the More screen.
class ThemeModeNotifier extends Notifier<ThemeMode> {
  static const _storageKey = 'theme_mode';

  @override
  ThemeMode build() {
    _restore();
    return ThemeMode.system;
  }

  Future<void> _restore() async {
    final raw = await ref.read(secureStorageProvider).read(key: _storageKey);
    state = ThemeMode.values.firstWhere((mode) => mode.name == raw, orElse: () => ThemeMode.system);
  }

  Future<void> set(ThemeMode mode) async {
    state = mode;
    await ref.read(secureStorageProvider).write(key: _storageKey, value: mode.name);
  }
}

final themeModeProvider = NotifierProvider<ThemeModeNotifier, ThemeMode>(ThemeModeNotifier.new);
