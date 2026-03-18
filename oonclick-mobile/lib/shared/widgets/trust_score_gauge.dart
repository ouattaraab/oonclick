import 'dart:math' as math;

import 'package:flutter/material.dart';

/// Jauge circulaire affichant un score de confiance de 0 à 100.
///
/// - score >= 70 → vert   (#00C853)
/// - score >= 40 → orange (#FF6B00)
/// - score <  40 → rouge  (#F44336)
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

  // ---------------------------------------------------------------------------
  // Couleur selon le score
  // ---------------------------------------------------------------------------

  static const Color _colorHigh = Color(0xFF00C853);
  static const Color _colorMid = Color(0xFFFF6B00);
  static const Color _colorLow = Color(0xFFF44336);

  Color get _gaugeColor {
    if (score >= 70) return _colorHigh;
    if (score >= 40) return _colorMid;
    return _colorLow;
  }

  @override
  Widget build(BuildContext context) {
    final clampedScore = score.clamp(0, 100);

    return SizedBox(
      width: size,
      height: size,
      child: CustomPaint(
        painter: _GaugePainter(
          score: clampedScore,
          color: _gaugeColor,
          strokeWidth: strokeWidth,
          trackColor: const Color(0xFFE9ECEF),
        ),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '$clampedScore',
                style: TextStyle(
                  fontSize: size * 0.26,
                  fontWeight: FontWeight.w700,
                  color: _gaugeColor,
                  height: 1.1,
                ),
              ),
              Text(
                '/100',
                style: TextStyle(
                  fontSize: size * 0.12,
                  fontWeight: FontWeight.w500,
                  color: const Color(0xFF6B7280),
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
    required this.color,
    required this.strokeWidth,
    required this.trackColor,
  });

  final int score;
  final Color color;
  final double strokeWidth;
  final Color trackColor;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = (math.min(size.width, size.height) / 2) - strokeWidth / 2;

    const startAngle = math.pi * 0.75;   // 135°
    const fullSweep = math.pi * 1.5;     // 270° de plage totale

    // --- Piste de fond ---
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

    // --- Arc rempli selon le score ---
    if (score > 0) {
      final sweepAngle = fullSweep * (score / 100);
      final fillPaint = Paint()
        ..color = color
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.round
        ..style = PaintingStyle.stroke;

      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        startAngle,
        sweepAngle,
        false,
        fillPaint,
      );
    }
  }

  @override
  bool shouldRepaint(_GaugePainter old) =>
      old.score != score || old.color != color;
}
