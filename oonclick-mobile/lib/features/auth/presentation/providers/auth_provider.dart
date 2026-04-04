import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
import '../../../../core/services/google_sign_in_service.dart';
import '../../../../core/services/secure_storage_service.dart';
import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';

// Re-export so screens only need to import auth_provider.dart.
export '../../data/repositories/auth_repository.dart' show authRepositoryProvider;

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

class AuthState {
  const AuthState({
    this.token,
    this.user,
    this.walletBalance = 0,
    this.isLoading = false,
    this.error,
  });

  final String? token;
  final UserModel? user;
  final int walletBalance;
  final bool isLoading;
  final String? error;

  bool get isAuthenticated => token != null && user != null;

  factory AuthState.initial() => const AuthState();

  AuthState copyWith({
    String? token,
    UserModel? user,
    int? walletBalance,
    bool? isLoading,
    String? error,
    bool clearToken = false,
    bool clearUser = false,
    bool clearError = false,
  }) {
    return AuthState(
      token: clearToken ? null : token ?? this.token,
      user: clearUser ? null : user ?? this.user,
      walletBalance: walletBalance ?? this.walletBalance,
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : error ?? this.error,
    );
  }

  @override
  String toString() =>
      'AuthState(authenticated: $isAuthenticated, role: ${user?.role})';
}

// ---------------------------------------------------------------------------
// Notifier
// ---------------------------------------------------------------------------

class AuthNotifier extends AsyncNotifier<AuthState> {
  @override
  Future<AuthState> build() async {
    final storage = ref.read(secureStorageProvider);
    final token = await storage.getToken();

    if (token == null || token.isEmpty) {
      return AuthState.initial();
    }

    // Restore token in-memory so ApiClient injects it.
    ref.read(authTokenProvider.notifier).state = token;

    try {
      final repo = ref.read(authRepositoryProvider);
      final user = await repo.me();
      return AuthState(token: token, user: user);
    } on ApiException {
      // Token is stale — clear storage and start fresh.
      await storage.clearAll();
      ref.read(authTokenProvider.notifier).state = null;
      return AuthState.initial();
    }
  }

  // ---------------------------------------------------------------------------
  // Register
  // ---------------------------------------------------------------------------

  Future<void> register(String identifier, String role,
      {String method = 'phone'}) async {
    state = AsyncData(state.valueOrNull?.copyWith(isLoading: true, clearError: true) ??
        const AuthState(isLoading: true));

    try {
      final repo = ref.read(authRepositoryProvider);
      await repo.register(identifier, role, method: method);
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false) ??
              const AuthState());
    } on ApiException catch (e) {
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false, error: e.message) ??
              AuthState(error: e.message));
      rethrow;
    }
  }

  // ---------------------------------------------------------------------------
  // Verify OTP
  // ---------------------------------------------------------------------------

  /// Returns `true` on success so callers can navigate accordingly.
  Future<bool> verifyOtp({
    required String phone,
    required String code,
    required String type,
    String? fingerprint,
    String? platform,
  }) async {
    state = AsyncData(state.valueOrNull?.copyWith(isLoading: true, clearError: true) ??
        const AuthState(isLoading: true));

    try {
      final repo = ref.read(authRepositoryProvider);
      final authResponse = await repo.verifyOtp(
        phone: phone,
        code: code,
        type: type,
        fingerprint: fingerprint,
        platform: platform,
      );

      // Persist to secure storage.
      final storage = ref.read(secureStorageProvider);
      await storage.saveToken(authResponse.token);
      await storage.saveUser(authResponse.user.toJsonString());

      // Update in-memory token so ApiClient picks it up.
      ref.read(authTokenProvider.notifier).state = authResponse.token;

      state = AsyncData(AuthState(
        token: authResponse.token,
        user: authResponse.user,
        walletBalance: authResponse.wallet?.balance ?? 0,
        isLoading: false,
      ));

      return true;
    } on ApiException catch (e) {
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false, error: e.message) ??
              AuthState(error: e.message));
      return false;
    }
  }

  // ---------------------------------------------------------------------------
  // Verify with Firebase (Phone Auth)
  // ---------------------------------------------------------------------------

  /// Verifies a Firebase ID token with the backend.
  /// Returns `true` on success.
  Future<bool> verifyWithFirebase({
    required String phone,
    required String firebaseIdToken,
    required String type,
    String? fingerprint,
    String? platform,
  }) async {
    state = AsyncData(state.valueOrNull?.copyWith(isLoading: true, clearError: true) ??
        const AuthState(isLoading: true));

    try {
      final repo = ref.read(authRepositoryProvider);
      final authResponse = await repo.verifyWithFirebase(
        phone: phone,
        firebaseIdToken: firebaseIdToken,
        type: type,
        fingerprint: fingerprint,
        platform: platform,
      );

      final storage = ref.read(secureStorageProvider);
      await storage.saveToken(authResponse.token);
      await storage.saveUser(authResponse.user.toJsonString());

      ref.read(authTokenProvider.notifier).state = authResponse.token;

      state = AsyncData(AuthState(
        token: authResponse.token,
        user: authResponse.user,
        walletBalance: authResponse.wallet?.balance ?? 0,
        isLoading: false,
      ));

      return true;
    } on ApiException catch (e) {
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false, error: e.message) ??
              AuthState(error: e.message));
      return false;
    }
  }

  // ---------------------------------------------------------------------------
  // Google Sign-In
  // ---------------------------------------------------------------------------

  /// Signs in with Google. Returns `true` on success, `false` if cancelled.
  Future<bool> signInWithGoogle({
    String role = 'subscriber',
    String? fingerprint,
    String? platform,
  }) async {
    state = AsyncData(state.valueOrNull?.copyWith(isLoading: true, clearError: true) ??
        const AuthState(isLoading: true));

    try {
      final googleService = ref.read(googleSignInServiceProvider);
      final result = await googleService.signIn();

      if (result == null) {
        // User cancelled
        state = AsyncData(
            state.valueOrNull?.copyWith(isLoading: false) ?? const AuthState());
        return false;
      }

      final repo = ref.read(authRepositoryProvider);
      final authResponse = await repo.loginWithGoogle(
        firebaseIdToken: result.firebaseIdToken,
        email: result.email,
        name: result.displayName,
        role: role,
        fingerprint: fingerprint,
        platform: platform,
      );

      final storage = ref.read(secureStorageProvider);
      await storage.saveToken(authResponse.token);
      await storage.saveUser(authResponse.user.toJsonString());

      ref.read(authTokenProvider.notifier).state = authResponse.token;

      state = AsyncData(AuthState(
        token: authResponse.token,
        user: authResponse.user,
        walletBalance: authResponse.wallet?.balance ?? 0,
        isLoading: false,
      ));

      return true;
    } on ApiException catch (e) {
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false, error: e.message) ??
              AuthState(error: e.message));
      return false;
    } catch (e) {
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false, error: e.toString()) ??
              AuthState(error: e.toString()));
      return false;
    }
  }

  // ---------------------------------------------------------------------------
  // Login
  // ---------------------------------------------------------------------------

  Future<void> login(String phone) async {
    state = AsyncData(state.valueOrNull?.copyWith(isLoading: true, clearError: true) ??
        const AuthState(isLoading: true));

    try {
      final repo = ref.read(authRepositoryProvider);
      await repo.login(phone);
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false) ??
              const AuthState());
    } on ApiException catch (e) {
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false, error: e.message) ??
              AuthState(error: e.message));
      rethrow;
    }
  }

  // ---------------------------------------------------------------------------
  // Logout
  // ---------------------------------------------------------------------------

  Future<void> logout() async {
    try {
      final repo = ref.read(authRepositoryProvider);
      await repo.logout();
    } catch (_) {
      // Best-effort server logout; always clear locally.
    }

    final storage = ref.read(secureStorageProvider);
    await storage.clearAll();
    ref.read(authTokenProvider.notifier).state = null;
    state = AsyncData(AuthState.initial());
  }

  // ---------------------------------------------------------------------------
  // Complete profile
  // ---------------------------------------------------------------------------

  /// Met à jour l'utilisateur localement sans appeler l'API.
  void updateUserLocally(UserModel user) {
    final storage = ref.read(secureStorageProvider);
    storage.saveUser(user.toJsonString());
    state = AsyncData(state.valueOrNull?.copyWith(user: user) ??
        AuthState(user: user));
  }

  Future<void> completeProfile(Map<String, dynamic> data) async {
    state = AsyncData(state.valueOrNull?.copyWith(isLoading: true, clearError: true) ??
        const AuthState(isLoading: true));

    try {
      final repo = ref.read(authRepositoryProvider);
      await repo.completeProfile(data);

      // Refresh user data.
      final user = await repo.me();
      final storage = ref.read(secureStorageProvider);
      await storage.saveUser(user.toJsonString());

      state = AsyncData(state.valueOrNull?.copyWith(
            isLoading: false,
            user: user,
          ) ??
          AuthState(user: user));
    } on ApiException catch (e) {
      state = AsyncData(
          state.valueOrNull?.copyWith(isLoading: false, error: e.message) ??
              AuthState(error: e.message));
      rethrow;
    }
  }
}

// ---------------------------------------------------------------------------
// Providers
// ---------------------------------------------------------------------------

final authProvider =
    AsyncNotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

/// Synchronous convenience — returns [AuthState.initial()] while loading.
final authStateProvider = Provider<AuthState>((ref) {
  return ref.watch(authProvider).valueOrNull ?? AuthState.initial();
});

/// Quick access to the current Bearer token.
final authTokenStateProvider = Provider<String?>((ref) {
  return ref.watch(authStateProvider).token;
});

/// Quick access to the currently authenticated user.
final currentUserProvider = Provider<UserModel?>((ref) {
  return ref.watch(authStateProvider).user;
});
