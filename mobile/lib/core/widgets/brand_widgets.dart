import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../branding/branding.dart';
import '../theme/app_theme.dart';

/// The company's uploaded logo, or the default house mark drawn in the
/// company's own colors (same artwork as the web app's
/// public/images/brand/logo-mark.svg).
class BrandLogo extends ConsumerWidget {
  const BrandLogo({super.key, this.size = 44});

  final double size;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branding = ref.watch(brandingProvider);
    final mark = CustomPaint(size: Size.square(size), painter: _HouseMarkPainter(branding.primary, branding.accent));

    if (branding.logoUrl == null) return mark;

    return ClipRRect(
      borderRadius: BorderRadius.circular(size * 0.25),
      child: Container(
        width: size,
        height: size,
        color: Colors.white,
        child: Image.network(
          branding.logoUrl!,
          fit: BoxFit.cover,
          errorBuilder: (_, _, _) => mark,
        ),
      ),
    );
  }
}

class _HouseMarkPainter extends CustomPainter {
  _HouseMarkPainter(this.primary, this.accent);

  final Color primary;
  final Color accent;

  @override
  void paint(Canvas canvas, Size size) {
    // Drawn on the SVG's 64x64 grid, scaled to fit.
    final s = size.width / 64;
    canvas.scale(s);

    final rect = RRect.fromRectAndRadius(const Rect.fromLTWH(0, 0, 64, 64), const Radius.circular(16));
    canvas.drawRRect(
      rect,
      Paint()
        ..shader = LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color.lerp(primary, Colors.white, 0.3)!, primary, Color.lerp(primary, Colors.black, 0.14)!],
          stops: const [0, 0.45, 1],
        ).createShader(const Rect.fromLTWH(0, 0, 64, 64)),
    );

    canvas.drawCircle(const Offset(47.5, 14.5), 4.5, Paint()..color = accent);

    final stroke = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = 5
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    canvas.drawPath(
      Path()
        ..moveTo(13, 32)
        ..lineTo(32, 16.5)
        ..lineTo(51, 32),
      stroke,
    );
    canvas.drawPath(
      Path()
        ..moveTo(19, 26)
        ..lineTo(19, 46)
        ..arcToPoint(const Offset(23, 50), radius: const Radius.circular(4), clockwise: false)
        ..lineTo(41, 50)
        ..arcToPoint(const Offset(45, 46), radius: const Radius.circular(4), clockwise: false)
        ..lineTo(45, 26),
      stroke..strokeCap = StrokeCap.butt,
    );
    canvas.drawRRect(
      RRect.fromRectAndRadius(const Rect.fromLTWH(28, 37, 8, 13), const Radius.circular(2)),
      Paint()..color = Colors.white,
    );
  }

  @override
  bool shouldRepaint(_HouseMarkPainter oldDelegate) => oldDelegate.primary != primary || oldDelegate.accent != accent;
}

/// Rounded-square tinted icon - gives every list/dashboard entry its
/// own color instead of a uniform gray icon. Pass a [paletteIndex] to
/// pick from AppColors.tilePalette, or leave it null for the brand color.
class IconTile extends StatelessWidget {
  const IconTile(this.icon, {super.key, this.paletteIndex, this.size = 44});

  final IconData icon;
  final int? paletteIndex;
  final double size;

  @override
  Widget build(BuildContext context) {
    final isDark = context.isDark;
    final Color foreground;
    final Color background;

    if (paletteIndex == null) {
      final primary = Theme.of(context).colorScheme.primary;
      foreground = primary;
      background = primary.withValues(alpha: isDark ? 0.22 : 0.12);
    } else {
      final (fg, lightBg, darkBg) = AppColors.tilePalette[paletteIndex! % AppColors.tilePalette.length];
      foreground = isDark ? Color.lerp(fg, Colors.white, 0.45)! : fg;
      background = isDark ? darkBg : lightBg;
    }

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(size * 0.3)),
      child: Icon(icon, color: foreground, size: size * 0.5),
    );
  }
}

/// Full-bleed photo banner (assets/images/*.jpg) with an ink-to-brand
/// gradient so white text stays readable on any photo.
class PhotoHeader extends ConsumerWidget {
  const PhotoHeader({super.key, required this.image, required this.child, this.height = 180, this.borderRadius});

  final String image;
  final Widget child;
  final double height;
  final BorderRadius? borderRadius;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final primary = ref.watch(brandingProvider).primary;

    return ClipRRect(
      borderRadius: borderRadius ?? BorderRadius.circular(22),
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: Stack(
          fit: StackFit.expand,
          children: [
            Image.asset('assets/images/$image.jpg', fit: BoxFit.cover),
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                  colors: [
                    AppColors.ink.withValues(alpha: 0.9),
                    AppColors.ink.withValues(alpha: 0.6),
                    primary.withValues(alpha: 0.45),
                  ],
                ),
              ),
            ),
            Padding(padding: const EdgeInsets.all(20), child: child),
          ],
        ),
      ),
    );
  }
}
