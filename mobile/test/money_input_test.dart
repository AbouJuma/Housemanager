import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/core/api/server_config.dart';
import 'package:mobile/core/widgets/money_input.dart';

void main() {
  test('groups thousands as you type and keeps up to two decimals', () {
    expect(formatMoneyInput('1250000'), '1,250,000');
    expect(formatMoneyInput('1,250,000.5'), '1,250,000.5');
    expect(formatMoneyInput('1250000.50'), '1,250,000.50');
    expect(formatMoneyInput('.5'), '0.5');
    expect(formatMoneyInput('12.345'), isNull); // third decimal rejected
    expect(formatMoneyInput('12a'), isNull);
  });

  test('parses grouped amounts back to numbers', () {
    expect(parseMoney('1,250,000.50'), 1250000.50);
    expect(parseMoney(''), isNull);
  });

  test('debug builds talk to the local server; release builds use the live one', () {
    // Tests run as a debug build.
    expect(apiBaseUrl(), localApiBaseUrl());
    expect(liveApiBaseUrl, 'https://housemanager.ecofieldgroup.com/api/v1');
  });
}
