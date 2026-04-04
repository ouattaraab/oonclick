import 'dart:async';

import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Service encapsulant Firebase Phone Auth pour la vérification SMS.
///
/// Firebase gère l'envoi du SMS et la vérification automatique (Android).
/// Ce service expose un flux simplifié pour le reste de l'app.
class FirebasePhoneAuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;

  String? _verificationId;
  int? _resendToken;

  /// Lance la vérification du numéro de téléphone.
  ///
  /// [onCodeSent] est appelé quand le SMS est envoyé (avec le verificationId).
  /// [onAutoVerified] est appelé si Android résout automatiquement le SMS.
  /// [onError] est appelé en cas d'erreur.
  Future<void> verifyPhoneNumber({
    required String phoneNumber,
    required void Function(String verificationId) onCodeSent,
    required void Function(PhoneAuthCredential credential) onAutoVerified,
    required void Function(String error) onError,
    int? forceResendingToken,
  }) async {
    try {
      await _auth.verifyPhoneNumber(
        phoneNumber: phoneNumber,
        timeout: const Duration(seconds: 60),
        forceResendingToken: forceResendingToken ?? _resendToken,

        // Android auto-retrieval : le code est automatiquement détecté
        verificationCompleted: (PhoneAuthCredential credential) {
          debugPrint('[FirebasePhoneAuth] Auto-verified');
          onAutoVerified(credential);
        },

        // Erreur
        verificationFailed: (FirebaseAuthException e) {
          debugPrint('[FirebasePhoneAuth] Error: ${e.code} - ${e.message}');
          String message;
          switch (e.code) {
            case 'invalid-phone-number':
              message = 'Numéro de téléphone invalide.';
            case 'too-many-requests':
              message =
                  'Trop de tentatives. Veuillez réessayer dans quelques minutes.';
            case 'quota-exceeded':
              message = 'Quota SMS dépassé. Réessayez plus tard.';
            default:
              message = e.message ?? 'Erreur de vérification du téléphone.';
          }
          onError(message);
        },

        // SMS envoyé — l'utilisateur doit saisir le code manuellement
        codeSent: (String verificationId, int? resendToken) {
          debugPrint('[FirebasePhoneAuth] Code sent, verificationId=$verificationId');
          _verificationId = verificationId;
          _resendToken = resendToken;
          onCodeSent(verificationId);
        },

        // Timeout auto-retrieval (Android)
        codeAutoRetrievalTimeout: (String verificationId) {
          _verificationId = verificationId;
        },
      );
    } catch (e) {
      onError('Erreur inattendue : $e');
    }
  }

  /// Vérifie le code OTP saisi par l'utilisateur.
  ///
  /// Retourne le [PhoneAuthCredential] si le code est correct.
  /// Lance une exception si le code est invalide.
  Future<PhoneAuthCredential> verifyCode(String smsCode) async {
    if (_verificationId == null) {
      throw Exception('Aucune vérification en cours. Veuillez renvoyer le code.');
    }

    return PhoneAuthProvider.credential(
      verificationId: _verificationId!,
      smsCode: smsCode,
    );
  }

  /// Signe l'utilisateur avec les credentials Firebase Phone Auth.
  ///
  /// Retourne le [UserCredential] Firebase (contient l'ID token).
  Future<UserCredential> signInWithCredential(
      PhoneAuthCredential credential) async {
    return _auth.signInWithCredential(credential);
  }

  /// Récupère l'ID token Firebase de l'utilisateur connecté.
  ///
  /// Ce token sera envoyé au backend pour créer/authentifier l'utilisateur.
  Future<String?> getIdToken() async {
    final user = _auth.currentUser;
    if (user == null) return null;
    return user.getIdToken();
  }

  /// Renvoie le code SMS (utilise le resend token Firebase).
  Future<void> resendCode({
    required String phoneNumber,
    required void Function(String verificationId) onCodeSent,
    required void Function(PhoneAuthCredential credential) onAutoVerified,
    required void Function(String error) onError,
  }) {
    return verifyPhoneNumber(
      phoneNumber: phoneNumber,
      onCodeSent: onCodeSent,
      onAutoVerified: onAutoVerified,
      onError: onError,
      forceResendingToken: _resendToken,
    );
  }

  /// Déconnecte l'utilisateur Firebase (nettoyage).
  Future<void> signOut() async {
    await _auth.signOut();
  }

  /// Token de renvoi actuel.
  int? get resendToken => _resendToken;

  /// ID de vérification actuel.
  String? get verificationId => _verificationId;
}

// ---------------------------------------------------------------------------
// Provider
// ---------------------------------------------------------------------------

final firebasePhoneAuthProvider = Provider<FirebasePhoneAuthService>((ref) {
  return FirebasePhoneAuthService();
});
