// Every screen and route of the app, for every role, against the REAL
// backend responses captured by tests/Feature/MobileApi/AppContractTest.php
// (mobile/test/fixtures/api/*.json - regenerate by running that PHP test).
//
// For each role: sign in (faked), open every route the role has - lists,
// details, forms - and fail if a screen throws, shows an error state, or
// the route doesn't resolve. Then scan lib/ for every navigation target
// and check each one resolves for its role.
import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile/core/api/api_client.dart';
import 'package:mobile/core/api/token_storage.dart';
import 'package:mobile/core/branding/branding.dart';
import 'package:mobile/core/l10n/l10n.dart';
import 'package:mobile/core/providers.dart';
import 'package:mobile/core/router/app_router.dart';
import 'package:mobile/core/theme/app_theme.dart';
import 'package:mobile/core/widgets/error_view.dart';
import 'package:mobile/features/auth/data/auth_repository.dart';
import 'package:mobile/features/auth/providers/auth_provider.dart';

const fixtureDir = 'test/fixtures/api';

Future<void> loadFont(String family, List<String> files) async {
  final dir =
      '${Platform.environment['FLUTTER_ROOT']}/bin/cache/artifacts/material_fonts';
  final loader = FontLoader(family);
  for (final file in files) {
    loader.addFont(
      Future.value(ByteData.view(File('$dir/$file').readAsBytesSync().buffer)),
    );
  }
  await loader.load();
}

dynamic fixture(String name) =>
    jsonDecode(File('$fixtureDir/$name.json').readAsStringSync());

String firstUuid(String name) {
  final data = (fixture(name) as Map<String, dynamic>)['data'];
  return ((data is List ? data.first : data) as Map<String, dynamic>)['uuid']
      as String;
}

/// Serves each API path from its captured fixture.
class FixtureAdapter implements HttpClientAdapter {
  final List<String> unmatched = [];

  /// Every request made, as `METHOD /path`.
  final List<String> calls = [];

  /// Bodies of the non-GET requests, by `METHOD /path`.
  final Map<String, dynamic> sent = {};

  static final _uuid = RegExp(
    r'/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
  );

  static const _routes = {
    'GET /portal/tenant/dashboard': 'tenant_dashboard',
    'GET /portal/tenant/leases': 'tenant_leases',
    'GET /portal/tenant/leases/{id}': 'tenant_lease',
    'GET /portal/tenant/invoices': 'tenant_invoices',
    'GET /portal/tenant/invoices/{id}': 'tenant_invoice',
    'GET /portal/tenant/maintenance-requests': 'tenant_maintenance',
    'GET /portal/tenant/maintenance-requests/{id}': 'tenant_maintenance_detail',
    'GET /portal/tenant/documents': 'tenant_documents',
    'GET /portal/landlord/dashboard': 'landlord_dashboard',
    'GET /portal/landlord/properties': 'landlord_properties',
    'GET /portal/landlord/properties/{id}': 'landlord_property',
    'GET /portal/landlord/expenses': 'landlord_expenses',
    'GET /portal/landlord/maintenance-requests': 'landlord_maintenance',
    'GET /portal/landlord/documents': 'landlord_documents',
    'GET /portal/landlord/tenants': 'landlord_tenants',
    'PUT /portal/landlord/tenants/{id}': 'landlord_tenant',
    'GET /portal/landlord/invoices': 'landlord_invoices',
    'GET /portal/landlord/expense-options': 'landlord_expense_options',
    'POST /portal/landlord/expenses': 'landlord_expense_saved',
    'PUT /portal/landlord/expenses/{id}': 'landlord_expense_saved',
    'GET /portal/landlord/buyers': 'landlord_buyers',
    'GET /portal/landlord/buyers/{id}': 'landlord_buyer',
    'PUT /portal/landlord/buyers/{id}': 'landlord_buyer',
    'POST /portal/landlord/properties/{id}/buildings': 'landlord_building_created',
    'POST /portal/landlord/buildings/{id}/units': 'landlord_unit_saved',
    'PUT /portal/landlord/units/{id}': 'landlord_unit_saved',
    'POST /vendors': 'module_vendors_detail',
    'PUT /vendors/{id}': 'module_vendors_detail',
    'DELETE /vendors/{id}': 'empty',
    'GET /portal/landlord/tenants/{id}': 'landlord_tenant',
    'POST /portal/landlord/tenants': 'landlord_tenant_created',
    'GET /portal/landlord/reports/profit-loss': 'landlord_profit_loss',
    'GET /portal/landlord/reports/units': 'landlord_units',
    'GET /portal/landlord/property-options': 'landlord_property_options',
    'POST /portal/landlord/properties': 'landlord_property_created',
    'PUT /portal/landlord/properties/{id}': 'landlord_property_created',
    'GET /portal/buyer/dashboard': 'buyer_dashboard',
    'GET /portal/buyer/sales': 'buyer_sales',
    'GET /portal/buyer/sales/{id}': 'buyer_sale',
    'GET /portal/buyer/invoices': 'buyer_invoices',
    'GET /portal/buyer/invoices/{id}': 'buyer_invoice',
    'GET /tenants': 'staff_tenants',
    'GET /tenants/{id}': 'staff_tenant',
    'GET /leases': 'staff_leases',
    'GET /leases/{id}': 'staff_lease',
    'GET /invoices': 'staff_invoices',
    'GET /invoices/{id}': 'staff_invoice',
    'GET /properties': 'staff_properties',
    'GET /properties/{id}/buildings': 'staff_buildings',
    'GET /buildings/{id}/units': 'staff_units',
    'GET /reports/rent-roll': 'staff_rent_roll',
    'GET /reports/occupancy': 'staff_occupancy',
    'GET /properties/{id}': 'module_properties_detail',
    'GET /maintenance-requests': 'module_maintenance',
    'GET /maintenance-requests/{id}': 'module_maintenance_detail',
    'PATCH /maintenance-requests/{id}/status': 'module_maintenance_status_updated',
    'GET /expenses': 'module_expenses',
    'GET /expenses/{id}': 'module_expenses_detail',
    'POST /expenses/{id}/mark-paid': 'module_expense_marked_paid',
    'GET /visitors': 'module_visitors',
    'GET /visitors/{id}': 'module_visitors_detail',
    'POST /visitors/{id}/check-out': 'module_visitor_checked_out',
    'GET /vendors': 'module_vendors',
    'GET /vendors/{id}': 'module_vendors_detail',
    'GET /inventory-items': 'module_inventory',
    'GET /inventory-items/{id}': 'module_inventory_detail',
    'GET /documents': 'module_documents',
    'GET /documents/{id}': 'module_documents_detail',
    'GET /auth/me': 'staff_account',
    'GET /portal/tenant/profile': 'tenant_account',
    'GET /portal/landlord/profile': 'landlord_account',
    'GET /portal/buyer/profile': 'buyer_account',
    'GET /notifications': 'staff_notifications',
    'GET /portal/tenant/notifications': 'tenant_notifications',
    'GET /portal/landlord/notifications': 'landlord_notifications',
    'GET /portal/buyer/notifications': 'buyer_notifications',
    'POST /tenants': 'staff_tenant_created',
    'PUT /tenants/{id}': 'staff_tenant_created',
    'POST /leases': 'staff_lease_created',
    'PUT /leases/{id}': 'staff_lease_created',
    'POST /invoices': 'staff_invoice_created',
    'POST /invoices/{id}/payments': 'staff_payment_recorded',
  };

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    final key = '${options.method} ${options.path.replaceAll(_uuid, '/{id}')}';
    calls.add(key);
    if (options.method != 'GET') sent[key] = options.data;
    final name = _routes[key];
    if (name == null) {
      unmatched.add(key);
      return ResponseBody.fromString(
        '{"message":"no fixture"}',
        404,
        headers: {
          Headers.contentTypeHeader: ['application/json'],
        },
      );
    }
    return ResponseBody.fromString(
      File('$fixtureDir/$name.json').readAsStringSync(),
      200,
      headers: {
        Headers.contentTypeHeader: ['application/json'],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

class FakeAuth extends AuthNotifier {
  FakeAuth(this.role, {this.permissions});
  final UserRole role;

  /// Staff/landlord permissions; default to the real ones from the captured login.
  final List<String>? permissions;

  @override
  AuthState build() => AuthState.signedIn(
    role,
    AuthenticatedProfile(
      name: 'Test Person',
      email: 'test@example.com',
      username: 'test.person',
      permissions: role == UserRole.staff || role == UserRole.landlord
          ? (permissions ?? ((fixture('login_${role.name}') as Map<String, dynamic>)['permissions'] as List).cast<String>())
          : const [],
    ),
  );
}

class FixedBranding extends BrandingNotifier {
  @override
  Branding build() => Branding.fallback;
}

class FixedThemeMode extends ThemeModeNotifier {
  @override
  ThemeMode build() => ThemeMode.light;
}

/// Every location each role can reach, with real ids from the fixtures.
Map<UserRole, List<String>> locations() => {
  UserRole.tenant: [
    '/dashboard',
    '/tenant/leases',
    '/tenant/leases/${firstUuid('tenant_leases')}',
    '/tenant/invoices',
    '/tenant/invoices/${firstUuid('tenant_invoices')}',
    '/tenant/maintenance',
    '/tenant/maintenance/new',
    '/tenant/maintenance/${firstUuid('tenant_maintenance')}',
    '/tenant/more',
    '/tenant/more/documents',
    '/tenant/more/profile',
    '/notifications',
  ],
  UserRole.landlord: [
    '/dashboard',
    '/landlord/properties',
    '/landlord/properties/${firstUuid('landlord_properties')}',
    '/landlord/properties/new',
    '/landlord/properties/${firstUuid('landlord_properties')}/edit',
    '/landlord/tenants',
    '/landlord/tenants/new',
    '/landlord/tenants/${firstUuid('landlord_tenants')}',
    '/landlord/reports',
    '/landlord/reports/profit-loss',
    '/landlord/reports/units',
    '/landlord/more',
    '/landlord/more/expenses',
    '/landlord/more/invoices',
    '/landlord/more/buyers',
    '/landlord/more/buyers/${firstUuid('landlord_buyers')}',
    '/landlord/more/maintenance',
    '/landlord/more/documents',
    '/landlord/more/profile',
    '/notifications',
  ],
  UserRole.buyer: [
    '/dashboard',
    '/buyer/sales',
    '/buyer/sales/${firstUuid('buyer_sales')}',
    '/buyer/invoices',
    '/buyer/invoices/${firstUuid('buyer_invoices')}',
    '/buyer/more',
    '/buyer/more/profile',
    '/notifications',
  ],
  UserRole.staff: [
    '/dashboard',
    '/staff/tenants',
    '/staff/tenants/new',
    '/staff/tenants/${firstUuid('staff_tenants')}',
    '/staff/tenants/${firstUuid('staff_tenants')}/edit',
    '/staff/leases',
    '/staff/leases/new',
    '/staff/leases/${firstUuid('staff_leases')}',
    '/staff/leases/${firstUuid('staff_leases')}/edit',
    '/staff/invoices',
    '/staff/invoices/new',
    '/staff/invoices/${firstUuid('staff_invoices')}',
    '/staff/more',
    '/staff/more/reports',
    '/staff/more/reports/rent-roll',
    '/staff/more/reports/occupancy',
    '/staff/more/profile',
    for (final module in ['maintenance', 'expenses', 'visitors', 'inventory', 'properties', 'vendors', 'documents']) ...[
      '/staff/more/$module',
      '/staff/more/$module/${firstUuid('module_$module')}',
    ],
    '/notifications',
  ],
};

Future<(GoRouter, FixtureAdapter)> pumpApp(
  WidgetTester tester,
  UserRole role, {
  Locale locale = const Locale('en'),
  List<String>? permissions,
}) async {
  tester.view.physicalSize = const Size(1170, 2532);
  tester.view.devicePixelRatio = 3;
  addTearDown(tester.view.reset);

  final adapter = FixtureAdapter();
  final container = ProviderContainer(
    overrides: [
      authProvider.overrideWith(() => FakeAuth(role, permissions: permissions)),
      brandingProvider.overrideWith(FixedBranding.new),
      themeModeProvider.overrideWith(FixedThemeMode.new),
      apiClientProvider.overrideWith((ref) {
        final client = ApiClient(
          TokenStorage(const FlutterSecureStorage()),
          'http://test/api/v1',
        );
        client.dio.httpClientAdapter = adapter;
        return client;
      }),
    ],
  );
  addTearDown(container.dispose);

  final router = container.read(routerProvider);
  await tester.pumpWidget(
    UncontrolledProviderScope(
      container: container,
      child: MaterialApp.router(
        theme: AppTheme.build(Branding.fallback, Brightness.light),
        locale: locale,
        supportedLocales: supportedLocales,
        localizationsDelegates: GlobalMaterialLocalizations.delegates,
        routerConfig: router,
      ),
    ),
  );
  await tester.pumpAndSettle();
  return (router, adapter);
}

/// Literal app routes used with go()/push()/route: anywhere in lib/.
Set<String> codeTargets() {
  final pattern = RegExp(
    r"""['"](/(dashboard|tenant|landlord|buyer|staff)[a-z0-9/_-]*)['"]""",
  );
  return {
    for (final file
        in Directory('lib')
            .listSync(recursive: true)
            .whereType<File>()
            .where((f) => f.path.endsWith('.dart')))
      for (final match in pattern.allMatches(file.readAsStringSync()))
        match.group(1)!,
  };
}

String currentPath(GoRouter router) =>
    router.routerDelegate.currentConfiguration.uri.path;

void main() {
  setUpAll(() async {
    FlutterSecureStorage.setMockInitialValues({});
    // Real fonts, so overflow checks reflect real text widths (the test
    // default font draws every glyph as a full square).
    await loadFont('Roboto', [
      'roboto-regular.ttf',
      'roboto-medium.ttf',
      'roboto-bold.ttf',
      'roboto-black.ttf',
    ]);
    await loadFont('MaterialIcons', ['materialicons-regular.otf']);
  });

  for (final language in ['en', 'sw']) {
    for (final role in UserRole.values) {
      testWidgets('every ${role.name} screen loads without errors [$language]', (
        tester,
      ) async {
        final (router, adapter) = await pumpApp(
          tester,
          role,
          locale: Locale(language),
        );
        final problems = <String>[];

        // Plus every navigation target written in lib/ for this role, so a
        // tile or link pointing at a route that doesn't exist is caught.
        final prefix = role == UserRole.staff ? '/staff' : '/${role.name}';
        final targets = codeTargets().where(
          (t) => t == '/dashboard' || t == prefix || t.startsWith('$prefix/'),
        );

        for (final location in {...locations()[role]!, ...targets}) {
          router.go(location);
          await tester.pumpAndSettle(
            const Duration(milliseconds: 100),
            EnginePhase.sendSemanticsUpdate,
            const Duration(seconds: 10),
          );

          final exception = tester.takeException();
          if (exception != null) problems.add('$location threw: $exception');
          if (currentPath(router) != location) {
            problems.add('$location redirected to ${currentPath(router)} (route missing)');
          }
          if (find.byType(ErrorView).evaluate().isNotEmpty) {
            problems.add('$location shows an error state');
          }
        }

        if (language == 'sw') {
          // The bottom bar's first tab, in Swahili.
          router.go('/dashboard');
          await tester.pumpAndSettle();
          expect(
            find.text('Nyumbani'),
            findsWidgets,
            reason: 'Swahili labels not shown',
          );
        }

        expect(
          adapter.unmatched,
          isEmpty,
          reason: 'API calls with no captured response: ${adapter.unmatched}',
        );
        expect(problems, isEmpty, reason: problems.join('\n'));
      });
    }
  }
  testWidgets('a staff role with only invoice access sees just those tabs and actions', (tester) async {
    final (router, _) = await pumpApp(tester, UserRole.staff, permissions: ['invoices.viewAny']);

    expect(find.text('Invoices'), findsWidgets);
    expect(find.text('Tenants'), findsNothing);
    expect(find.text('Leases'), findsNothing);
    // No create rights -> no + button on the dashboard.
    expect(find.byIcon(Icons.add), findsNothing);

    // A route outside the role's tabs doesn't open.
    router.go('/staff/tenants');
    await tester.pumpAndSettle();
    expect(currentPath(router), isNot('/staff/tenants'));

    router.go('/staff/invoices');
    await tester.pumpAndSettle();
    expect(currentPath(router), '/staff/invoices');
    expect(find.byType(FloatingActionButton), findsNothing, reason: 'no invoices.create -> no add button');
  });

  testWidgets('the notification inbox shows access changes with an unread badge', (tester) async {
    final (router, _) = await pumpApp(tester, UserRole.staff);

    expect(find.text('1'), findsWidgets, reason: 'unread badge on the bell');

    router.go('/notifications');
    await tester.pumpAndSettle();
    expect(find.text('Your access was updated'), findsOneWidget);
    expect(find.textContaining('Invoices: create'), findsOneWidget);
  });

  // --- Roles a company invents: what the app shows follows permissions.

  Future<void> openPath(WidgetTester tester, GoRouter router, String path) async {
    router.go(path);
    await tester.pumpAndSettle();
    expect(currentPath(router), path);
    expect(find.byType(ErrorView), findsNothing);
  }

  List<String> tabLabels(WidgetTester tester) => [
        for (final d in tester.widgetList<NavigationDestination>(find.byType(NavigationDestination))) d.label,
      ];

  testWidgets('a cleaner/caretaker gets maintenance and can update a job', (tester) async {
    final (router, adapter) = await pumpApp(
      tester,
      UserRole.staff,
      permissions: ['maintenance.viewAny', 'maintenance.view', 'maintenance.update'],
    );

    expect(tabLabels(tester), ['Home', 'Maintenance', 'More']);
    expect(find.text('Repairs & job status'), findsOneWidget, reason: 'dashboard tile');

    // Nothing else opens.
    for (final other in ['/staff/tenants', '/staff/invoices', '/staff/more/expenses', '/staff/more/reports']) {
      router.go(other);
      await tester.pumpAndSettle();
      expect(currentPath(router), isNot(other));
    }

    await openPath(tester, router, '/staff/maintenance');
    expect(find.text('Leaking tap'), findsOneWidget);

    await openPath(tester, router, '/staff/maintenance/${firstUuid('module_maintenance')}');
    await tester.tap(find.text('Update status'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(ChoiceChip, 'Completed'));
    await tester.enterText(find.byType(TextField), 'Washer replaced');
    await tester.tap(find.widgetWithText(FilledButton, 'Save'));
    await tester.pumpAndSettle();

    final key = 'PATCH /maintenance-requests/{id}/status';
    expect(adapter.sent[key], {'status': 'completed', 'notes': 'Washer replaced'});
    expect(adapter.unmatched, isEmpty);
  });

  testWidgets('a maintenance viewer without update rights only reads', (tester) async {
    final (router, _) = await pumpApp(tester, UserRole.staff, permissions: ['maintenance.viewAny', 'maintenance.view']);

    await openPath(tester, router, '/staff/maintenance/${firstUuid('module_maintenance')}');
    expect(find.text('Leaking tap'), findsOneWidget);
    expect(find.text('Update status'), findsNothing);
  });

  testWidgets('an accountant gets invoices and expenses as tabs and reports under More', (tester) async {
    final (router, adapter) = await pumpApp(
      tester,
      UserRole.staff,
      permissions: ['invoices.viewAny', 'invoices.view', 'invoices.create', 'expenses.viewAny', 'expenses.view', 'expenses.update', 'reports.view'],
    );

    expect(tabLabels(tester), ['Home', 'Invoices', 'Expenses', 'More']);

    await openPath(tester, router, '/staff/more');
    expect(find.text('Reports'), findsOneWidget);
    await openPath(tester, router, '/staff/more/reports');

    await openPath(tester, router, '/staff/expenses/${firstUuid('module_expenses')}');
    await tester.tap(find.text('Mark as paid'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Mark as paid').last);
    await tester.pumpAndSettle();

    final body = adapter.sent['POST /expenses/{id}/mark-paid'] as Map;
    expect(body['payment_method'], 'cash');
    expect(body['paid_on'], matches(RegExp(r'^\d{4}-\d{2}-\d{2}$')));
    expect(adapter.unmatched, isEmpty);
  });

  testWidgets('security gets the visitor log and can check visitors out', (tester) async {
    final (router, adapter) = await pumpApp(
      tester,
      UserRole.staff,
      permissions: ['visitors.viewAny', 'visitors.view', 'visitors.update'],
    );

    expect(tabLabels(tester), ['Home', 'Visitors', 'More']);

    await openPath(tester, router, '/staff/visitors/${firstUuid('module_visitors')}');
    await tester.tap(find.text('Check out'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Check out').last);
    await tester.pumpAndSettle();

    expect(adapter.calls, contains('POST /visitors/{id}/check-out'));
    expect(adapter.unmatched, isEmpty);
  });

  testWidgets('a storekeeper with many features keeps three tabs and the rest under More', (tester) async {
    final (router, _) = await pumpApp(
      tester,
      UserRole.staff,
      permissions: ['inventory.viewAny', 'vendors.viewAny', 'properties.viewAny', 'documents.viewAny', 'maintenance.viewAny'],
    );

    expect(tabLabels(tester), ['Home', 'Maintenance', 'Inventory', 'Properties', 'More']);

    await openPath(tester, router, '/staff/more');
    expect(find.text('Vendors'), findsOneWidget);
    expect(find.text('Documents'), findsOneWidget);
    await openPath(tester, router, '/staff/more/vendors/${firstUuid('module_vendors')}');
    await openPath(tester, router, '/staff/inventory');
  });

  testWidgets('a role with no app permissions sees a friendly note, not errors', (tester) async {
    await pumpApp(tester, UserRole.staff, permissions: ['settings.view']);

    expect(tabLabels(tester), ['Home', 'More']);
    expect(find.text('Your role has no app features yet.'), findsOneWidget);
  });

  // --- Landlords: what they see follows the company's "Landlord" role.

  testWidgets('a landlord manages properties and tenants and sees their reports', (tester) async {
    final (router, adapter) = await pumpApp(tester, UserRole.landlord);

    expect(tabLabels(tester), ['Home', 'Properties', 'Tenants', 'Reports', 'More']);

    // Add a property.
    await openPath(tester, router, '/landlord/properties');
    expect(find.byType(FloatingActionButton), findsOneWidget);
    await openPath(tester, router, '/landlord/properties/new');
    await tester.enterText(find.byType(TextFormField).first, 'Juma Towers');
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Add property'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Add property'));
    await tester.pumpAndSettle();
    expect((adapter.sent['POST /portal/landlord/properties'] as Map)['name'], 'Juma Towers');

    // Add a tenant - the login details are shown once.
    await openPath(tester, router, '/landlord/tenants/new');
    await tester.enterText(find.widgetWithText(TextFormField, 'Full name'), 'Neema Mpangaji');
    await tester.enterText(find.widgetWithText(TextFormField, 'Phone'), '0716000555');
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Add tenant'));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Add tenant'));
    await tester.pumpAndSettle();
    expect((adapter.sent['POST /portal/landlord/tenants'] as Map)['phone'], '0716000555');
    expect(find.text('Tenant added'), findsOneWidget);
    expect(find.textContaining('neema'), findsWidgets);
    await tester.tap(find.text('Done'));
    await tester.pumpAndSettle();

    // Reports: profit & loss and rented / free units.
    await openPath(tester, router, '/landlord/reports/profit-loss');
    expect(find.text('Income received'), findsOneWidget);
    await openPath(tester, router, '/landlord/reports/units');
    expect(find.text('Rented'), findsWidgets);
    expect(find.text('Free'), findsWidgets);
    expect(adapter.unmatched, isEmpty);
  });

  testWidgets('a landlord role without tenants or reports hides them', (tester) async {
    final (router, _) = await pumpApp(tester, UserRole.landlord, permissions: ['properties.viewAny']);

    expect(tabLabels(tester), ['Home', 'Properties', 'Reports', 'More']);
    expect(find.text('Tenants'), findsNothing);

    await openPath(tester, router, '/landlord/properties');
    expect(find.byType(FloatingActionButton), findsNothing, reason: 'no properties.create');

    await openPath(tester, router, '/landlord/properties/${firstUuid('landlord_properties')}');
    expect(find.byIcon(Icons.edit_outlined), findsNothing, reason: 'no properties.update');

    await openPath(tester, router, '/landlord/reports');
    expect(find.text('Units'), findsWidgets);
    expect(find.text('Profit & Loss'), findsNothing, reason: 'no reports.view');

    for (final blocked in ['/landlord/tenants', '/landlord/more/expenses', '/landlord/more/documents']) {
      router.go(blocked);
      await tester.pumpAndSettle();
      expect(currentPath(router), isNot(blocked));
    }
  });

  // --- Landlords: buildings, units, expenses, buyers, invoices; vendors for staff.

  testWidgets('a landlord adds a unit and records an expense', (tester) async {
    final (router, adapter) = await pumpApp(tester, UserRole.landlord);

    // Add a unit to the property's first building.
    await openPath(tester, router, '/landlord/properties/${firstUuid('landlord_properties')}');
    await tester.tap(find.text('Add unit').first);
    await tester.pumpAndSettle();
    await tester.enterText(find.widgetWithText(TextFormField, 'Unit number'), 'Z9');
    await tester.tap(find.byType(DropdownButtonFormField<String>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Apartment').last);
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Add unit'));
    await tester.tap(find.widgetWithText(FilledButton, 'Add unit'));
    await tester.pumpAndSettle();
    expect((adapter.sent['POST /portal/landlord/buildings/{id}/units'] as Map)['unit_number'], 'Z9');

    // Record an expense (once the "Unit added" toast has gone).
    await tester.pump(const Duration(seconds: 6));
    await tester.pumpAndSettle();
    await openPath(tester, router, '/landlord/more/expenses');
    expect(find.text('Total spent'), findsOneWidget);
    await tester.tap(find.byType(FloatingActionButton));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(DropdownButtonFormField<int>, 'Property'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Juma Towers').last);
    await tester.pumpAndSettle();
    await tester.enterText(find.widgetWithText(TextFormField, 'Description'), 'Gate repair');
    await tester.enterText(find.widgetWithText(TextFormField, 'Amount'), '150000');
    await tester.ensureVisible(find.widgetWithText(DropdownButtonFormField<int>, 'Category'));
    await tester.tap(find.widgetWithText(DropdownButtonFormField<int>, 'Category'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Repairs').last);
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Add expense'));
    await tester.tap(find.widgetWithText(FilledButton, 'Add expense'));
    await tester.pumpAndSettle();
    final sent = adapter.sent['POST /portal/landlord/expenses'] as Map;
    expect(sent['description'], 'Gate repair');
    expect(sent['amount'], 150000);
    expect(sent['payment_status'], 'unpaid');
    expect(adapter.unmatched, isEmpty);
  });

  testWidgets('a landlord sees pending invoices and edits a buyer', (tester) async {
    final (router, adapter) = await pumpApp(tester, UserRole.landlord);

    await openPath(tester, router, '/landlord/more/invoices');
    expect(find.text('Pending'), findsOneWidget);
    expect(find.text('Still owed · 5 invoices'), findsOneWidget);

    await openPath(tester, router, '/landlord/more/buyers/${firstUuid('landlord_buyers')}');
    await tester.tap(find.byIcon(Icons.edit_outlined));
    await tester.pumpAndSettle();
    await tester.enterText(find.widgetWithText(TextFormField, 'Full name'), 'Asha B.');
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Save changes'));
    await tester.tap(find.widgetWithText(FilledButton, 'Save changes'));
    await tester.pumpAndSettle();
    expect((adapter.sent['PUT /portal/landlord/buyers/{id}'] as Map)['name'], 'Asha B.');
  });

  testWidgets('a landlord without the new rights sees none of those buttons', (tester) async {
    final (router, _) = await pumpApp(tester, UserRole.landlord, permissions: ['properties.viewAny', 'expenses.viewAny']);

    await openPath(tester, router, '/landlord/properties/${firstUuid('landlord_properties')}');
    expect(find.text('Add unit'), findsNothing);
    expect(find.text('Add building'), findsNothing);
    await openPath(tester, router, '/landlord/more/expenses');
    expect(find.byType(FloatingActionButton), findsNothing);
    for (final blocked in ['/landlord/more/invoices', '/landlord/more/buyers']) {
      router.go(blocked);
      await tester.pumpAndSettle();
      expect(currentPath(router), isNot(blocked));
    }
  });

  testWidgets('staff add, edit and delete vendors', (tester) async {
    final (router, adapter) = await pumpApp(tester, UserRole.staff);

    await openPath(tester, router, '/staff/more/vendors');
    await tester.tap(find.byType(FloatingActionButton));
    await tester.pumpAndSettle();
    await tester.enterText(find.widgetWithText(TextFormField, 'Name'), 'Bomba Plumbers');
    await tester.tap(find.widgetWithText(DropdownButtonFormField<String>, 'Category'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Plumbing').last);
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Add vendor'));
    await tester.tap(find.widgetWithText(FilledButton, 'Add vendor'));
    await tester.pumpAndSettle();
    expect((adapter.sent['POST /vendors'] as Map)['category'], 'plumbing');

    await openPath(tester, router, '/staff/more/vendors/${firstUuid('module_vendors')}');
    await tester.tap(find.byIcon(Icons.edit_outlined));
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Save changes'));
    await tester.tap(find.widgetWithText(FilledButton, 'Save changes'));
    await tester.pumpAndSettle();
    expect(adapter.calls, contains('PUT /vendors/{id}'));

    await tester.tap(find.byIcon(Icons.delete_outline));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Delete'));
    await tester.pumpAndSettle();
    expect(adapter.calls, contains('DELETE /vendors/{id}'));
    expect(adapter.unmatched, isEmpty);
  });

  testWidgets('lists offer today / this week / this month / this year filters', (tester) async {
    final (router, adapter) = await pumpApp(tester, UserRole.tenant);

    await openPath(tester, router, '/tenant/invoices');
    expect(find.text('This week'), findsOneWidget);
    await tester.tap(find.text('This month'));
    await tester.pumpAndSettle();
    expect(adapter.calls.where((c) => c == 'GET /portal/tenant/invoices').length, greaterThan(1), reason: 'reloaded with the period');
  });
}
