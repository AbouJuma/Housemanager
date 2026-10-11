import 'package:flutter/material.dart';
import '../l10n/l10n.dart';

enum ToastType { success, error, info }

/// Floating, colored message at the bottom of the screen - the app's
/// equivalent of the web app's toasts. Forms still show their inline
/// error text too; this makes sure a failure is noticed even when the
/// form is scrolled away from it.
void showToast(BuildContext context, String message, {ToastType type = ToastType.info}) {
  final (Color color, IconData icon) = switch (type) {
    ToastType.success => (const Color(0xFF12B76A), Icons.check_circle_rounded),
    ToastType.error => (const Color(0xFFF04438), Icons.error_rounded),
    ToastType.info => (Theme.of(context).colorScheme.primary, Icons.info_rounded),
  };

  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      behavior: SnackBarBehavior.floating,
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      backgroundColor: color,
      duration: Duration(seconds: type == ToastType.error ? 6 : 3),
      showCloseIcon: true,
      closeIconColor: Colors.white,
      content: Row(
        children: [
          Icon(icon, color: Colors.white),
          const SizedBox(width: 12),
          Expanded(child: Text(context.tr(message), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600))),
        ],
      ),
    ));
}
