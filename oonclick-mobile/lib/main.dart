import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/config/app_config.dart';
import 'core/router/app_router.dart' show rootNavigatorKey, routerProvider;
import 'core/services/fcm_service.dart';
import 'core/services/hive_service.dart';
import 'core/theme/app_theme.dart';

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Verify HTTPS in release builds.
  AppConfig.assertSecureConfig();

  // Initialise Firebase (FCM push notifications).
  try {
    await Firebase.initializeApp();
    await FcmService.init();
    // Connect FCM navigation to the GoRouter navigator key.
    FcmService.setNavigatorKey(rootNavigatorKey);
  } catch (e) {
    debugPrint('Firebase init failed (will work without push notifications): $e');
  }

  // Initialise offline storage before the widget tree is mounted.
  await HiveService.init();

  runApp(
    // ProviderScope is the root of the Riverpod dependency graph.
    const ProviderScope(
      child: OonClickApp(),
    ),
  );
}

// ---------------------------------------------------------------------------
// Root widget
// ---------------------------------------------------------------------------

/// Root application widget.
///
/// Uses [ConsumerWidget] so it can read [routerProvider] reactively —
/// the router rebuilds whenever auth state changes.
class OonClickApp extends ConsumerWidget {
  const OonClickApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'oon.click',

      // ---- Themes ----
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: ThemeMode.system,

      // ---- Router ----
      routerConfig: router,

      // ---- Misc ----
      debugShowCheckedModeBanner: false,

      // ---- Localisation (French — Ivory Coast primary, France fallback) ----
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        Locale('fr', 'CI'),
        Locale('fr', 'FR'),
        Locale('fr'), // generic French fallback
      ],
      locale: const Locale('fr', 'CI'),
    );
  }
}
