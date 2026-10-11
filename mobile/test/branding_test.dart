import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/core/branding/branding.dart';
import 'package:mobile/core/theme/app_theme.dart';

void main() {
  test('parses the branding block from a login response', () {
    final branding = Branding.fromJson({
      'company_name': 'Acme Homes',
      'logo_url': null,
      'primary_color': '#7E22CE',
      'accent_color': '#F97316',
    });

    expect(branding.companyName, 'Acme Homes');
    expect(branding.primary, const Color(0xFF7E22CE));
    expect(Branding.toHex(branding.accent), '#F97316');
  });

  test('falls back to the default palette for missing or invalid colors', () {
    expect(Branding.fromJson(null).primary, Branding.defaultPrimary);
    expect(Branding.fromJson({'primary_color': 'red'}).primary, Branding.defaultPrimary);
  });

  test('a light brand color gets dark button text', () {
    final theme = AppTheme.build(Branding.fromJson({'primary_color': '#FDE047'}), Brightness.light);
    expect(theme.colorScheme.onPrimary, AppColors.lightText);

    final defaultTheme = AppTheme.build(Branding.fallback, Brightness.light);
    expect(defaultTheme.colorScheme.onPrimary, Colors.white);
  });
}
