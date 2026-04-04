import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

/// Dialog modal non-dismissible pour forcer la mise à jour de l'app.
class ForceUpdateDialog extends StatelessWidget {
  final String? storeUrl;
  final String? releaseNotes;
  final bool forceful;

  const ForceUpdateDialog({
    super.key,
    this.storeUrl,
    this.releaseNotes,
    this.forceful = true,
  });

  static Future<void> show(
    BuildContext context, {
    String? storeUrl,
    String? releaseNotes,
    bool forceful = true,
  }) {
    return showDialog(
      context: context,
      barrierDismissible: !forceful,
      builder: (_) => ForceUpdateDialog(
        storeUrl: storeUrl,
        releaseNotes: releaseNotes,
        forceful: forceful,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: !forceful,
      child: AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.system_update, color: Color(0xFF0EA5E9), size: 28),
            SizedBox(width: 10),
            Text('Mise à jour requise', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Une nouvelle version de oon.click est disponible. Veuillez mettre à jour pour continuer.',
              style: TextStyle(fontSize: 14, color: Color(0xFF475569)),
            ),
            if (releaseNotes != null && releaseNotes!.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  releaseNotes!,
                  style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (!forceful)
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Plus tard', style: TextStyle(color: Color(0xFF94A3B8))),
            ),
          ElevatedButton(
            onPressed: () async {
              if (storeUrl != null) {
                final uri = Uri.parse(storeUrl!);
                if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0EA5E9),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            ),
            child: const Text('Mettre à jour', style: TextStyle(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }
}
