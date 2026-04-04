import 'package:flutter/material.dart';

/// Sky Gradient design system colour palette for oon.click.
///
/// All colours are static constants — reference them as [AppColors.sky],
/// [AppColors.navy], etc. throughout the entire codebase.
abstract final class AppColors {
  AppColors._();

  // ---------------------------------------------------------------------------
  // Sky family
  // ---------------------------------------------------------------------------

  /// Primary sky blue — main brand colour.
  static const Color sky = Color(0xFF2AABF0);

  /// Sky variant 2 — slightly darker, used for hover/links.
  static const Color sky2 = Color(0xFF1A95D8);

  /// Sky variant 3 — darkest sky, used in gradients.
  static const Color sky3 = Color(0xFF0E7AB8);

  /// Very light sky — chip/tag backgrounds.
  static const Color skyPale = Color(0xFFEBF7FE);

  /// Medium sky — prefix backgrounds, dividers.
  static const Color skyMid = Color(0xFFC5E8FA);

  // ---------------------------------------------------------------------------
  // Navy family
  // ---------------------------------------------------------------------------

  /// Primary navy — headers, dark surfaces.
  static const Color navy = Color(0xFF1B2A6E);

  /// Navy variant 2 — slightly darker navy.
  static const Color navy2 = Color(0xFF162058);

  // ---------------------------------------------------------------------------
  // Utility
  // ---------------------------------------------------------------------------

  /// Border / divider colour.
  static const Color border = Color(0xFFC8E4F6);

  /// Muted text / secondary label.
  static const Color muted = Color(0xFF5A7098);

  /// App background (very light blue-white).
  static const Color bg = Color(0xFFF0F8FF);

  // ---------------------------------------------------------------------------
  // Semantic
  // ---------------------------------------------------------------------------

  /// Success green.
  static const Color success = Color(0xFF16A34A);

  /// Success background (light green tint).
  static const Color successLight = Color(0xFFDCFCE7);

  /// Warning amber.
  static const Color warn = Color(0xFFD97706);

  /// Warning background (light amber tint).
  static const Color warnLight = Color(0xFFFEF3C7);

  /// Danger red.
  static const Color danger = Color(0xFFDC2626);

  /// Danger background (light red tint).
  static const Color dangerLight = Color(0xFFFEE2E2);

  // ---------------------------------------------------------------------------
  // Neutrals
  // ---------------------------------------------------------------------------

  /// Pure white — card surfaces.
  static const Color white = Color(0xFFFFFFFF);

  /// Dark text.
  static const Color textDark = Color(0xFF1B2A6E);

  /// Hint / placeholder text.
  static const Color textHint = Color(0xFFADB5BD);

  // ---------------------------------------------------------------------------
  // Gradients (convenience)
  // ---------------------------------------------------------------------------

  /// Primary sky linear gradient (sky → sky3, left-right).
  static const LinearGradient skyGradient = LinearGradient(
    colors: [sky, sky3],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );

  /// Sky gradient (sky → sky3, top-left → bottom-right).
  static const LinearGradient skyGradientDiagonal = LinearGradient(
    colors: [sky, sky3],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  /// Navy gradient (navy → sky3, top → bottom).
  static const LinearGradient navyGradient = LinearGradient(
    colors: [navy, sky3],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );

  /// Navy diagonal gradient.
  static const LinearGradient navyGradientDiagonal = LinearGradient(
    colors: [navy, sky3],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
