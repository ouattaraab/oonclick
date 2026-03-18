import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

// ---------------------------------------------------------------------------
// SecureStorageService
// ---------------------------------------------------------------------------

/// Abstracts [FlutterSecureStorage] with typed accessors for the keys used
/// across the oon.click application.
///
/// On Android the data is stored in EncryptedSharedPreferences.
/// On iOS the data is stored in the Keychain.
class SecureStorageService {
  SecureStorageService(this._storage);

  final FlutterSecureStorage _storage;

  // ---------------------------------------------------------------------------
  // Storage keys — private constants to avoid magic strings across the app.
  // ---------------------------------------------------------------------------

  static const _tokenKey = 'auth_token';
  static const _userKey = 'current_user';
  static const _deviceFingerprintKey = 'device_fingerprint';

  // ---------------------------------------------------------------------------
  // Auth token
  // ---------------------------------------------------------------------------

  /// Persists the Sanctum Bearer token.
  Future<void> saveToken(String token) =>
      _storage.write(key: _tokenKey, value: token);

  /// Returns the stored Bearer token, or `null` if not present.
  Future<String?> getToken() => _storage.read(key: _tokenKey);

  /// Removes the stored Bearer token (called on logout).
  Future<void> deleteToken() => _storage.delete(key: _tokenKey);

  // ---------------------------------------------------------------------------
  // Authenticated user (JSON snapshot)
  // ---------------------------------------------------------------------------

  /// Persists a JSON-encoded representation of the authenticated user.
  Future<void> saveUser(String userJson) =>
      _storage.write(key: _userKey, value: userJson);

  /// Returns the stored user JSON, or `null` if not present.
  Future<String?> getUser() => _storage.read(key: _userKey);

  /// Removes the stored user snapshot.
  Future<void> deleteUser() => _storage.delete(key: _userKey);

  // ---------------------------------------------------------------------------
  // Device fingerprint (cached so it is computed only once)
  // ---------------------------------------------------------------------------

  /// Persists the computed device fingerprint.
  Future<void> saveFingerprint(String fingerprint) =>
      _storage.write(key: _deviceFingerprintKey, value: fingerprint);

  /// Returns the cached device fingerprint, or `null` if not yet computed.
  Future<String?> getFingerprint() =>
      _storage.read(key: _deviceFingerprintKey);

  // ---------------------------------------------------------------------------
  // Full reset
  // ---------------------------------------------------------------------------

  /// Deletes all entries from secure storage.
  /// Called on logout or account deletion.
  Future<void> clearAll() => _storage.deleteAll();
}

// ---------------------------------------------------------------------------
// Riverpod provider
// ---------------------------------------------------------------------------

/// Android-specific options: use EncryptedSharedPreferences for extra security.
const _androidOptions = AndroidOptions(encryptedSharedPreferences: true);

/// iOS-specific options: accessible only when device is unlocked.
const _iosOptions = IOSOptions(
  accessibility: KeychainAccessibility.first_unlock_this_device,
);

/// Global provider for [SecureStorageService].
final secureStorageProvider = Provider<SecureStorageService>((ref) {
  return SecureStorageService(
    const FlutterSecureStorage(
      aOptions: _androidOptions,
      iOptions: _iosOptions,
    ),
  );
});
