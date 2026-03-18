import 'package:intl/intl.dart';

/// Formats an integer FCFA amount for display.
///
/// Example: `formatFcfa(1500)` → `"1 500 FCFA"`
String formatFcfa(int amount) {
  final formatter = NumberFormat('#,###', 'fr_FR');
  return '${formatter.format(amount)} FCFA';
}

/// Same as [formatFcfa] but shows a leading `+` for credits.
String formatFcfaCredit(int amount) => '+${formatFcfa(amount)}';
