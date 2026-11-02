import 'package:flutter/material.dart';

import '../branding/branding.dart';

/// The app's look: deep near-black in dark mode and soft gray-white in
/// light mode, large rounded cards, the company's blue and green from its
/// branding, and gold for highlights (the centre action, key figures).
class AppColors {
  static const ink = Color(0xFF0F2440);

  /// Highlight gold - the centre action button, key numbers, accents.
  static const gold = Color(0xFFE0A526);
  static const goldDeep = Color(0xFFB7791F);

  static const lightBackground = Color(0xFFF2F4F7);
  static const lightSurface = Colors.white;
  static const lightBorder = Color(0xFFE6E9EF);
  static const lightText = Color(0xFF0E1420);
  static const lightMuted = Color(0xFF5B6576);

  static const darkBackground = Color(0xFF0A0D12);
  static const darkSurface = Color(0xFF14181F);
  static const darkSurfaceHigh = Color(0xFF1C222B);
  static const darkBorder = Color(0xFF242B35);
  static const darkText = Color(0xFFEEF1F5);
  static const darkMuted = Color(0xFF9AA4B2);

  /// The floating bottom bar - dark in both modes, like a capsule.
  static const capsule = Color(0xFF12161D);

  /// Icon tiles - (foreground, light background, dark background). Kept to
  /// the brand family: blue, green, gold, teal, slate, rose.
  static const tilePalette = <(Color, Color, Color)>[
    (Color(0xFF1D4ED8), Color(0xFFDBEAFE), Color(0x2E3B82F6)),
    (Color(0xFF0F766E), Color(0xFFCCFBF1), Color(0x2E14B8A6)),
    (Color(0xFF047857), Color(0xFFD1FAE5), Color(0x2E10B981)),
    (Color(0xFFB7791F), Color(0xFFFEF3C7), Color(0x2EE0A526)),
    (Color(0xFFBE123C), Color(0xFFFFE4E6), Color(0x2EF43F5E)),
    (Color(0xFF334155), Color(0xFFE2E8F0), Color(0x2E94A3B8)),
  ];
}

extension AppThemeContext on BuildContext {
  bool get isDark => Theme.of(this).brightness == Brightness.dark;

  /// Secondary text - readable on light and dark cards.
  Color get mutedText => isDark ? AppColors.darkMuted : AppColors.lightMuted;
}

class AppTheme {
  static const fontFamily = 'PlusJakartaSans';

  static ThemeData build(Branding branding, Brightness brightness) {
    final isDark = brightness == Brightness.dark;

    // fidelity keeps the company's exact hue as the seed.
    final base = ColorScheme.fromSeed(
      seedColor: branding.primary,
      brightness: brightness,
      dynamicSchemeVariant: DynamicSchemeVariant.fidelity,
    );
    final scheme = base.copyWith(
      primary: isDark ? Color.lerp(branding.primary, Colors.white, 0.3) : branding.primary,
      onPrimary: isDark ? AppColors.ink : _onColor(branding.primary),
      secondary: branding.accent,
      onSecondary: _onColor(branding.accent),
      tertiary: AppColors.gold,
      onTertiary: AppColors.lightText,
      surface: isDark ? AppColors.darkSurface : AppColors.lightSurface,
      onSurface: isDark ? AppColors.darkText : AppColors.lightText,
      onSurfaceVariant: isDark ? AppColors.darkMuted : AppColors.lightMuted,
      surfaceContainerHighest: isDark ? AppColors.darkSurfaceHigh : const Color(0xFFEDF0F4),
      outline: isDark ? AppColors.darkBorder : const Color(0xFFD5DAE2),
      outlineVariant: isDark ? AppColors.darkBorder : AppColors.lightBorder,
    );

    final radius = BorderRadius.circular(16);
    final borderColor = isDark ? AppColors.darkBorder : AppColors.lightBorder;
    final baseText = ThemeData(brightness: brightness).textTheme.apply(
          fontFamily: fontFamily,
          bodyColor: scheme.onSurface,
          displayColor: scheme.onSurface,
        );
    final textTheme = baseText.copyWith(
      displaySmall: baseText.displaySmall?.copyWith(fontWeight: FontWeight.w800, letterSpacing: -1),
      headlineMedium: baseText.headlineMedium?.copyWith(fontWeight: FontWeight.w800, letterSpacing: -0.8),
      headlineSmall: baseText.headlineSmall?.copyWith(fontWeight: FontWeight.w800, letterSpacing: -0.5),
      titleLarge: baseText.titleLarge?.copyWith(fontWeight: FontWeight.w700, letterSpacing: -0.3),
      titleMedium: baseText.titleMedium?.copyWith(fontWeight: FontWeight.w700),
      titleSmall: baseText.titleSmall?.copyWith(fontWeight: FontWeight.w700),
      labelLarge: baseText.labelLarge?.copyWith(fontWeight: FontWeight.w600),
    );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      fontFamily: fontFamily,
      colorScheme: scheme,
      textTheme: textTheme,
      scaffoldBackgroundColor: isDark ? AppColors.darkBackground : AppColors.lightBackground,
      // Smooth fade-and-scale between pages on every platform.
      pageTransitionsTheme: const PageTransitionsTheme(builders: {
        TargetPlatform.android: FadeForwardsPageTransitionsBuilder(),
        TargetPlatform.iOS: FadeForwardsPageTransitionsBuilder(),
        TargetPlatform.windows: FadeForwardsPageTransitionsBuilder(),
        TargetPlatform.macOS: FadeForwardsPageTransitionsBuilder(),
        TargetPlatform.linux: FadeForwardsPageTransitionsBuilder(),
      }),
      appBarTheme: AppBarTheme(
        centerTitle: false,
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: isDark ? AppColors.darkBackground : AppColors.lightBackground,
        surfaceTintColor: Colors.transparent,
        foregroundColor: scheme.onSurface,
        titleTextStyle: TextStyle(fontFamily: fontFamily, fontSize: 21, fontWeight: FontWeight.w800, letterSpacing: -0.4, color: scheme.onSurface),
      ),
      cardTheme: CardThemeData(
        elevation: 0,
        color: scheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(22),
          side: isDark ? BorderSide(color: borderColor.withValues(alpha: 0.6)) : BorderSide.none,
        ),
        shadowColor: Colors.black.withValues(alpha: 0.06),
        margin: EdgeInsets.zero,
      ),
      listTileTheme: ListTileThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        iconColor: scheme.primary,
        titleTextStyle: TextStyle(fontFamily: fontFamily, fontSize: 15, fontWeight: FontWeight.w600, color: scheme.onSurface),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        indicatorColor: branding.primary.withValues(alpha: isDark ? 0.35 : 0.14),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(54),
          shape: const StadiumBorder(),
          textStyle: const TextStyle(fontFamily: fontFamily, fontSize: 16, fontWeight: FontWeight.w700),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size(0, 48),
          shape: const StadiumBorder(),
          side: BorderSide(color: borderColor),
          textStyle: const TextStyle(fontFamily: fontFamily, fontWeight: FontWeight.w600),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(textStyle: const TextStyle(fontFamily: fontFamily, fontWeight: FontWeight.w600)),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.gold,
        foregroundColor: AppColors.lightText,
        shape: CircleBorder(),
        elevation: 4,
      ),
      chipTheme: ChipThemeData(
        shape: const StadiumBorder(),
        side: BorderSide(color: borderColor),
        backgroundColor: scheme.surface,
        selectedColor: branding.primary.withValues(alpha: isDark ? 0.4 : 0.14),
        labelStyle: TextStyle(fontFamily: fontFamily, fontWeight: FontWeight.w600, color: scheme.onSurface),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: isDark ? AppColors.darkSurfaceHigh : Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 17),
        border: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: borderColor)),
        enabledBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: borderColor)),
        focusedBorder: OutlineInputBorder(borderRadius: radius, borderSide: BorderSide(color: scheme.primary, width: 1.6)),
        labelStyle: TextStyle(color: scheme.onSurfaceVariant),
      ),
      segmentedButtonTheme: SegmentedButtonThemeData(
        style: SegmentedButton.styleFrom(
          selectedBackgroundColor: branding.primary.withValues(alpha: isDark ? 0.4 : 0.14),
          selectedForegroundColor: scheme.primary,
          side: BorderSide(color: borderColor),
          shape: const StadiumBorder(),
        ),
      ),
      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: isDark ? AppColors.darkSurface : Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(32))),
        showDragHandle: true,
      ),
      dialogTheme: DialogThemeData(
        backgroundColor: isDark ? AppColors.darkSurface : Colors.white,
        surfaceTintColor: Colors.transparent,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      dividerTheme: DividerThemeData(color: borderColor, space: 1),
      progressIndicatorTheme: ProgressIndicatorThemeData(color: scheme.primary),
    );
  }

  static Color _onColor(Color background) =>
      background.computeLuminance() > 0.45 ? AppColors.lightText : Colors.white;
}
