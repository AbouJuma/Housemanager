import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/widgets/app_scaffold.dart';
import '../data/documents_repository.dart';

class DocumentsScreen extends ConsumerWidget {
  const DocumentsScreen({super.key, required this.role});

  final String role;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final documents = ref.watch(documentsProvider(role));

    return AppScaffold(
      title: 'Documents',
      body: AsyncListView(
        value: documents,
        emptyLabel: 'No documents yet.',
        onRetry: () => ref.invalidate(documentsProvider(role)),
        itemBuilder: (context, document) => Card(
          child: ListTile(
            leading: const Icon(Icons.description_outlined),
            title: Text(document.title),
            subtitle: document.category != null ? Text(document.category!) : null,
            trailing: document.fileUrl != null ? const Icon(Icons.open_in_new) : null,
            onTap: document.fileUrl != null ? () => launchUrl(Uri.parse(document.fileUrl!)) : null,
          ),
        ),
      ),
    );
  }
}
