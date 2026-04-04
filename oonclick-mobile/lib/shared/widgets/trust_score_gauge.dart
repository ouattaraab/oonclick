import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';

/// Jauge circulaire affichant un score de confiance de 0 à 100.
///
/// L'arc utilise le dégradé navy → sky du design system oon.click.
///
/// Exemple d'utilisation :
/// ```dart
/// TrustScoreGauge(score: 87, size: 120)
/// ```
class TrustScoreGauge extends StatelessWidget {
  const TrustScoreGauge({
    super.key,
    required this.score,
    this.size = 110,
    this.strokeWidth = 10,
  });

  /// Score entre 0 et 100.
  final int score;

  /// Diamètre total du widget (en pixels logiques).
  final double size;

  /// Épaisseur du trait de l'arc.
  final double strokeWidth;

  @override
  Widget build(BuildContext context) {
    final clampedScore = score.clamp(0, 100);

    return SizedBox(
      width: size,
      height: size,
      child: CustomPaint(
        painter: _GaugePainter(
          score: clampedScore,
          strokeWidth: strokeWidth,
          trackColor: AppColors.skyMid,
        ),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '$clampedScore',
                style: TextStyle(
                  fontSize: size * 0.26,
                  fontWeight: FontWeight.w900,
                  color: AppColors.navy,
                  height: 1.1,
                ),
              ),
              Text(
                '/100',
                style: TextStyle(
                  fontSize: size * 0.12,
                  fontWeight: FontWeight.w600,
                  color: AppColors.muted,
                  height: 1.1,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// CustomPainter
// ---------------------------------------------------------------------------

class _GaugePainter extends CustomPainter {
  _GaugePainter({
    required this.score,
    required this.strokeWidth,
    required this.trackColor,
  });

  final int score;
  final double strokeWidth;
  final Color trackColor;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (math.min(size.width, size.height) / 2) - strokeWidth / 2;

    const startAngle = math.pi * 0.75; // 135°
    const fullSweep = math.pi * 1.5;   // 270° arc total

    // --- Track (background arc) ---
    final trackPaint = Paint()
      ..color = trackColor
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round
      ..style = PaintingStyle.stroke;

    canvas.drawArc(
      Rect.fromCircle(center: center, radius: radius),
      startAngle,
      fullSweep,
      false,
      trackPaint,
    );

    // --- Filled arc with navy → sky gradient ---
    if (score > 0) {
      final sweepAngle = fullSweep * (score / 100);
      final arcRect = Rect.fromCircle(center: center, radius: radius);

      final fillPaint = Paint()
        ..shader = const LinearGradient(
          colors: [AppColors.navy, AppColors.sky],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ).createShader(arcRect)
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round
        ..style = PaintingStyle.stroke;

      canvas.drawArc(arcRect, startAngle, sweepAngle, false, fillPaint);
    }
  }

  @override
  bool shouldRepaint(_GaugePainter old) => old.score != score;
}
