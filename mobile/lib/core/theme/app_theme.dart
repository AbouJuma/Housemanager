import 'package:flutter/material.dart';

import '../branding/branding.dart';

/// Neutral palette shared with the web app (resources/css/app.scss) so
/// both feel like one product: slate text, soft gray-blue backgrounds in
/// light mode, deep navy in dark mode.
class AppColors {
  static const ink = Color(0xFF0F2440);

  static const lightBackground = Color(0xFFF3F5F8);
  static const lightSurface = Colors.white;
  static const lightBorder = Color(0xFFE8ECF2);
  static const lightText = Color(0xFF101828);
  static const lightMuted = Color(0xFF475467);

  static const darkBackground = Color(0xFF0A1A2F);
  static const darkSurface = Color(0xFF10213A);
  static const darkSurfaceHigh = Color(0xFF18304F);
  static const darkBorder = Color(0xFF24405F);
  static const darkText = Color(0xFFE4E7EC);
  static const darkMuted = Color(0xFFA6B0C3);

  /// Colorful icon tiles - (foreground, light background, dark background).
  static const tilePalette = <(Color, Color, Color)>[
    (Color(0xFF1D4ED8), Color(0xFFDBEAFE), Color(0x2E3B82F6)),
    (Color(0xFF6D28D9), Color(0xFFEDE9FE), Color(0x2E8B5CF6)),
    (Color(0xFF047857), Color(0xFFD1FAE5), Color(0x2E10B981)),
    (Color(0xFFB45309), Color(0xFFFEF3C7), Color(0x2EF59E0B)),
    (Color(0xFFBE123C), Color(0xFFFFE4E6), Color(0x2EF43F5E)),
    (Color(0xFF0369A1), Color(0xFFE0F2FE), Color(0x2E0EA5E9)),
  ];
}

extension AppThemeContext on BuildContext {
  bool get isDark => Theme.of(this).brightness == Brightness.dark;

  /// Secondary text - readable on both white cards and navy ones
  /// (replaces Colors.grey, which was too faint on white).
  Color get mutedText => isDark ? AppColors.darkMuted : AppColors.lightMuted;
}

class AppTheme {
  static ThemeData build(Branding branding, Brightness brightness) {
    final isDark = brightness == Brightness.dark;

    // fidelity keeps the company's exact hue as the seed rather than
    // drifting toward a pastel tone.
    final base = ColorScheme.fromSeed(
      seedColor: branding.primary,
      brightness: brightness,
      dynamicSchemeVariant: DynamicSchemeVariant.fidelity,
    );
    final scheme = base.copyWith(
      primary: isDark ? Color.lerp(branding.primary, Colors.white, 0.35) : branding.primary,
      onPrimary: isDark ? AppColors.ink : _onColor(branding.primary),
      secondary: branding.accent,
      onSecondary: _onColor(branding.accent),
      surface: isDark ? AppColors.darkSurface : AppColors.lightSurface,
      onSurface: isDark ? AppColors.darkText : AppColors.lightText,
      onSurfaceVariant: isDark ? AppColors.darkMuted : AppColors.lightMuted,
      surfaceContainerHighest: isDark ? AppColors.darkSurfaceHigh : const Color(0xFFEEF1F5),
      outline: isDark ? AppColors.darkBorder : const Color(0xFFD0D5DD),
      outlineVariant: isDark ? AppColors.darkBorder : AppColors.lightBorder,
    );

    final radius = BorderRadius.circular(14);
    final borderColor = isDark ? AppColors.darkBorder : AppColors.lightBorder;

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: isDark ? AppColors.darkBackground : AppColors.lightBackground,
      appBarTheme: AppBarTheme(
        centerTitle: false,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        backgroundColor: isDark ? AppColors.darkBackground : AppColors.lightBackground,
        surfaceTintColor: Colors.transparent,
        foregroundColor: scheme.onSurface,
        titleTextStyle: TextStyle(fontSize: 20, fontWeight: FontWeight.w700, letterSpacing: -0.3, color: scheme.onSurface),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: scheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16), side: BorderSide(color: borderColor)),
        margin: EdgeInsets.zero,
      ),
      listTileTheme: ListTileThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        iconColor: scheme.primary,
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        indicatorColor: branding.primary.withValues(alpha: isDark ? 0.35 : 0.14),
        iconTheme: WidgetStateProperty.resolveWith(
          (states) => IconThemeData(color: states.contains(WidgetState.selected) ? scheme.primary : scheme.onSurfaceVariant),
        ),
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => TextStyle(
            fontSize: 12,
            fontWeight: states.contains(WidgetState.selected) ? FontWeight.w700 : FontWeight.w500,
            color: states.contains(WidgetState.selected) ? scheme.primary : scheme.onSurfaceVariant,
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(borderRadius: radius),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          shape: RoundedRectangleBorder(borderRadius: radius),
          side: BorderSide(color: borderColor),
        ),
      ),
      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: branding.primary,
        foregroundColor: _onColor(branding.primary),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: isDark ? AppColors.darkSurfaceHigh : const Color(0xFFF8FAFC),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: borderColor)),
        enabledBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: borderColor)),
        focusedBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: scheme.primary, width: 1.6)),
        labelStyle: TextStyle(color: scheme.onSurfaceVariant),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: SegmentedButton.styleFrom(
          selectedBackgroundColor: branding.primary.withValues(alpha: isDark ? 0.35 : 0.14),
          selectedForegroundColor: scheme.primary,
          side: BorderSide(color: borderColor),
        ),
      ),
      dividerTheme: DividerThemeData(color: borderColor, space: 1),
      progressIndicatorTheme: ProgressIndicatorThemeData(color: scheme.primary),
    );
  }

  static Color _onColor(Color background) =>
      background.computeLuminance() > 0.45 ? AppColors.lightText : Colors.white;
}
