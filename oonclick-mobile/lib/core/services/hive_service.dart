import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
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

  /// Key used to store the Hive encryption key in secure storage.
  static const _hiveEncryptionKeyName = 'hive_encryption_key';

  // ---------------------------------------------------------------------------
  // Initialisation
  // ---------------------------------------------------------------------------

  /// Initialises Hive and opens all application boxes.
  /// User data boxes are encrypted via a key stored in platform secure storage.
  ///
  /// Must be awaited before the widget tree is built.
  static Future<void> init() async {
    await Hive.initFlutter();

    final encryptionCipher = await _getEncryptionCipher();

    await Future.wait([
      Hive.openBox<dynamic>(_feedCacheBoxName, encryptionCipher: encryptionCipher),
      Hive.openBox<dynamic>(_userCacheBoxName, encryptionCipher: encryptionCipher),
      Hive.openBox<dynamic>(_settingsBoxName),
    ]);
  }

  /// Retrieves or generates the AES encryption key from secure storage.
  static Future<HiveAesCipher> _getEncryptionCipher() async {
    const storage = FlutterSecureStorage(
      aOptions: AndroidOptions(encryptedSharedPreferences: true),
      iOptions: IOSOptions(
        accessibility: KeychainAccessibility.first_unlock_this_device,
      ),
    );

    final existingKey = await storage.read(key: _hiveEncryptionKeyName);

    if (existingKey != null) {
      final keyBytes = base64Url.decode(existingKey);
      return HiveAesCipher(keyBytes);
    }

    final newKey = Hive.generateSecureKey();
    await storage.write(
      key: _hiveEncryptionKeyName,
      value: base64UrlEncode(newKey),
    );
    return HiveAesCipher(newKey);
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

  // ---------------------------------------------------------------------------
  // Typed settings helpers
  // ---------------------------------------------------------------------------

  /// Returns the boolean value stored under [key] in the settings box, or
  /// [defaultValue] if the key is absent.
  static bool? getBool(String key) {
    final val = settings.get(key);
    if (val is bool) return val;
    return null;
  }

  /// Persists a boolean [value] under [key] in the settings box.
  static Future<void> setBool(String key, bool value) async {
    await settings.put(key, value);
  }
}
