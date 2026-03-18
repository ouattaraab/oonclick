import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/api/api_client.dart';
import '../../../../core/api/api_exception.dart';
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

  Future<void> register(String phone, String role) async {
    state = AsyncData(state.valueOrNull?.copyWith(isLoading: true, clearError: true) ??
        const AuthState(isLoading: true));

    try {
      final repo = ref.read(authRepositoryProvider);
      await repo.register(phone, role);
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
