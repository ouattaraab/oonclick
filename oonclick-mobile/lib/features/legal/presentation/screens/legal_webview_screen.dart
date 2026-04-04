import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';

// ---------------------------------------------------------------------------
// Legal WebView screen
//
// Builds the full URL from the API base URL (strips the /api suffix) and
// opens the page in the device's default browser.  A loading indicator and a
// manual "open" button are shown while the browser launches.
// ---------------------------------------------------------------------------

class LegalWebviewScreen extends StatefulWidget {
  const LegalWebviewScreen({
    super.key,
    required this.title,
    required this.path,
  });

  /// AppBar title, e.g. "Conditions Générales d'Utilisation".
  final String title;

  /// Server-relative path, e.g. "/cgu" or "/confidentialite".
  final String path;

  @override
  State<LegalWebviewScreen> createState() => _LegalWebviewScreenState();
}

class _LegalWebviewScreenState extends State<LegalWebviewScreen> {
  bool _launchFailed = false;

  String get _url {
    // Strip /api suffix so we reach the web pages, not the JSON API.
    final base = AppConfig.baseUrl.replaceAll(RegExp(r'/api$'), '');
    return '$base${widget.path}';
  }

  @override
  void initState() {
    super.initState();
    // Attempt to open the browser on the first frame after the widget is built.
    WidgetsBinding.instance.addPostFrameCallback((_) => _launch());
  }

  Future<void> _launch() async {
    setState(() {
      _launchFailed = false;
    });

    try {
      final uri = Uri.parse(_url);
      final canLaunch = await canLaunchUrl(uri);
      if (canLaunch) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
        if (mounted) setState(() => _launchFailed = false);
      } else {
        if (mounted) setState(() => _launchFailed = true);
      }
    } catch (_) {
      if (mounted) setState(() => _launchFailed = true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // ---- Navy top bar ----
          _LegalTopBar(title: widget.title),

          // ---- Body ----
          Expanded(
            child: Center(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 32),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    if (!_launchFailed) ...[
                      const SizedBox(
                        width: 40,
                        height: 40,
                        child: CircularProgressIndicator(
                          strokeWidth: 3,
                          color: AppColors.sky,
                        ),
                      ),
                      const SizedBox(height: 20),
                      Text(
                        'Ouverture de ${widget.title}…',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.nunito(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.muted,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Le document s\'ouvre dans votre navigateur.',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.nunito(
                          fontSize: 12,
                          color: AppColors.textHint,
                        ),
                      ),
                    ] else ...[
                      Container(
                        width: 56,
                        height: 56,
                        decoration: BoxDecoration(
                          color: AppColors.warnLight,
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: const Icon(
                          Icons.open_in_browser_rounded,
                          color: AppColors.warn,
                          size: 28,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Impossible d\'ouvrir le navigateur automatiquement.',
                        textAlign: TextAlign.center,
                        style: GoogleFonts.nunito(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: AppColors.navy,
                        ),
                      ),
                    ],
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: _launch,
                        icon: const Icon(Icons.open_in_new_rounded, size: 18),
                        label: Text(
                          'Ouvrir dans le navigateur',
                          style: GoogleFonts.nunito(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.sky,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: 0,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: () => context.pop(),
                      child: Text(
                        'Retour',
                        style: GoogleFonts.nunito(
                          fontSize: 13,
                          color: AppColors.muted,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Top bar
// ---------------------------------------------------------------------------

class _LegalTopBar extends StatelessWidget {
  const _LegalTopBar({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        MediaQuery.of(context).padding.top + 12,
        16,
        14,
      ),
      decoration: const BoxDecoration(gradient: AppColors.navyGradient),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => context.pop(),
            child: Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(30),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(
                Icons.arrow_back_ios_new_rounded,
                color: Colors.white,
                size: 15,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              title,
              style: GoogleFonts.nunito(
                fontSize: 15,
                fontWeight: FontWeight.w800,
                color: Colors.white,
              ),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
