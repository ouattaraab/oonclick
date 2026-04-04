import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// oon.click design system — Sky Gradient theme.
///
/// Use [AppColors] for colour constants and [AppTheme.lightTheme] /
/// [AppTheme.darkTheme] as [MaterialApp] theme arguments.
abstract final class AppTheme {
  AppTheme._();

  // ---------------------------------------------------------------------------
  // Legacy colour aliases — kept so existing widgets compile unchanged.
  // New code should use AppColors directly.
  // ---------------------------------------------------------------------------

  static const Color primary = AppColors.sky;
  static const Color primaryDark = AppColors.sky3;
  static const Color primaryLight = AppColors.sky2;

  static const Color success = AppColors.success;
  static const Color successLight = AppColors.successLight;

  static const Color error = AppColors.danger;
  static const Color errorLight = AppColors.dangerLight;

  static const Color warning = AppColors.warn;

  static const Color textPrimary = AppColors.navy;
  static const Color textSecondary = AppColors.muted;
  static const Color textHint = AppColors.textHint;

  static const Color bgPage = AppColors.bg;
  static const Color bgCard = AppColors.white;
  static const Color divider = AppColors.border;

  // ---------------------------------------------------------------------------
  // Theme aliases used by MaterialApp.router
  // ---------------------------------------------------------------------------

  static ThemeData get lightTheme => light;

  static ThemeData get darkTheme => _buildDark();

  // ---------------------------------------------------------------------------
  // Light theme
  // ---------------------------------------------------------------------------

  static ThemeData get light {
    final nunito = GoogleFonts.nunitoTextTheme();

    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.sky,
        primary: AppColors.sky,
        onPrimary: Colors.white,
        secondary: AppColors.navy,
        onSecondary: Colors.white,
        surface: AppColors.bg,
        onSurface: AppColors.navy,
        error: AppColors.danger,
        brightness: Brightness.light,
      ),
      scaffoldBackgroundColor: AppColors.bg,
      textTheme: nunito,
      primaryTextTheme: nunito,
    ).copyWith(
      // ---- AppBar ----
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.white,
        foregroundColor: AppColors.navy,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        centerTitle: true,
        titleTextStyle: GoogleFonts.nunito(
          fontSize: 17,
          fontWeight: FontWeight.w700,
          color: AppColors.navy,
        ),
        systemOverlayStyle: const SystemUiOverlayStyle(
          statusBarColor: Colors.transparent,
          statusBarIconBrightness: Brightness.dark,
        ),
      ),

      // ---- ElevatedButton — gradient wrapper applied per-widget via _GradientButton ----
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.sky,
          foregroundColor: Colors.white,
          minimumSize: const Size(double.infinity, 44),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: GoogleFonts.nunito(
            fontSize: 15,
            fontWeight: FontWeight.w700,
          ),
          elevation: 0,
        ),
      ),

      // ---- OutlinedButton ----
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.sky,
          side: const BorderSide(color: AppColors.sky),
          minimumSize: const Size(double.infinity, 44),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          textStyle: GoogleFonts.nunito(
            fontSize: 15,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),

      // ---- TextButton ----
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.sky2,
          textStyle: GoogleFonts.nunito(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),

      // ---- Input ----
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.skyPale,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.border, width: 1.5),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.border, width: 1.5),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.sky, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.danger, width: 1.5),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: AppColors.danger, width: 2),
        ),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        hintStyle: GoogleFonts.nunito(
          color: AppColors.textHint,
          fontSize: 14,
        ),
        labelStyle: GoogleFonts.nunito(
          color: AppColors.muted,
          fontSize: 14,
        ),
      ),

      // ---- Card ----
      cardTheme: CardThemeData(
        color: AppColors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: const BorderSide(color: AppColors.border),
        ),
        margin: EdgeInsets.zero,
      ),

      // ---- Chip ----
      chipTheme: ChipThemeData(
        backgroundColor: AppColors.skyPale,
        selectedColor: AppColors.sky.withAlpha(40),
        labelStyle: GoogleFonts.nunito(fontSize: 13),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(8),
          side: const BorderSide(color: AppColors.border),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      ),

      // ---- BottomNavigationBar ----
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: AppColors.white,
        selectedItemColor: AppColors.sky,
        unselectedItemColor: AppColors.muted,
        selectedLabelStyle: GoogleFonts.nunito(
          fontWeight: FontWeight.w700,
          fontSize: 11,
        ),
        unselectedLabelStyle: GoogleFonts.nunito(fontSize: 11),
        type: BottomNavigationBarType.fixed,
        elevation: 0,
      ),

      // ---- NavigationBar ----
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: AppColors.white,
        indicatorColor: AppColors.skyPale,
        iconTheme: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return const IconThemeData(color: AppColors.sky, size: 24);
          }
          return const IconThemeData(color: AppColors.muted, size: 24);
        }),
        labelTextStyle: WidgetStateProperty.resolveWith((states) {
          if (states.contains(WidgetState.selected)) {
            return GoogleFonts.nunito(
              fontWeight: FontWeight.w700,
              fontSize: 11,
              color: AppColors.sky,
            );
          }
          return GoogleFonts.nunito(fontSize: 11, color: AppColors.muted);
        }),
        elevation: 0,
        surfaceTintColor: Colors.transparent,
      ),

      // ---- Snackbar ----
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        contentTextStyle: GoogleFonts.nunito(fontSize: 14),
      ),

      // ---- Divider ----
      dividerTheme: const DividerThemeData(
        color: AppColors.border,
        thickness: 1,
      ),

      // ---- Dialog ----
      dialogTheme: DialogThemeData(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
        ),
        backgroundColor: AppColors.white,
        titleTextStyle: GoogleFonts.nunito(
          fontSize: 18,
          fontWeight: FontWeight.w800,
          color: AppColors.navy,
        ),
        contentTextStyle: GoogleFonts.nunito(
          fontSize: 14,
          color: AppColors.muted,
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Dark theme — minimal implementation
  // ---------------------------------------------------------------------------

  static ThemeData _buildDark() {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.sky,
        brightness: Brightness.dark,
        primary: AppColors.sky,
        onPrimary: Colors.white,
        surface: const Color(0xFF1B2A6E),
        onSurface: Colors.white,
        error: AppColors.danger,
      ),
      scaffoldBackgroundColor: const Color(0xFF0F1629),
      textTheme: GoogleFonts.nunitoTextTheme(
        ThemeData.dark().textTheme,
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Gradient button helper used across screens
// ---------------------------------------------------------------------------

/// A full-width button rendered with the Sky Gradient ([AppColors.sky] →
/// [AppColors.sky3]).
///
/// Drop-in replacement for [ElevatedButton] wherever the gradient treatment
/// is needed.
class SkyGradientButton extends StatelessWidget {
  const SkyGradientButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.isLoading = false,
    this.height = 44,
    this.borderRadius = 12,
    this.width = double.infinity,
    this.gradient = AppColors.skyGradient,
    this.child,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool isLoading;
  final double height;
  final double borderRadius;
  final double width;
  final LinearGradient gradient;
  final Widget? child;

  @override
  Widget build(BuildContext context) {
    final disabled = onPressed == null || isLoading;

    return GestureDetector(
      onTap: disabled ? null : onPressed,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        width: width,
        height: height,
        decoration: BoxDecoration(
          gradient: disabled
              ? LinearGradient(colors: [
                  AppColors.sky.withAlpha(100),
                  AppColors.sky3.withAlpha(100),
                ])
              : gradient,
          borderRadius: BorderRadius.circular(borderRadius),
          boxShadow: disabled
              ? null
              : [
                  BoxShadow(
                    color: AppColors.sky.withAlpha(60),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
        ),
        child: Center(
          child: isLoading
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2.5,
                    color: Colors.white,
                  ),
                )
              : child ??
                  Text(
                    label,
                    style: GoogleFonts.nunito(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 15,
                    ),
                  ),
        ),
      ),
    );
  }
}
