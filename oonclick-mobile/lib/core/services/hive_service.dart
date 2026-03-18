import 'package:hive_flutter/hive_flutter.dart';

// ---------------------------------------------------------------------------
// HiveService
// ---------------------------------------------------------------------------

/// Centralises Hive initialisation and named-box accessors.
///
/// Call [HiveService.init] once from `main()` before [runApp].
/// Thereafter, access boxes via the static getters — they are guaranteed to
/// be open.
class HiveService {
  HiveService._();

  // ---------------------------------------------------------------------------
  // Box names — private constants used both here and in feature repositories.
  // ---------------------------------------------------------------------------

  static const _feedCacheBoxName = 'feed_cache';
  static const _userCacheBoxName = 'user_cache';
  static const _settingsBoxName = 'settings';

  // ---------------------------------------------------------------------------
  // Initialisation
  // ---------------------------------------------------------------------------

  /// Initialises Hive and opens all application boxes.
  ///
  /// Must be awaited before the widget tree is built.
  static Future<void> init() async {
    await Hive.initFlutter();

    await Future.wait([
      Hive.openBox<dynamic>(_feedCacheBoxName),
      Hive.openBox<dynamic>(_userCacheBoxName),
      Hive.openBox<dynamic>(_settingsBoxName),
    ]);
  }

  // ---------------------------------------------------------------------------
  // Box accessors
  // ---------------------------------------------------------------------------

  /// Cache for the advertising feed (campaign data, ad metadata).
  static Box<dynamic> get feedCache => Hive.box<dynamic>(_feedCacheBoxName);

  /// Offline cache for the authenticated user's data.
  static Box<dynamic> get userCache => Hive.box<dynamic>(_userCacheBoxName);

  /// Application settings and user preferences.
  static Box<dynamic> get settings => Hive.box<dynamic>(_settingsBoxName);

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /// Clears every open box.  Called on logout to wipe local state.
  static Future<void> clearAll() async {
    await Future.wait([
      feedCache.clear(),
      userCache.clear(),
      // settings intentionally preserved across logout (theme, locale…).
    ]);
  }

  /// Closes all boxes gracefully.  Safe to call multiple times.
  static Future<void> close() async {
    await Hive.close();
  }
}
