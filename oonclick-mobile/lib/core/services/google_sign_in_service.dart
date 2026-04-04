import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_sign_in/google_sign_in.dart';

import '../config/app_config.dart';

/// Service encapsulating Google Sign-In + Firebase Auth flow.
///
/// Flow:
/// 1. User taps "Continuer avec Google"
/// 2. [signIn] opens the Google account picker
/// 3. Uses the Google credential to sign in to Firebase
/// 4. Returns the Firebase ID token for backend verification
class GoogleSignInService {
  GoogleSignInService();

  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
    serverClientId: AppConfig.googleServerClientId,
  );

  final FirebaseAuth _firebaseAuth = FirebaseAuth.instance;

  /// Signs in with Google and returns a [GoogleAuthResult] containing
  /// the Firebase ID token, email, and display name.
  ///
  /// Returns `null` if the user cancelled the sign-in.
  /// Throws on error.
  Future<GoogleAuthResult?> signIn() async {
    try {
      // 1. Google Sign-In
      final googleUser = await _googleSignIn.signIn();
      if (googleUser == null) return null; // User cancelled

      // 2. Get auth details
      final googleAuth = await googleUser.authentication;

      // 3. Create Firebase credential
      final credential = GoogleAuthProvider.credential(
        accessToken: googleAuth.accessToken,
        idToken: googleAuth.idToken,
      );

      // 4. Sign in to Firebase
      final userCredential =
          await _firebaseAuth.signInWithCredential(credential);

      // 5. Get Firebase ID token
      final idToken = await userCredential.user?.getIdToken();

      if (idToken == null) {
        throw Exception('Impossible de récupérer le token Firebase.');
      }

      return GoogleAuthResult(
        firebaseIdToken: idToken,
        email: googleUser.email,
        displayName: googleUser.displayName,
        photoUrl: googleUser.photoUrl,
      );
    } catch (e) {
      debugPrint('[GoogleSignInService] Error: $e');
      rethrow;
    }
  }

  /// Signs out from both Google and Firebase.
  Future<void> signOut() async {
    await _googleSignIn.signOut();
    await _firebaseAuth.signOut();
  }
}

/// Result of a successful Google Sign-In.
class GoogleAuthResult {
  const GoogleAuthResult({
    required this.firebaseIdToken,
    required this.email,
    this.displayName,
    this.photoUrl,
  });

  final String firebaseIdToken;
  final String email;
  final String? displayName;
  final String? photoUrl;
}

/// Riverpod provider for [GoogleSignInService].
final googleSignInServiceProvider = Provider<GoogleSignInService>((ref) {
  return GoogleSignInService();
});
