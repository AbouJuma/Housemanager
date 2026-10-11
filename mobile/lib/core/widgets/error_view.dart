import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../l10n/l10n.dart';

import '../api/api_client.dart';
import '../theme/app_theme.dart';

enum ErrorKind { connection, server, notFound, forbidden, unknown }

/// What went wrong, in words a person can act on - never a raw
/// DioException / TypeError dump. The real error still goes to the
/// debug console for developers.
({ErrorKind kind, String title, String message}) describeError(Object error) {
  debugPrint('[error] $error');

  final apiError = switch (error) {
    ApiException e => e,
    DioException(error: ApiException e) => e,
    _ => null,
  };

  if (apiError != null) {
    return switch (apiError.statusCode) {
      null => (
          kind: ErrorKind.connection,
          title: "Can't connect right now",
          message: 'Check your internet connection and that the server is running, then try again.',
        ),
      403 => (kind: ErrorKind.forbidden, title: 'No access', message: "You don't have permission to view this."),
      404 => (kind: ErrorKind.notFound, title: 'Not found', message: 'This item no longer exists or was moved.'),
      >= 500 => (
          kind: ErrorKind.server,
          title: 'Server problem',
          message: "Something went wrong on our side. Please try again in a moment.",
        ),
      _ => (kind: ErrorKind.unknown, title: 'Something went wrong', message: apiError.message),
    };
  }

  if (error is DioException) {
    return (
      kind: ErrorKind.connection,
      title: "Can't connect right now",
      message: 'Check your internet connection and that the server is running, then try again.',
    );
  }

  return (
    kind: ErrorKind.unknown,
    title: 'Something went wrong',
    message: "We couldn't load this. Please try again.",
  );
}

/// Short one-line version for toasts.
String friendlyErrorMessage(Object error) => describeError(error).message;

/// Full-screen friendly error state with a Retry button.
class ErrorView extends StatelessWidget {
  const ErrorView({super.key, required this.error, this.onRetry});

  final Object error;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final described = describeError(error);
    final icon = switch (described.kind) {
      ErrorKind.connection => Icons.wifi_off_rounded,
      ErrorKind.forbidden => Icons.lock_outline_rounded,
      ErrorKind.notFound => Icons.search_off_rounded,
      _ => Icons.error_outline_rounded,
    };
    final scheme = Theme.of(context).colorScheme;

    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(color: scheme.primary.withValues(alpha: 0.1), shape: BoxShape.circle),
              child: Icon(icon, size: 34, color: scheme.primary),
            ),
            const SizedBox(height: 18),
            Text(context.tr(described.title), style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            Text(context.tr(described.message), textAlign: TextAlign.center, style: TextStyle(color: context.mutedText)),
            if (onRetry != null) ...[
              const SizedBox(height: 20),
              FilledButton.icon(
                style: FilledButton.styleFrom(minimumSize: const Size(140, 46)),
                onPressed: onRetry,
                icon: const Icon(Icons.refresh_rounded),
                label: Text(context.tr('Try again')),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
