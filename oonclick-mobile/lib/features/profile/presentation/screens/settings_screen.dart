import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../auth/data/models/user_model.dart';
import '../../../notifications/presentation/providers/notifications_provider.dart';
import '../providers/profile_provider.dart';

// ---------------------------------------------------------------------------
// Settings screen (full-screen, outside shell)
// ---------------------------------------------------------------------------

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final userAsync = ref.watch(profileProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // ---- Navy top bar ----
          _TopBar(),

          // ---- Scrollable content ----
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
              data: (user) => _SettingsContent(user: user),
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

class _TopBar extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final topPadding = MediaQuery.of(context).padding.top;

    return Container(
      color: AppColors.navy,
      padding: EdgeInsets.fromLTRB(12, topPadding + 8, 12, 12),
      child: Row(
        children: [
          GestureDetector(
            onTap: () => context.pop(),
            child: Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(31),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.arrow_back_ios_new,
                color: Colors.white,
                size: 14,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Text(
            'Paramètres',
            style: GoogleFonts.nunito(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Scrollable settings content
// ---------------------------------------------------------------------------

class _SettingsContent extends ConsumerWidget {
  const _SettingsContent({required this.user});

  final UserModel? user;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final name = user?.name ?? 'Utilisateur';
    final contact = user?.email ?? user?.phone ?? '';
    final initials = _initials(name);
    final unreadCount = ref.watch(unreadCountProvider);
    final notificationBadge = unreadCount > 0 ? '$unreadCount' : null;

    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        // 1. Mini profile card
        _MiniProfileCard(
          initials: initials,
          name: name,
          contact: contact,
        ),
        const SizedBox(height: 12),

        // 2. Settings list
        _SettingsList(
          items: [
            if (user?.isAdvertiser ?? false)
              _SettingsItemData(
                emoji: '📢',
                iconBg: const Color(0xFFFEE2E2),
                label: 'Mes Campagnes',
                subLabel: 'Gérer vos campagnes publicitaires',
                onTap: () => context.push('/campaigns'),
              ),
            _SettingsItemData(
              emoji: '👤',
              iconBg: const Color(0xFFEBF7FE),
              label: 'Informations personnelles',
              onTap: () => context.push('/profile/edit'),
            ),
            _SettingsItemData(
              emoji: '🔔',
              iconBg: const Color(0xFFFEF3C7),
              label: 'Notifications',
              badge: notificationBadge,
              onTap: () => context.push('/profile/notifications'),
            ),
            _SettingsItemData(
              emoji: '📱',
              iconBg: const Color(0xFFDCFCE7),
              label: 'Opérateur mobile',
              subLabel: user?.phone ?? '',
              onTap: () => context.push('/profile/edit'),
            ),
            _SettingsItemData(
              emoji: '🔒',
              iconBg: const Color(0xFFEDE9FE),
              label: 'Sécurité',
              onTap: () => context.push('/profile/security'),
            ),
            _SettingsItemData(
              emoji: '🤝',
              iconBg: const Color(0xFFD1FAE5),
              label: 'Parrainage',
              onTap: () => context.push('/profile/referral'),
            ),
            _SettingsItemData(
              emoji: '❓',
              iconBg: const Color(0xFFF3F4F6),
              label: 'Aide & Support',
              onTap: () => context.push('/profile/help'),
            ),
            _SettingsItemData(
              emoji: '📄',
              iconBg: const Color(0xFFEBF7FE),
              label: 'Conditions d\'utilisation',
              subLabel: 'CGU oon.click',
              onTap: () => _openLegalPage('/cgu'),
            ),
            _SettingsItemData(
              emoji: '🛡️',
              iconBg: const Color(0xFFE0F2FE),
              label: 'Politique de confidentialité',
              onTap: () => _openLegalPage('/confidentialite'),
            ),
            _SettingsItemData(
              emoji: '🔏',
              iconBg: const Color(0xFFEDE9FE),
              label: 'Gérer mes consentements',
              subLabel: 'Notifications, e-mails promotionnels',
              onTap: () => context.push('/profile/privacy'),
            ),
          ],
        ),
        const SizedBox(height: 12),

        // 3. Logout item
        _LogoutItem(
          onTap: () => _confirmLogout(context, ref),
        ),
        const SizedBox(height: 20),

        // 4. Version footer
        Center(
          child: Text(
            'oon.click v1.4.2 · Côte d\'Ivoire',
            style: GoogleFonts.nunito(
              fontSize: 9,
              color: AppColors.muted,
            ),
          ),
        ),
        const SizedBox(height: 12),
      ],
    );
  }

  void _openLegalPage(String path) {
    final base = AppConfig.baseUrl.replaceAll(RegExp(r'/api$'), '');
    final url = Uri.parse('$base$path');
    launchUrl(url, mode: LaunchMode.externalApplication);
  }

  String _initials(String text) {
    final parts = text.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    if (text.length >= 2) return text.substring(0, 2).toUpperCase();
    return text.toUpperCase();
  }

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          'Déconnexion',
          style: GoogleFonts.nunito(
            fontSize: 18,
            fontWeight: FontWeight.w800,
            color: AppColors.navy,
          ),
        ),
        content: Text(
          'Souhaitez-vous vraiment vous déconnecter ?',
          style: GoogleFonts.nunito(fontSize: 14, color: AppColors.muted),
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
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
              await ref.read(profileProvider.notifier).logout();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.danger,
              minimumSize: Size.zero,
              padding:
                  const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            ),
            child: Text(
              'Déconnecter',
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
// Mini profile card
// ---------------------------------------------------------------------------

class _MiniProfileCard extends StatelessWidget {
  const _MiniProfileCard({
    required this.initials,
    required this.name,
    required this.contact,
  });

  final String initials;
  final String name;
  final String contact;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.skyPale, AppColors.white],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        border: Border.all(color: AppColors.border, width: 1.5),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          // Avatar
          Container(
            width: 44,
            height: 44,
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
              gradient: AppColors.navyGradientDiagonal,
            ),
            child: Center(
              child: Text(
                initials,
                style: GoogleFonts.nunito(
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                  color: Colors.white,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w900,
                    color: AppColors.navy,
                  ),
                ),
                if (contact.isNotEmpty)
                  Text(
                    contact,
                    style: GoogleFonts.nunito(
                      fontSize: 10,
                      color: AppColors.muted,
                    ),
                  ),
              ],
            ),
          ),
          GestureDetector(
            onTap: () => context.push('/profile/edit'),
            child: Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: AppColors.skyPale,
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.edit_outlined,
                color: AppColors.sky,
                size: 16,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Settings list
// ---------------------------------------------------------------------------

class _SettingsItemData {
  const _SettingsItemData({
    required this.emoji,
    required this.iconBg,
    required this.label,
    this.subLabel,
    this.badge,
    required this.onTap,
  });

  final String emoji;
  final Color iconBg;
  final String label;
  final String? subLabel;
  final String? badge;
  final VoidCallback onTap;
}

class _SettingsList extends StatelessWidget {
  const _SettingsList({required this.items});

  final List<_SettingsItemData> items;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: List.generate(items.length, (index) {
          final item = items[index];
          final isLast = index == items.length - 1;
          return Column(
            children: [
              _SettingsItem(data: item),
              if (!isLast)
                Divider(
                  height: 1,
                  thickness: 1,
                  color: AppColors.border,
                  indent: 52,
                ),
            ],
          );
        }),
      ),
    );
  }
}

class _SettingsItem extends StatelessWidget {
  const _SettingsItem({required this.data});

  final _SettingsItemData data;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: data.onTap,
      borderRadius: BorderRadius.circular(14),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
        child: Row(
          children: [
            // Icon square
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: data.iconBg,
                borderRadius: BorderRadius.circular(9),
              ),
              child: Center(
                child: Text(
                  data.emoji,
                  style: const TextStyle(fontSize: 15),
                ),
              ),
            ),
            const SizedBox(width: 12),
            // Label + sublabel
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    data.label,
                    style: GoogleFonts.nunito(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: AppColors.navy,
                    ),
                  ),
                  if (data.subLabel != null && data.subLabel!.isNotEmpty)
                    Text(
                      data.subLabel!,
                      style: GoogleFonts.nunito(
                        fontSize: 10,
                        color: AppColors.muted,
                      ),
                    ),
                ],
              ),
            ),
            // Badge
            if (data.badge != null) ...[
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.sky,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  data.badge!,
                  style: GoogleFonts.nunito(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(width: 6),
            ],
            const Icon(
              Icons.chevron_right,
              color: AppColors.muted,
              size: 18,
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Logout item
// ---------------------------------------------------------------------------

class _LogoutItem extends StatelessWidget {
  const _LogoutItem({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        decoration: BoxDecoration(
          color: AppColors.white,
          border: Border.all(color: AppColors.dangerLight),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: AppColors.dangerLight,
                borderRadius: BorderRadius.circular(9),
              ),
              child: const Center(
                child: Text('🚪', style: TextStyle(fontSize: 15)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                'Déconnexion',
                style: GoogleFonts.nunito(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppColors.danger,
                ),
              ),
            ),
            const Icon(
              Icons.chevron_right,
              color: AppColors.danger,
              size: 18,
            ),
          ],
        ),
      ),
    );
  }
}
