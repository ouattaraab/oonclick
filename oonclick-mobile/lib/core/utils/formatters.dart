import 'package:intl/intl.dart';

// ---------------------------------------------------------------------------
// Formatters
// ---------------------------------------------------------------------------

/// Stateless formatting utilities for the oon.click application.
///
/// All methods are static — no instantiation required.
abstract final class Formatters {
  // ---------------------------------------------------------------------------
  // Currency
  // ---------------------------------------------------------------------------

  /// Formats an integer amount in FCFA using French locale grouping.
  ///
  /// Examples:
  /// ```dart
  /// Formatters.currency(10000)  // "10 000 FCFA"
  /// Formatters.currency(500)    // "500 FCFA"
  /// ```
  static String currency(int amount) {
    final formatted = NumberFormat('#,###', 'fr_FR').format(amount);
    // NumberFormat uses non-breaking space (U+00A0) as group separator in
    // fr_FR — that is correct for the locale but we keep it as-is.
    return '$formatted FCFA';
  }

  /// Formats a double amount, showing cents only when non-zero.
  ///
  /// Example: `Formatters.currencyDecimal(500.5)` → "500,50 FCFA"
  static String currencyDecimal(double amount) {
    final formatted = NumberFormat('#,##0.##', 'fr_FR').format(amount);
    return '$formatted FCFA';
  }

  // ---------------------------------------------------------------------------
  // Dates & times
  // ---------------------------------------------------------------------------

  /// Formats a [DateTime] as `d MMM yyyy` in French.
  ///
  /// Example: `Formatters.date(DateTime(2024, 1, 15))` → "15 janv. 2024"
  static String date(DateTime date) =>
      DateFormat('d MMM yyyy', 'fr_FR').format(date);

  /// Formats a [DateTime] as `d MMM yyyy, HH:mm` in French.
  ///
  /// Example: → "15 janv. 2024, 14:30"
  static String dateTime(DateTime date) =>
      DateFormat('d MMM yyyy, HH:mm', 'fr_FR').format(date);

  /// Formats a [DateTime] as a short time string `HH:mm`.
  static String time(DateTime date) =>
      DateFormat('HH:mm', 'fr_FR').format(date);

  /// Returns a human-friendly relative time string (e.g. "il y a 3 min").
  static String relativeTime(DateTime date) {
    final now = DateTime.now();
    final diff = now.difference(date);

    if (diff.inSeconds < 60) return 'à l\'instant';
    if (diff.inMinutes < 60) return 'il y a ${diff.inMinutes} min';
    if (diff.inHours < 24) return 'il y a ${diff.inHours} h';
    if (diff.inDays < 7) return 'il y a ${diff.inDays} j';
    if (diff.inDays < 30) return 'il y a ${(diff.inDays / 7).floor()} sem.';
    if (diff.inDays < 365) return 'il y a ${(diff.inDays / 30).floor()} mois';
    return 'il y a ${(diff.inDays / 365).floor()} an(s)';
  }

  // ---------------------------------------------------------------------------
  // Duration
  // ---------------------------------------------------------------------------

  /// Formats a duration in seconds as `m:ss`.
  ///
  /// Examples:
  /// ```dart
  /// Formatters.duration(90)  // "1:30"
  /// Formatters.duration(5)   // "0:05"
  /// ```
  static String duration(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    return '$m:${s.toString().padLeft(2, '0')}';
  }

  /// Formats a [Duration] object as `m:ss`.
  static String durationObj(Duration d) => duration(d.inSeconds);

  // ---------------------------------------------------------------------------
  // Phone numbers
  // ---------------------------------------------------------------------------

  /// Formats an Ivory Coast (+225) phone number with visual spacing.
  ///
  /// Input must include the country code prefix (+225 or 225).
  ///
  /// Example:
  /// ```dart
  /// Formatters.phone('+2250701234567')  // "+225 07 01 23 45 67"
  /// ```
  static String phone(String phone) {
    // Normalise — strip spaces and dashes.
    final clean = phone.replaceAll(RegExp(r'[\s\-]'), '');

    if (clean.startsWith('+225') && clean.length >= 13) {
      final local = clean.substring(4); // 9 or 10 digits
      if (local.length == 10) {
        return '+225 ${local.substring(0, 2)} ${local.substring(2, 4)} '
            '${local.substring(4, 6)} ${local.substring(6, 8)} '
            '${local.substring(8, 10)}';
      }
    }

    // Unrecognised format — return as-is.
    return phone;
  }

  // ---------------------------------------------------------------------------
  // Numbers & percentages
  // ---------------------------------------------------------------------------

  /// Formats a large integer with k/M suffix for compact display.
  ///
  /// Examples: 1500 → "1,5 k", 1_200_000 → "1,2 M"
  static String compact(int value) {
    if (value >= 1000000) {
      return '${(value / 1000000).toStringAsFixed(1).replaceAll('.', ',')} M';
    }
    if (value >= 1000) {
      return '${(value / 1000).toStringAsFixed(1).replaceAll('.', ',')} k';
    }
    return value.toString();
  }

  /// Formats a ratio as a percentage string.
  ///
  /// Example: `Formatters.percent(0.75)` → "75 %"
  static String percent(double ratio, {int decimals = 0}) {
    final pct = (ratio * 100).toStringAsFixed(decimals);
    return '$pct %';
  }

  // ---------------------------------------------------------------------------
  // Text helpers
  // ---------------------------------------------------------------------------

  /// Truncates [text] to [maxLength] characters, appending `…` if truncated.
  static String truncate(String text, int maxLength) {
    if (text.length <= maxLength) return text;
    return '${text.substring(0, maxLength)}…';
  }

  /// Capitalises the first letter of [text].
  static String capitalize(String text) {
    if (text.isEmpty) return text;
    return text[0].toUpperCase() + text.substring(1);
  }
}
