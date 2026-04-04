import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../auth/data/models/user_model.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/repositories/profile_repository.dart';
import '../providers/profile_provider.dart';

// ---------------------------------------------------------------------------
// Privacy / Confidentiality screen
//
// C1–C4 are mandatory consents locked to ON (displayed with a lock icon).
// C5 (push notifications) and C6 (marketing emails) are optional toggles that
// call POST /consents to persist the preference.
// ---------------------------------------------------------------------------

class PrivacyScreen extends ConsumerStatefulWidget {
  const PrivacyScreen({super.key});

  @override
  ConsumerState<PrivacyScreen> createState() => _PrivacyScreenState();
}

class _PrivacyScreenState extends ConsumerState<PrivacyScreen> {
  // Optional consent state — loaded from API then toggled by the user.
  bool _consentNotifications = true; // C5
  bool _consentMarketing = false;    // C6
  bool _consentsLoaded = false;

  bool _updatingC5 = false;
  bool _updatingC6 = false;

  @override
  void initState() {
    super.initState();
    _loadConsents();
  }

  Future<void> _loadConsents() async {
    try {
      final repo = ref.read(profileRepositoryProvider);
      final consents = await repo.getConsents();
      if (!mounted) return;
      setState(() {
        for (final c in consents) {
          if (c['consent_type'] == 'C5') {
            _consentNotifications = c['granted'] == true;
          }
          if (c['consent_type'] == 'C6') {
            _consentMarketing = c['granted'] == true;
          }
        }
        _consentsLoaded = true;
      });
    } catch (_) {
      if (mounted) setState(() => _consentsLoaded = true);
    }
  }

  /// Updates a single optional consent via the API.
  Future<void> _updateConsent(String type, bool granted) async {
    final repo = ref.read(profileRepositoryProvider);
    try {
      await repo.updateConsent(type, granted: granted);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Erreur lors de la mise à jour du consentement.',
            style: GoogleFonts.nunito(),
          ),
          backgroundColor: AppColors.danger,
        ),
      );
      // Revert optimistic update on failure.
      setState(() {
        if (type == 'C5') _consentNotifications = !granted;
        if (type == 'C6') _consentMarketing = !granted;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final userAsync = ref.watch(profileProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          _PrivacyTopBar(),
          Expanded(
            child: userAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (e, _) => Center(
                child: Text(
                  e.toString(),
                  style: GoogleFonts.nunito(color: AppColors.muted),
                ),
              ),
              data: (user) => _PrivacyContent(
                user: user,
                consentNotifications: _consentNotifications,
                consentMarketing: _consentMarketing,
                updatingC5: _updatingC5,
                updatingC6: _updatingC6,
                onNotificationsChanged: (val) async {
                  setState(() {
                    _consentNotifications = val;
                    _updatingC5 = true;
                  });
                  await _updateConsent('C5', val);
                  if (mounted) setState(() => _updatingC5 = false);
                },
                onMarketingChanged: (val) async {
                  setState(() {
                    _consentMarketing = val;
                    _updatingC6 = true;
                  });
                  await _updateConsent('C6', val);
                  if (mounted) setState(() => _updatingC6 = false);
                },
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

class _PrivacyTopBar extends StatelessWidget {
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
          Text(
            'Confidentialité',
            style: GoogleFonts.nunito(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Content
// ---------------------------------------------------------------------------

class _PrivacyContent extends StatelessWidget {
  const _PrivacyContent({
    required this.user,
    required this.consentNotifications,
    required this.consentMarketing,
    required this.updatingC5,
    required this.updatingC6,
    required this.onNotificationsChanged,
    required this.onMarketingChanged,
  });

  final UserModel? user;
  final bool consentNotifications;
  final bool consentMarketing;
  final bool updatingC5;
  final bool updatingC6;
  final Future<void> Function(bool) onNotificationsChanged;
  final Future<void> Function(bool) onMarketingChanged;

  String _formatDate(DateTime date) {
    return DateFormat("d MMMM yyyy 'à' HH:mm", 'fr').format(date);
  }

  DateTime _consentDate() {
    if (user?.phoneVerifiedAt != null) {
      try {
        return DateTime.parse(user!.phoneVerifiedAt!).toLocal();
      } catch (_) {}
    }
    return DateTime.now();
  }

  @override
  Widget build(BuildContext context) {
    final acceptedDate = _formatDate(_consentDate());

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // ---- Documents légaux ----
        _SectionLabel(label: 'Documents légaux'),
        const SizedBox(height: 10),

        _PrivacyCard(
          children: [
            _AcceptanceRow(
              emoji: '📄',
              iconBg: const Color(0xFFEBF7FE),
              label: 'Conditions Générales d\'Utilisation',
              acceptedAt: acceptedDate,
              onTap: () {
                final base = AppConfig.baseUrl.replaceAll(RegExp(r'/api$'), '');
                launchUrl(Uri.parse('$base/cgu'), mode: LaunchMode.externalApplication);
              },
            ),
            const Divider(height: 1, thickness: 1, color: AppColors.border),
            _AcceptanceRow(
              emoji: '🛡️',
              iconBg: const Color(0xFFE0F2FE),
              label: 'Politique de Confidentialité',
              acceptedAt: acceptedDate,
              onTap: () {
                final base = AppConfig.baseUrl.replaceAll(RegExp(r'/api$'), '');
                launchUrl(Uri.parse('$base/confidentialite'), mode: LaunchMode.externalApplication);
              },
            ),
          ],
        ),
        const SizedBox(height: 20),

        // ---- Consentements obligatoires (C1–C4) ----
        _SectionLabel(label: 'Consentements obligatoires'),
        const SizedBox(height: 6),
        Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: Text(
            'Ces consentements sont requis pour utiliser oon.click et ne peuvent pas être révoqués.',
            style: GoogleFonts.nunito(
              fontSize: 11,
              color: AppColors.muted,
              height: 1.4,
            ),
          ),
        ),

        _PrivacyCard(
          children: [
            _LockedConsentRow(
              emoji: '📋',
              iconBg: const Color(0xFFEBF7FE),
              label: 'CGU et Politique de confidentialité',
              subLabel: 'Accepté à l\'inscription',
              acceptedAt: acceptedDate,
            ),
            const Divider(height: 1, thickness: 1, color: AppColors.border),
            _LockedConsentRow(
              emoji: '🎯',
              iconBg: const Color(0xFFFEF3C7),
              label: 'Publicités ciblées',
              subLabel: 'Affichage de pubs selon votre profil',
              acceptedAt: acceptedDate,
            ),
            const Divider(height: 1, thickness: 1, color: AppColors.border),
            _LockedConsentRow(
              emoji: '🤝',
              iconBg: const Color(0xFFDCFCE7),
              label: 'Transfert de données aux annonceurs',
              subLabel: 'Données pseudonymisées — mesure d\'audience',
              acceptedAt: acceptedDate,
            ),
            const Divider(height: 1, thickness: 1, color: AppColors.border),
            _LockedConsentRow(
              emoji: '🔐',
              iconBg: const Color(0xFFEDE9FE),
              label: 'Empreinte de l\'appareil',
              subLabel: 'Identifiant technique anti-fraude',
              acceptedAt: acceptedDate,
            ),
          ],
        ),
        const SizedBox(height: 20),

        // ---- Préférences optionnelles (C5–C6) ----
        _SectionLabel(label: 'Préférences optionnelles'),
        const SizedBox(height: 6),
        Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: Text(
            'Vous pouvez modifier ces préférences à tout moment.',
            style: GoogleFonts.nunito(
              fontSize: 11,
              color: AppColors.muted,
            ),
          ),
        ),

        _PrivacyCard(
          children: [
            _ToggleConsentRow(
              emoji: '🔔',
              iconBg: const Color(0xFFFEF3C7),
              label: 'Notifications push',
              subLabel: 'Alertes de nouvelles pubs et gains',
              value: consentNotifications,
              updating: updatingC5,
              onChanged: onNotificationsChanged,
            ),
            const Divider(height: 1, thickness: 1, color: AppColors.border),
            _ToggleConsentRow(
              emoji: '📧',
              iconBg: const Color(0xFFEDE9FE),
              label: 'E-mails promotionnels',
              subLabel: 'Offres et actualités oon.click',
              value: consentMarketing,
              updating: updatingC6,
              onChanged: onMarketingChanged,
            ),
          ],
        ),
        const SizedBox(height: 20),

        // ---- Données personnelles ----
        _SectionLabel(label: 'Données personnelles'),
        const SizedBox(height: 10),

        _PrivacyCard(
          children: [
            _ActionRow(
              emoji: '📥',
              iconBg: const Color(0xFFEBF7FE),
              label: 'Télécharger mes données',
              subLabel: 'Exporter une copie de vos informations',
              onTap: () => _exportData(context),
            ),
            const Divider(height: 1, thickness: 1, color: AppColors.border),
            _DangerActionRow(
              emoji: '🗑️',
              label: 'Supprimer mon compte',
              subLabel:
                  'Action irréversible — toutes vos données seront effacées',
              onTap: () => _confirmDeleteAccount(context),
            ),
          ],
        ),
        const SizedBox(height: 24),

        // ---- Info footer ----
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: AppColors.skyPale,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('ℹ️', style: TextStyle(fontSize: 16)),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Vos données sont traitées conformément au RGPD et aux lois ivoiriennes sur la protection des données personnelles.',
                  style: GoogleFonts.nunito(
                    fontSize: 12,
                    color: AppColors.muted,
                    height: 1.5,
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
      ],
    );
  }

  void _exportData(BuildContext context) async {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Export en cours...', style: GoogleFonts.nunito()),
        backgroundColor: AppColors.sky,
        duration: const Duration(seconds: 2),
      ),
    );

    try {
      final repo = ProviderScope.containerOf(context).read(profileRepositoryProvider);
      final data = await repo.exportData();
      final jsonStr = const JsonEncoder.withIndent('  ').convert(data);
      final dir = await getTemporaryDirectory();
      final file = File('${dir.path}/oonclick_data_export.json');
      await file.writeAsString(jsonStr);
      await Share.shareXFiles([XFile(file.path)], text: 'Mes données oon.click');
    } catch (e) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Erreur: $e', style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
        ),
      );
    }
  }

  void _confirmDeleteAccount(BuildContext context) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'Supprimer le compte',
          style: GoogleFonts.nunito(
            fontSize: 17,
            fontWeight: FontWeight.w800,
            color: AppColors.danger,
          ),
        ),
        content: Text(
          'Cette action est irréversible. Toutes vos données, gains non retirés et historique seront définitivement supprimés.',
          style: GoogleFonts.nunito(fontSize: 14, color: AppColors.muted),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: Text(
              'Annuler',
              style: GoogleFonts.nunito(color: AppColors.muted),
            ),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(ctx).pop();
              try {
                final repo = ProviderScope.containerOf(context).read(profileRepositoryProvider);
                await repo.deleteAccount();
                if (!context.mounted) return;
                ProviderScope.containerOf(context).read(profileProvider.notifier).logout();
              } catch (e) {
                if (!context.mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Erreur: $e', style: GoogleFonts.nunito()),
                    backgroundColor: AppColors.danger,
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.danger,
              minimumSize: Size.zero,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            ),
            child: Text(
              'Supprimer',
              style: GoogleFonts.nunito(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Row widgets
// ---------------------------------------------------------------------------

/// Tappable row showing a legal document with its acceptance date.
class _AcceptanceRow extends StatelessWidget {
  const _AcceptanceRow({
    required this.emoji,
    required this.iconBg,
    required this.label,
    required this.acceptedAt,
    required this.onTap,
  });

  final String emoji;
  final Color iconBg;
  final String label;
  final String acceptedAt;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: iconBg,
                borderRadius: BorderRadius.circular(10),
              ),
              child:
                  Center(child: Text(emoji, style: const TextStyle(fontSize: 17))),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: AppColors.navy,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Accepté le $acceptedAt',
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: AppColors.successLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'Accepté',
                style: GoogleFonts.nunito(
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  color: AppColors.success,
                ),
              ),
            ),
            const SizedBox(width: 6),
            const Icon(Icons.chevron_right, color: AppColors.muted, size: 16),
          ],
        ),
      ),
    );
  }
}

/// Non-toggleable row for mandatory consents (C1–C4) — lock icon.
class _LockedConsentRow extends StatelessWidget {
  const _LockedConsentRow({
    required this.emoji,
    required this.iconBg,
    required this.label,
    required this.subLabel,
    required this.acceptedAt,
  });

  final String emoji;
  final Color iconBg;
  final String label;
  final String subLabel;
  final String acceptedAt;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: iconBg,
              borderRadius: BorderRadius.circular(10),
            ),
            child:
                Center(child: Text(emoji, style: const TextStyle(fontSize: 17))),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.navy,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subLabel,
                  style: GoogleFonts.nunito(
                    fontSize: 11,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),
          // Lock icon indicating non-revocable
          Tooltip(
            message: 'Requis pour utiliser l\'application',
            child: Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: AppColors.successLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.lock_rounded,
                size: 14,
                color: AppColors.success,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Toggle row for optional consents (C5, C6).
class _ToggleConsentRow extends StatelessWidget {
  const _ToggleConsentRow({
    required this.emoji,
    required this.iconBg,
    required this.label,
    required this.subLabel,
    required this.value,
    required this.updating,
    required this.onChanged,
  });

  final String emoji;
  final Color iconBg;
  final String label;
  final String subLabel;
  final bool value;
  final bool updating;
  final Future<void> Function(bool) onChanged;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: iconBg,
              borderRadius: BorderRadius.circular(10),
            ),
            child:
                Center(child: Text(emoji, style: const TextStyle(fontSize: 17))),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.navy,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subLabel,
                  style: GoogleFonts.nunito(
                    fontSize: 11,
                    color: AppColors.muted,
                  ),
                ),
              ],
            ),
          ),
          if (updating)
            const SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: AppColors.sky,
              ),
            )
          else
            Switch(
              value: value,
              onChanged: onChanged,
              activeThumbColor: AppColors.sky,
              activeTrackColor: AppColors.skyMid,
            ),
        ],
      ),
    );
  }
}

class _ActionRow extends StatelessWidget {
  const _ActionRow({
    required this.emoji,
    required this.iconBg,
    required this.label,
    required this.subLabel,
    required this.onTap,
  });

  final String emoji;
  final Color iconBg;
  final String label;
  final String subLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: iconBg,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Center(
                  child: Text(emoji, style: const TextStyle(fontSize: 17))),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: AppColors.navy,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subLabel,
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.muted, size: 18),
          ],
        ),
      ),
    );
  }
}

class _DangerActionRow extends StatelessWidget {
  const _DangerActionRow({
    required this.emoji,
    required this.label,
    required this.subLabel,
    required this.onTap,
  });

  final String emoji;
  final String label;
  final String subLabel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        child: Row(
          children: [
            Container(
              width: 36,
              height: 36,
              decoration: BoxDecoration(
                color: AppColors.dangerLight,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Center(
                  child: Text(emoji, style: const TextStyle(fontSize: 17))),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: AppColors.danger,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    subLabel,
                    style: GoogleFonts.nunito(
                      fontSize: 11,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.danger, size: 18),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

class _SectionLabel extends StatelessWidget {
  const _SectionLabel({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: GoogleFonts.nunito(
        fontSize: 13,
        fontWeight: FontWeight.w800,
        color: AppColors.muted,
        letterSpacing: 0.3,
      ),
    );
  }
}

class _PrivacyCard extends StatelessWidget {
  const _PrivacyCard({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(children: children),
    );
  }
}
