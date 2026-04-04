import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';
import '../../data/models/referral_tree_model.dart';
import '../providers/profile_provider.dart';

// ---------------------------------------------------------------------------
// Referral screen (full-screen, outside shell)
// ---------------------------------------------------------------------------

class ReferralScreen extends ConsumerWidget {
  const ReferralScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final statsAsync = ref.watch(profileStatsProvider);
    final treeAsync  = ref.watch(referralTreeProvider);

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // ---- Gradient header ----
          statsAsync.when(
            loading: () => _Header(referralCount: 0, isLoading: true),
            error: (e, st) => const _Header(referralCount: 0),
            data: (stats) => _Header(referralCount: stats.referralCount),
          ),

          // ---- Scrollable content ----
          Expanded(
            child: statsAsync.when(
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.sky),
              ),
              error: (e, _) => Center(
                child: Text(
                  e.toString(),
                  style: GoogleFonts.nunito(color: AppColors.muted),
                ),
              ),
              data: (stats) => _ReferralContent(
                referralCode:  stats.referralCode,
                referralCount: stats.referralCount,
                treeAsync:     treeAsync,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Header with navy → sky3 gradient
// ---------------------------------------------------------------------------

class _Header extends StatelessWidget {
  const _Header({
    required this.referralCount,
    this.isLoading = false,
  });

  final int referralCount;
  final bool isLoading;

  @override
  Widget build(BuildContext context) {
    final topPadding = MediaQuery.of(context).padding.top;

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.navy, AppColors.sky3],
        ),
      ),
      padding: EdgeInsets.fromLTRB(14, topPadding + 10, 14, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Back button + title
          Row(
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
                'Invitez vos amis',
                style: GoogleFonts.nunito(
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          // Earnings stats row
          Container(
            decoration: BoxDecoration(
              color: Colors.white.withAlpha(31),
              borderRadius: BorderRadius.circular(12),
            ),
            child: IntrinsicHeight(
              child: Row(
                children: [
                  _HeaderStatCell(
                    value: '+${AppConfig.referralBonus} F',
                    label: 'Pour vous',
                  ),
                  _HeaderDivider(),
                  _HeaderStatCell(
                    value: '+${AppConfig.referralBonus} F',
                    label: 'Pour votre filleul',
                  ),
                  _HeaderDivider(),
                  _HeaderStatCell(
                    value: isLoading ? '—' : '$referralCount',
                    label: 'Filleuls actifs',
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _HeaderStatCell extends StatelessWidget {
  const _HeaderStatCell({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 4),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              value,
              style: GoogleFonts.nunito(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: GoogleFonts.nunito(
                fontSize: 9,
                color: Colors.white.withAlpha(191),
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _HeaderDivider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(width: 1, color: Colors.white.withAlpha(50));
  }
}

// ---------------------------------------------------------------------------
// Scrollable referral content
// ---------------------------------------------------------------------------

class _ReferralContent extends StatelessWidget {
  const _ReferralContent({
    required this.referralCode,
    required this.referralCount,
    required this.treeAsync,
  });

  final String referralCode;
  final int referralCount;
  final AsyncValue<ReferralTreeModel> treeAsync;

  void _copyCode(BuildContext context, String text) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          'Lien copié !',
          style: GoogleFonts.nunito(fontSize: 13),
        ),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  String get _shareLink => 'https://oon.click/invite/$referralCode';

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(12),
      children: [
        // 1. Referral code card
        _ReferralCodeCard(
          referralCode: referralCode,
          onCopy: () => _copyCode(context, referralCode),
        ),
        const SizedBox(height: 10),

        // 2. Share row
        _ShareRow(
          onWhatsApp: () => _copyCode(context, _shareLink),
          onSms: () => _copyCode(context, _shareLink),
          onCopyLink: () => _copyCode(context, _shareLink),
        ),
        const SizedBox(height: 14),

        // 3. Multi-level tree section
        treeAsync.when(
          loading: () => const _TreeSectionShimmer(),
          error: (e, st) => _LevelSection(
            label: 'MES FILLEULS ($referralCount)',
            count: referralCount,
            earnings: 0,
            referrals: const [],
            emptyMessage: 'Aucun filleul pour l\'instant',
          ),
          data: (tree) => _TreeSection(tree: tree),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Tree section — renders level 1 and conditionally level 2
// ---------------------------------------------------------------------------

class _TreeSection extends StatelessWidget {
  const _TreeSection({required this.tree});

  final ReferralTreeModel tree;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Niveau 1
        _LevelSection(
          label: 'MES FILLEULS DIRECTS (${tree.level1.count})',
          count: tree.level1.count,
          earnings: tree.level1.earnings,
          referrals: tree.level1.referrals,
          emptyMessage: 'Aucun filleul pour l\'instant',
        ),

        // Niveau 2 uniquement si la feature est activée
        if (tree.multiLevelEnabled) ...[
          const SizedBox(height: 14),
          _LevelSection(
            label: 'FILLEULS INDIRECTS — NIVEAU 2 (${tree.level2.count})',
            count: tree.level2.count,
            earnings: tree.level2.earnings,
            referrals: tree.level2.referrals,
            emptyMessage: 'Pas encore de filleuls de niveau 2',
            accentColor: AppColors.skyMid,
          ),
          const SizedBox(height: 14),
          _TotalEarningsCard(totalEarnings: tree.totalEarnings),
        ],
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Level section card
// ---------------------------------------------------------------------------

class _LevelSection extends StatelessWidget {
  const _LevelSection({
    required this.label,
    required this.count,
    required this.earnings,
    required this.referrals,
    required this.emptyMessage,
    this.accentColor,
  });

  final String label;
  final int count;
  final int earnings;
  final List<ReferralEntry> referrals;
  final String emptyMessage;
  final Color? accentColor;

  @override
  Widget build(BuildContext context) {
    final accent = accentColor ?? AppColors.navy;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                label,
                style: GoogleFonts.nunito(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: accent,
                  letterSpacing: 0.5,
                ),
              ),
            ),
            if (earnings > 0)
              Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: AppColors.success.withAlpha(25),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  '+$earnings F',
                  style: GoogleFonts.nunito(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: AppColors.success,
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 8),
        if (referrals.isEmpty)
          _EmptyState(message: emptyMessage)
        else
          ...referrals.map((r) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: _ReferralEntryTile(entry: r),
              )),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Individual referral tile
// ---------------------------------------------------------------------------

class _ReferralEntryTile extends StatelessWidget {
  const _ReferralEntryTile({required this.entry});

  final ReferralEntry entry;

  @override
  Widget build(BuildContext context) {
    final joinDate = entry.joinedAt;
    final joinLabel = joinDate != null
        ? '${joinDate.day.toString().padLeft(2, '0')}/'
              '${joinDate.month.toString().padLeft(2, '0')}/'
              '${joinDate.year}'
        : '';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: const BoxDecoration(
              shape: BoxShape.circle,
              gradient: LinearGradient(
                colors: [AppColors.skyMid, AppColors.sky],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Center(
              child: Text(
                entry.name.isNotEmpty
                    ? entry.name[0].toUpperCase()
                    : '?',
                style: GoogleFonts.nunito(
                  fontSize: 14,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  entry.name,
                  style: GoogleFonts.nunito(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.navy,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                if (joinLabel.isNotEmpty)
                  Text(
                    'Inscrit le $joinLabel',
                    style: GoogleFonts.nunito(
                      fontSize: 10,
                      color: AppColors.muted,
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Total earnings summary card (shown when multi-level is enabled)
// ---------------------------------------------------------------------------

class _TotalEarningsCard extends StatelessWidget {
  const _TotalEarningsCard({required this.totalEarnings});

  final int totalEarnings;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.navy, AppColors.sky3],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Text('💰', style: TextStyle(fontSize: 24)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Gains totaux de parrainage',
                  style: GoogleFonts.nunito(
                    fontSize: 11,
                    color: Colors.white.withAlpha(200),
                  ),
                ),
                Text(
                  '+$totalEarnings FCFA',
                  style: GoogleFonts.nunito(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Empty state
// ---------------------------------------------------------------------------

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          const Text('👥', style: TextStyle(fontSize: 28)),
          const SizedBox(height: 8),
          Text(
            message,
            style: GoogleFonts.nunito(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: AppColors.navy,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 4),
          Text(
            'Partagez votre code et commencez à gagner !',
            style: GoogleFonts.nunito(
              fontSize: 11,
              color: AppColors.muted,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Loading shimmer placeholder for tree section
// ---------------------------------------------------------------------------

class _TreeSectionShimmer extends StatelessWidget {
  const _TreeSectionShimmer();

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          height: 14,
          width: 160,
          decoration: BoxDecoration(
            color: AppColors.border,
            borderRadius: BorderRadius.circular(4),
          ),
        ),
        const SizedBox(height: 8),
        ...List.generate(
          2,
          (_) => Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Container(
              height: 54,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(10),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Referral code card
// ---------------------------------------------------------------------------

class _ReferralCodeCard extends StatelessWidget {
  const _ReferralCodeCard({
    required this.referralCode,
    required this.onCopy,
  });

  final String referralCode;
  final VoidCallback onCopy;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.sky, AppColors.sky3],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: AppColors.sky.withAlpha(60),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'VOTRE CODE DE PARRAINAGE',
            style: GoogleFonts.nunito(
              fontSize: 10,
              color: Colors.white,
              letterSpacing: 1,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            referralCode.isNotEmpty ? referralCode : '—',
            style: GoogleFonts.nunito(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: Colors.white,
              letterSpacing: 2,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Partagez ce code et gagnez ${AppConfig.referralBonus} F pour chaque ami inscrit.',
            style: GoogleFonts.nunito(
              fontSize: 10,
              color: Colors.white.withAlpha(230),
            ),
          ),
          const SizedBox(height: 12),
          GestureDetector(
            onTap: referralCode.isNotEmpty ? onCopy : null,
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white.withAlpha(51),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text('📋', style: TextStyle(fontSize: 13)),
                  const SizedBox(width: 6),
                  Text(
                    'Copier le code',
                    style: GoogleFonts.nunito(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Share row
// ---------------------------------------------------------------------------

class _ShareRow extends StatelessWidget {
  const _ShareRow({
    required this.onWhatsApp,
    required this.onSms,
    required this.onCopyLink,
  });

  final VoidCallback onWhatsApp;
  final VoidCallback onSms;
  final VoidCallback onCopyLink;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _ShareButton(
            emoji: '💬',
            label: 'WhatsApp',
            bgColor: const Color(0xFFDCFCE7),
            onTap: onWhatsApp,
          ),
        ),
        const SizedBox(width: 7),
        Expanded(
          child: _ShareButton(
            emoji: '✉️',
            label: 'SMS',
            bgColor: AppColors.skyPale,
            onTap: onSms,
          ),
        ),
        const SizedBox(width: 7),
        Expanded(
          child: _ShareButton(
            emoji: '🔗',
            label: 'Copier lien',
            bgColor: const Color(0xFFF3F4F6),
            onTap: onCopyLink,
          ),
        ),
      ],
    );
  }
}

class _ShareButton extends StatelessWidget {
  const _ShareButton({
    required this.emoji,
    required this.label,
    required this.bgColor,
    required this.onTap,
  });

  final String emoji;
  final String label;
  final Color bgColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(emoji, style: const TextStyle(fontSize: 18)),
            const SizedBox(height: 4),
            Text(
              label,
              style: GoogleFonts.nunito(
                fontSize: 9,
                fontWeight: FontWeight.w700,
                color: AppColors.navy,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
