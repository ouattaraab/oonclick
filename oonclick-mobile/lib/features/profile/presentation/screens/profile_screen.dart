import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../shared/widgets/trust_score_gauge.dart';
import '../../../auth/data/models/user_model.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../../data/models/profile_stats_model.dart';
import '../providers/profile_provider.dart';

// ---------------------------------------------------------------------------
// Écran principal
// ---------------------------------------------------------------------------

/// Onglet Profil dans la navigation principale.
class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final userAsync = ref.watch(profileProvider);
    final statsAsync = ref.watch(profileStatsProvider);

    return Scaffold(
      backgroundColor: AppTheme.bgPage,
      body: userAsync.when(
        loading: () => const _ProfileSkeleton(),
        error: (e, _) => _ProfileError(message: e.toString()),
        data: (user) {
          if (user == null) {
            return const _ProfileError(message: 'Utilisateur introuvable.');
          }
          return RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: () => ref.read(profileProvider.notifier).refresh(),
            child: CustomScrollView(
              slivers: [
                _ProfileAppBar(user: user),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      const SizedBox(height: 16),
                      _KycSection(user: user),
                      const SizedBox(height: 16),
                      statsAsync.when(
                        loading: () => const _StatsSkeleton(),
                        error: (error, stack) => const SizedBox.shrink(),
                        data: (stats) => Column(
                          children: [
                            _TrustScoreSection(stats: stats),
                            const SizedBox(height: 16),
                            _StatsSection(stats: stats),
                            const SizedBox(height: 16),
                            _ReferralSection(stats: stats),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      _ActionsSection(user: user),
                    ]),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// AppBar SliverAppBar avec header profil
// ---------------------------------------------------------------------------

class _ProfileAppBar extends ConsumerWidget {
  const _ProfileAppBar({required this.user});

  final UserModel user;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final initials = _initials(user.name ?? user.phone);
    final avatarColor = _avatarColor(user.trustScore);

    return SliverAppBar(
      expandedHeight: 200,
      pinned: true,
      backgroundColor: AppTheme.bgCard,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppTheme.primary, AppTheme.primaryDark],
            ),
          ),
          child: SafeArea(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Avatar circulaire
                CircleAvatar(
                  radius: 40,
                  backgroundColor: avatarColor.withAlpha(50),
                  child: Text(
                    initials,
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w700,
                      color: avatarColor,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                // Nom / téléphone
                Text(
                  user.name ?? 'Utilisateur',
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  Formatters.phone(user.phone),
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.white.withAlpha(200),
                  ),
                ),
                const SizedBox(height: 8),
                // Badge rôle
                _RoleBadge(role: user.role),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _initials(String text) {
    final parts = text.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    if (text.length >= 2) return text.substring(0, 2).toUpperCase();
    return text.toUpperCase();
  }

  Color _avatarColor(int trustScore) {
    if (trustScore >= 70) return const Color(0xFF00C853);
    if (trustScore >= 40) return AppTheme.primary;
    return AppTheme.error;
  }
}

class _RoleBadge extends StatelessWidget {
  const _RoleBadge({required this.role});

  final String role;

  @override
  Widget build(BuildContext context) {
    final label = switch (role) {
      'subscriber' => 'Abonné',
      'advertiser' => 'Annonceur',
      'admin' => 'Administrateur',
      _ => role,
    };
    final color = role == 'advertiser' ? AppTheme.warning : Colors.white;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: color.withAlpha(40),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withAlpha(150)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section KYC
// ---------------------------------------------------------------------------

class _KycSection extends StatelessWidget {
  const _KycSection({required this.user});

  final UserModel user;

  static const _kycLabels = [
    'Non vérifié',
    'KYC Niveau 1',
    'KYC Niveau 2',
    'KYC Niveau 3',
  ];

  static const _kycLimits = [
    'Aucun retrait autorisé',
    'Retrait jusqu\'à 10 000 FCFA',
    'Retrait jusqu\'à 50 000 FCFA',
    'Retrait illimité',
  ];

  @override
  Widget build(BuildContext context) {
    final level = user.kycLevel.clamp(0, 3);
    return _Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.verified_user_outlined, color: AppTheme.primary, size: 20),
              const SizedBox(width: 8),
              const Text(
                'Vérification KYC',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: AppTheme.textPrimary,
                ),
              ),
              const Spacer(),
              if (level < 2)
                OutlinedButton(
                  onPressed: () {
                    // TODO: navigation vers le flow KYC
                  },
                  style: OutlinedButton.styleFrom(
                    minimumSize: Size.zero,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                  child: const Text('Améliorer'),
                ),
            ],
          ),
          const SizedBox(height: 12),
          // Barre de progression
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: level / 3,
              minHeight: 8,
              backgroundColor: AppTheme.divider,
              valueColor: const AlwaysStoppedAnimation<Color>(AppTheme.primary),
            ),
          ),
          const SizedBox(height: 8),
          // Indicateurs de niveaux
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: List.generate(4, (i) {
              final isReached = i <= level;
              return Text(
                '$i',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: isReached ? AppTheme.primary : AppTheme.textHint,
                ),
              );
            }),
          ),
          const SizedBox(height: 8),
          Text(
            _kycLabels[level],
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppTheme.textPrimary,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            _kycLimits[level],
            style: const TextStyle(
              fontSize: 13,
              color: AppTheme.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section Trust Score
// ---------------------------------------------------------------------------

class _TrustScoreSection extends StatelessWidget {
  const _TrustScoreSection({required this.stats});

  final ProfileStatsModel stats;

  @override
  Widget build(BuildContext context) {
    return _Card(
      child: Row(
        children: [
          TrustScoreGauge(score: stats.trustScore, size: 100),
          const SizedBox(width: 20),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Score de confiance',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.textPrimary,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  'Score de confiance : ${stats.trustScore}/100',
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppTheme.textSecondary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  _trustLabel(stats.trustScore),
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: _trustColor(stats.trustScore),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _trustLabel(int score) {
    if (score >= 70) return 'Excellent';
    if (score >= 40) return 'Moyen';
    return 'A améliorer';
  }

  Color _trustColor(int score) {
    if (score >= 70) return const Color(0xFF00C853);
    if (score >= 40) return AppTheme.primary;
    return AppTheme.error;
  }
}

// ---------------------------------------------------------------------------
// Section Statistiques
// ---------------------------------------------------------------------------

class _StatsSection extends StatelessWidget {
  const _StatsSection({required this.stats});

  final ProfileStatsModel stats;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Mes statistiques',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: AppTheme.textPrimary,
          ),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _StatCard(
                icon: Icons.play_circle_outline,
                iconColor: AppTheme.primary,
                label: 'Vues totales',
                value: Formatters.compact(stats.totalViews),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _StatCard(
                icon: Icons.trending_up_outlined,
                iconColor: const Color(0xFF00C853),
                label: 'FCFA gagnés',
                value: Formatters.currency(stats.totalEarned),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _StatCard(
                icon: Icons.account_balance_outlined,
                iconColor: AppTheme.warning,
                label: 'FCFA retirés',
                value: Formatters.currency(stats.totalWithdrawn),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.iconColor,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final Color iconColor;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppTheme.bgCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.divider),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: iconColor, size: 22),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppTheme.textPrimary,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              color: AppTheme.textSecondary,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section Parrainage
// ---------------------------------------------------------------------------

class _ReferralSection extends StatelessWidget {
  const _ReferralSection({required this.stats});

  final ProfileStatsModel stats;

  @override
  Widget build(BuildContext context) {
    return _Card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.people_outline, color: AppTheme.primary, size: 20),
              const SizedBox(width: 8),
              const Text(
                'Parrainage',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: AppTheme.textPrimary,
                ),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.primary.withAlpha(20),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${stats.referralCount} filleul${stats.referralCount > 1 ? 's' : ''}',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.primary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Code de parrainage
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: AppTheme.bgPage,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: AppTheme.divider),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    stats.referralCode.isNotEmpty
                        ? stats.referralCode
                        : '—',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 2,
                      color: AppTheme.textPrimary,
                    ),
                  ),
                ),
                IconButton(
                  onPressed: stats.referralCode.isNotEmpty
                      ? () {
                          Clipboard.setData(
                            ClipboardData(text: stats.referralCode),
                          );
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Code copié dans le presse-papier'),
                              duration: Duration(seconds: 2),
                            ),
                          );
                        }
                      : null,
                  icon: const Icon(Icons.copy_outlined, color: AppTheme.primary),
                  tooltip: 'Copier le code',
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              const Icon(Icons.info_outline, color: AppTheme.textSecondary, size: 16),
              const SizedBox(width: 6),
              const Expanded(
                child: Text(
                  'Gagnez 200 FCFA pour chaque ami parrainé',
                  style: TextStyle(
                    fontSize: 13,
                    color: AppTheme.textSecondary,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Section Actions
// ---------------------------------------------------------------------------

class _ActionsSection extends ConsumerWidget {
  const _ActionsSection({required this.user});

  final UserModel user;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return _Card(
      child: Column(
        children: [
          // Modifier le profil
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const CircleAvatar(
              backgroundColor: Color(0x15FF6B00),
              child: Icon(Icons.edit_outlined, color: AppTheme.primary, size: 20),
            ),
            title: const Text(
              'Modifier le profil',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w500,
                color: AppTheme.textPrimary,
              ),
            ),
            trailing: const Icon(Icons.chevron_right, color: AppTheme.textSecondary),
            onTap: () => _showEditProfileSheet(context, ref, user),
          ),
          const Divider(height: 1),
          // Déconnexion
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: CircleAvatar(
              backgroundColor: AppTheme.error.withAlpha(20),
              child: const Icon(Icons.logout, color: AppTheme.error, size: 20),
            ),
            title: const Text(
              'Déconnexion',
              style: TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w500,
                color: AppTheme.error,
              ),
            ),
            trailing: const Icon(Icons.chevron_right, color: AppTheme.textSecondary),
            onTap: () => _confirmLogout(context, ref),
          ),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Dialog déconnexion
  // ---------------------------------------------------------------------------

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Souhaitez-vous vraiment vous déconnecter ?'),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.of(ctx).pop();
              await ref.read(profileProvider.notifier).logout();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.error,
              minimumSize: Size.zero,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            ),
            child: const Text('Déconnecter'),
          ),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Bottom sheet modification profil
  // ---------------------------------------------------------------------------

  void _showEditProfileSheet(BuildContext context, WidgetRef ref, UserModel user) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppTheme.bgCard,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => _EditProfileSheet(user: user),
    );
  }
}

// ---------------------------------------------------------------------------
// Bottom sheet de modification
// ---------------------------------------------------------------------------

class _EditProfileSheet extends ConsumerStatefulWidget {
  const _EditProfileSheet({required this.user});

  final UserModel user;

  @override
  ConsumerState<_EditProfileSheet> createState() => _EditProfileSheetState();
}

class _EditProfileSheetState extends ConsumerState<_EditProfileSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _cityCtrl;
  late final TextEditingController _operatorCtrl;
  late final TextEditingController _interestsCtrl;

  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _cityCtrl = TextEditingController();
    _operatorCtrl = TextEditingController();
    _interestsCtrl = TextEditingController();
  }

  @override
  void dispose() {
    _cityCtrl.dispose();
    _operatorCtrl.dispose();
    _interestsCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _isLoading = true);
    try {
      final interests = _interestsCtrl.text
          .split(',')
          .map((s) => s.trim())
          .where((s) => s.isNotEmpty)
          .toList();

      final data = <String, dynamic>{};
      if (_cityCtrl.text.isNotEmpty) data['city'] = _cityCtrl.text.trim();
      if (_operatorCtrl.text.isNotEmpty) {
        data['operator'] = _operatorCtrl.text.trim();
      }
      if (interests.isNotEmpty) data['interests'] = interests;

      if (data.isNotEmpty) {
        await ref.read(authProvider.notifier).completeProfile(data);
      }

      if (mounted) Navigator.of(context).pop();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Padding(
      padding: EdgeInsets.fromLTRB(24, 16, 24, 24 + bottomInset),
      child: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Poignée
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppTheme.divider,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Modifier le profil',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w700,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: _cityCtrl,
              decoration: const InputDecoration(
                labelText: 'Ville',
                hintText: 'Ex : Abidjan',
                prefixIcon: Icon(Icons.location_city_outlined),
              ),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _operatorCtrl,
              decoration: const InputDecoration(
                labelText: 'Opérateur mobile',
                hintText: 'Ex : MTN, Orange',
                prefixIcon: Icon(Icons.sim_card_outlined),
              ),
            ),
            const SizedBox(height: 14),
            TextFormField(
              controller: _interestsCtrl,
              decoration: const InputDecoration(
                labelText: 'Centres d\'intérêt',
                hintText: 'Ex : Sport, Musique, Tech',
                prefixIcon: Icon(Icons.interests_outlined),
                helperText: 'Séparez les centres d\'intérêt par des virgules',
              ),
              maxLines: 2,
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _isLoading ? null : _save,
              child: _isLoading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Text('Enregistrer'),
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Widgets partagés internes
// ---------------------------------------------------------------------------

class _Card extends StatelessWidget {
  const _Card({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppTheme.bgCard,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.divider),
      ),
      child: child,
    );
  }
}

// ---------------------------------------------------------------------------
// Skeleton de chargement
// ---------------------------------------------------------------------------

class _ProfileSkeleton extends StatelessWidget {
  const _ProfileSkeleton();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFE0E0E0),
      highlightColor: const Color(0xFFF5F5F5),
      child: ListView(
        children: [
          Container(height: 200, color: Colors.white),
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(
              children: List.generate(
                4,
                (i) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Container(
                    height: 80,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatsSkeleton extends StatelessWidget {
  const _StatsSkeleton();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: const Color(0xFFE0E0E0),
      highlightColor: const Color(0xFFF5F5F5),
      child: Column(
        children: List.generate(
          3,
          (i) => Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Container(
              height: 80,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Écran d'erreur
// ---------------------------------------------------------------------------

class _ProfileError extends StatelessWidget {
  const _ProfileError({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, color: AppTheme.error, size: 56),
            const SizedBox(height: 16),
            const Text(
              'Une erreur est survenue',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppTheme.textSecondary),
            ),
          ],
        ),
      ),
    );
  }
}
