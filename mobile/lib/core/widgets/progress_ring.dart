import 'dart:math' as math;

import 'package:flutter/material.dart';

/// Circular progress with a rounded cap and something (usually a
/// percentage) in the middle - occupancy, amount paid, etc.
class ProgressRing extends StatelessWidget {
  const ProgressRing({
    super.key,
    required this.value,
    this.size = 96,
    this.strokeWidth = 10,
    this.color,
    this.trackColor,
    this.center,
  });

  /// 0.0 - 1.0; clamped.
  final double value;
  final double size;
  final double strokeWidth;
  final Color? color;
  final Color? trackColor;
  final Widget? center;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return SizedBox.square(
      dimension: size,
      child: TweenAnimationBuilder<double>(
        tween: Tween(begin: 0, end: value.clamp(0.0, 1.0)),
        duration: const Duration(milliseconds: 900),
        curve: Curves.easeOutCubic,
        builder: (context, animated, child) => CustomPaint(
          painter: _RingPainter(
            animated,
            strokeWidth,
            color ?? scheme.primary,
            trackColor ?? scheme.surfaceContainerHighest,
          ),
          child: child,
        ),
        child: Center(child: center),
      ),
    );
  }
}

class _RingPainter extends CustomPainter {
  _RingPainter(this.value, this.strokeWidth, this.color, this.trackColor);

  final double value;
  final double strokeWidth;
  final Color color;
  final Color trackColor;

  @override
  void paint(Canvas canvas, Size size) {
    final rect = Offset.zero & size;
    final arcRect = rect.deflate(strokeWidth / 2);
    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;

    canvas.drawArc(arcRect, 0, math.pi * 2, false, paint..color = trackColor);
    if (value > 0) {
      canvas.drawArc(arcRect, -math.pi / 2, math.pi * 2 * value, false, paint..color = color);
    }
  }

  @override
  bool shouldRepaint(_RingPainter old) => old.value != value || old.color != color || old.trackColor != trackColor;
}
