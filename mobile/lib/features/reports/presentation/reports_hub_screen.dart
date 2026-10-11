import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/l10n/l10n.dart';

import '../../../core/widgets/app_scaffold.dart';

class ReportsHubScreen extends StatelessWidget {
  const ReportsHubScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return AppScaffold(
      title: 'Reports',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: ListTile(
              leading: const Icon(Icons.home_work_outlined),
              title: Text(context.tr('Rent Roll')),
              subtitle: Text(context.tr('Every unit, its tenant, lease dates and rent amount.')),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => context.push('/staff/more/reports/rent-roll'),
            ),
          ),
          Card(
            child: ListTile(
              leading: const Icon(Icons.pie_chart_outline),
              title: Text(context.tr('Occupancy')),
              subtitle: Text(context.tr('Occupied vs. vacant units, per property.')),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => context.push('/staff/more/reports/occupancy'),
            ),
          ),
        ],
      ),
    );
  }
}
