import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../providers/wallet_provider.dart';

// ---------------------------------------------------------------------------
// Operator data
// ---------------------------------------------------------------------------

class _Operator {
  const _Operator({
    required this.id,
    required this.name,
    required this.shortName,
    required this.color,
    this.textColor = Colors.white,
  });
  final String id;
  final String name;
  final String shortName;
  final Color color;
  final Color textColor;
}

const _operators = [
  _Operator(
    id: 'orange',
    name: 'Orange Money',
    shortName: 'OM',
    color: Color(0xFFFF6600),
  ),
  _Operator(
    id: 'mtn',
    name: 'MTN Money',
    shortName: 'MTN',
    color: Color(0xFFFFC107),
    textColor: Color(0xFF1B2A6E),
  ),
  _Operator(
    id: 'moov',
    name: 'Moov Money',
    shortName: 'MV',
    color: Color(0xFF003087),
  ),
  _Operator(
    id: 'wave',
    name: 'Wave',
    shortName: 'WV',
    color: Color(0xFF1CC9BE),
  ),
];

// ---------------------------------------------------------------------------
// Withdrawal Screen (full page — replaces bottom sheet)
// ---------------------------------------------------------------------------

class WithdrawalScreen extends ConsumerStatefulWidget {
  const WithdrawalScreen({super.key});

  @override
  ConsumerState<WithdrawalScreen> createState() => _WithdrawalScreenState();
}

class _WithdrawalScreenState extends ConsumerState<WithdrawalScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amountCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();

  String? _selectedOperatorId;
  bool _isLoading = false;

  @override
  void dispose() {
    _amountCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  int get _enteredAmount =>
      int.tryParse(_amountCtrl.text.replaceAll(' ', '')) ?? 0;

  int get _currentBalance =>
      ref.read(walletProvider).valueOrNull?.balance ?? 0;

  bool get _isKycSatisfied {
    final user = ref.read(currentUserProvider);
    return user != null && user.kycLevel >= 1;
  }

  /// Client-side fee estimate (1%). Actual fee is validated server-side.
  int get _fee => (_enteredAmount * 0.01).round();
  int get _received => _enteredAmount - _fee;

  Future<void> _confirm() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedOperatorId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Veuillez sélectionner un opérateur',
              style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
        ),
      );
      return;
    }

    // Show confirmation bottom sheet
    final confirmed = await _showConfirmSheet();
    if (!confirmed) return;

    setState(() => _isLoading = true);

    try {
      final op =
          _operators.firstWhere((o) => o.id == _selectedOperatorId).name;
      await ref.read(walletProvider.notifier).withdraw(
            amount: _enteredAmount,
            operator: op,
            phone: '+225${_phoneCtrl.text.trim()}', // CI country code
          );
      if (!mounted) return;
      await _showSuccessSheet();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString(), style: GoogleFonts.nunito()),
          backgroundColor: AppColors.danger,
        ),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<bool> _showConfirmSheet() async {
    final op = _operators.firstWhere(
      (o) => o.id == _selectedOperatorId,
      orElse: () => _operators.first,
    );
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _ConfirmBottomSheet(
        operatorName: op.name,
        operatorColor: op.color,
        operatorTextColor: op.textColor,
        operatorShortName: op.shortName,
        amount: _enteredAmount,
        fee: _fee,
        received: _received,
        balanceAfter: _currentBalance - _enteredAmount,
        phone: '+225${_phoneCtrl.text.trim()}', // CI country code
      ),
    );
    return result ?? false;
  }

  Future<void> _showSuccessSheet() async {
    final op = _operators.firstWhere(
      (o) => o.id == _selectedOperatorId,
      orElse: () => _operators.first,
    );
    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => _SuccessDialog(
        amount: _received,
        operatorName: op.name,
        onBack: () {
          Navigator.of(context).pop();
          context.go('/wallet');
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Column(
        children: [
          // Navy top bar
          Container(
            padding: EdgeInsets.fromLTRB(
              16,
              MediaQuery.of(context).padding.top + 12,
              16,
              16,
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
                    child: const Icon(Icons.arrow_back_ios_new_rounded,
                        color: Colors.white, size: 15),
                  ),
                ),
                const SizedBox(width: 12),
                Text(
                  'Retrait Mobile Money',
                  style: GoogleFonts.nunito(
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),

          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Balance display
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: AppColors.skyGradient,
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Column(
                        children: [
                          Text(
                            'Solde disponible',
                            style: GoogleFonts.nunito(
                              fontSize: 13,
                              color: Colors.white.withAlpha(210),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            Formatters.currency(_currentBalance),
                            style: GoogleFonts.nunito(
                              fontSize: 26,
                              fontWeight: FontWeight.w900,
                              color: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 20),

                    // KYC warning
                    if (!_isKycSatisfied)
                      Container(
                        margin: const EdgeInsets.only(bottom: 16),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEF3C7),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: AppColors.warn.withAlpha(80)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.warning_amber_rounded,
                                color: AppColors.warn, size: 18),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                'Vérification KYC requise pour retirer.',
                                style: GoogleFonts.nunito(
                                  fontSize: 12,
                                  color: AppColors.warn,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),

                    // Operator grid 2x2
                    _FieldLabel('Opérateur Mobile Money'),
                    const SizedBox(height: 10),
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisSpacing: 10,
                      mainAxisSpacing: 10,
                      childAspectRatio: 2.2,
                      children: _operators.map((op) {
                        final isSelected = _selectedOperatorId == op.id;
                        return GestureDetector(
                          onTap: () =>
                              setState(() => _selectedOperatorId = op.id),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            decoration: BoxDecoration(
                              color: isSelected
                                  ? AppColors.skyPale
                                  : Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: isSelected
                                    ? AppColors.sky
                                    : AppColors.border,
                                width: isSelected ? 2 : 1.5,
                              ),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                // Colored logo circle
                                Container(
                                  width: 32,
                                  height: 32,
                                  decoration: BoxDecoration(
                                    color: op.color,
                                    shape: BoxShape.circle,
                                  ),
                                  child: Center(
                                    child: Text(
                                      op.shortName,
                                      style: GoogleFonts.nunito(
                                        fontSize: op.shortName.length > 2
                                            ? 9
                                            : 11,
                                        fontWeight: FontWeight.w900,
                                        color: op.textColor,
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Flexible(
                                  child: Text(
                                    op.name,
                                    style: GoogleFonts.nunito(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w700,
                                      color: isSelected
                                          ? AppColors.navy
                                          : AppColors.muted,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      }).toList(),
                    ),

                    const SizedBox(height: 18),

                    // Phone field
                    _FieldLabel('Numéro Mobile Money'),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _phoneCtrl,
                      keyboardType: TextInputType.phone,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                        LengthLimitingTextInputFormatter(10),
                      ],
                      style: GoogleFonts.nunito(
                          color: AppColors.navy, fontWeight: FontWeight.w600),
                      decoration: InputDecoration(
                        prefixText: '+225 ',
                        prefixStyle: GoogleFonts.nunito(
                          fontWeight: FontWeight.w700,
                          color: AppColors.navy,
                        ),
                        hintText: '07 01 23 45 67',
                        hintStyle: GoogleFonts.nunito(color: AppColors.textHint),
                      ),
                      validator: (v) {
                        if (v == null || v.trim().isEmpty) {
                          return 'Veuillez saisir votre numéro';
                        }
                        if (v.trim().length < 9) return 'Numéro invalide';
                        return null;
                      },
                    ),

                    const SizedBox(height: 16),

                    // Amount field
                    _FieldLabel('Montant (FCFA)'),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _amountCtrl,
                      keyboardType: TextInputType.number,
                      inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                      onChanged: (_) => setState(() {}),
                      style: GoogleFonts.nunito(
                          color: AppColors.navy, fontWeight: FontWeight.w700),
                      decoration: InputDecoration(
                        hintText:
                            'Min. ${Formatters.currency(AppConfig.minWithdrawal)}',
                        hintStyle:
                            GoogleFonts.nunito(color: AppColors.textHint),
                        suffixText: 'FCFA',
                        suffixStyle: GoogleFonts.nunito(
                          color: AppColors.muted,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      validator: (v) {
                        if (v == null || v.isEmpty) {
                          return 'Veuillez saisir un montant';
                        }
                        final a = int.tryParse(v);
                        if (a == null || a <= 0) return 'Montant invalide';
                        if (a < AppConfig.minWithdrawal) {
                          return 'Minimum ${Formatters.currency(AppConfig.minWithdrawal)}';
                        }
                        if (a > _currentBalance) return 'Solde insuffisant';
                        return null;
                      },
                    ),

                    // Fee rows
                    if (_enteredAmount >= AppConfig.minWithdrawal) ...[
                      const SizedBox(height: 14),
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Column(
                          children: [
                            _FeeRow(
                                label: 'Frais de retrait',
                                value: Formatters.currency(_fee),
                                isLast: false),
                            _FeeRow(
                              label: 'Vous recevrez',
                              value: Formatters.currency(_received),
                              isLast: true,
                              highlight: true,
                            ),
                          ],
                        ),
                      ),
                    ],

                    const SizedBox(height: 24),

                    SkyGradientButton(
                      label: 'Confirmer le retrait',
                      onPressed: (_isLoading || !_isKycSatisfied)
                          ? null
                          : _confirm,
                      isLoading: _isLoading,
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
// Fee row
// ---------------------------------------------------------------------------

class _FeeRow extends StatelessWidget {
  const _FeeRow({
    required this.label,
    required this.value,
    required this.isLast,
    this.highlight = false,
  });
  final String label;
  final String value;
  final bool isLast;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        border: isLast
            ? null
            : const Border(
                bottom: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: GoogleFonts.nunito(
                  color: AppColors.muted, fontSize: 13)),
          Text(
            value,
            style: GoogleFonts.nunito(
              fontWeight: FontWeight.w800,
              fontSize: 14,
              color: highlight ? AppColors.success : AppColors.navy,
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Field label
// ---------------------------------------------------------------------------

class _FieldLabel extends StatelessWidget {
  const _FieldLabel(this.label);
  final String label;

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: GoogleFonts.nunito(
        fontWeight: FontWeight.w700,
        fontSize: 14,
        color: AppColors.navy,
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Confirmation bottom sheet
// ---------------------------------------------------------------------------

class _ConfirmBottomSheet extends StatelessWidget {
  const _ConfirmBottomSheet({
    required this.operatorName,
    required this.operatorColor,
    required this.operatorTextColor,
    required this.operatorShortName,
    required this.amount,
    required this.fee,
    required this.received,
    required this.balanceAfter,
    required this.phone,
  });

  final String operatorName;
  final Color operatorColor;
  final Color operatorTextColor;
  final String operatorShortName;
  final int amount;
  final int fee;
  final int received;
  final int balanceAfter;
  final String phone;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.fromLTRB(
        20,
        0,
        20,
        MediaQuery.of(context).padding.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle
          Center(
            child: Container(
              margin: const EdgeInsets.symmetric(vertical: 12),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          Text(
            'Confirmer le retrait',
            style: GoogleFonts.nunito(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: AppColors.navy,
            ),
          ),
          const SizedBox(height: 16),

          // Recap card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.skyPale,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Opérateur',
                        style: GoogleFonts.nunito(
                            color: AppColors.muted, fontSize: 13)),
                    Row(
                      children: [
                        Container(
                          width: 24,
                          height: 24,
                          decoration: BoxDecoration(
                            color: operatorColor,
                            shape: BoxShape.circle,
                          ),
                          child: Center(
                            child: Text(
                              operatorShortName,
                              style: GoogleFonts.nunito(
                                fontSize: operatorShortName.length > 2 ? 7 : 9,
                                fontWeight: FontWeight.w900,
                                color: operatorTextColor,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 6),
                        Text(
                          operatorName,
                          style: GoogleFonts.nunito(
                            fontWeight: FontWeight.w700,
                            fontSize: 13,
                            color: AppColors.navy,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const Divider(color: AppColors.border, height: 16),
                _RecapRow(label: 'Montant', value: Formatters.currency(amount)),
                const Divider(color: AppColors.border, height: 16),
                _RecapRow(label: 'Frais', value: Formatters.currency(fee)),
                const Divider(color: AppColors.border, height: 16),
                _RecapRow(
                  label: 'Solde restant',
                  value: Formatters.currency(balanceAfter),
                  highlight: true,
                ),
              ],
            ),
          ),

          const SizedBox(height: 20),

          SkyGradientButton(
            label: 'Confirmer',
            onPressed: () => Navigator.of(context).pop(true),
          ),
          const SizedBox(height: 10),
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: Text(
              'Annuler',
              style: GoogleFonts.nunito(
                color: AppColors.muted,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _RecapRow extends StatelessWidget {
  const _RecapRow({
    required this.label,
    required this.value,
    this.highlight = false,
  });
  final String label;
  final String value;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: GoogleFonts.nunito(
                color: AppColors.muted, fontSize: 13)),
        Text(
          value,
          style: GoogleFonts.nunito(
            fontWeight: FontWeight.w700,
            fontSize: 13,
            color: highlight ? AppColors.sky : AppColors.navy,
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Success dialog
// ---------------------------------------------------------------------------

class _SuccessDialog extends StatelessWidget {
  const _SuccessDialog({
    required this.amount,
    required this.operatorName,
    required this.onBack,
  });
  final int amount;
  final String operatorName;
  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Ring success icon
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppColors.sky, AppColors.sky3],
                ),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check_rounded,
                  color: Colors.white, size: 36),
            ),
            const SizedBox(height: 16),
            Text(
              'Retrait initié !',
              style: GoogleFonts.nunito(
                fontSize: 20,
                fontWeight: FontWeight.w900,
                color: AppColors.navy,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              Formatters.currency(amount),
              style: GoogleFonts.nunito(
                fontSize: 26,
                fontWeight: FontWeight.w900,
                color: AppColors.sky,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              operatorName,
              style: GoogleFonts.nunito(
                  fontSize: 14, color: AppColors.muted),
            ),
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF3C7),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'Délai de traitement : 1–5 min',
                style: GoogleFonts.nunito(
                  fontSize: 12,
                  color: AppColors.warn,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            const SizedBox(height: 20),
            SkyGradientButton(
              label: 'Retour au wallet',
              onPressed: onBack,
            ),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// WithdrawalSheet kept for backwards compat (wallet_screen.dart import)
// ---------------------------------------------------------------------------

/// Legacy bottom-sheet widget — redirects to [WithdrawalScreen] full page.
class WithdrawalSheet extends StatelessWidget {
  const WithdrawalSheet({super.key, required this.currentBalance});
  final int currentBalance;

  @override
  Widget build(BuildContext context) {
    // We now use the full-page WithdrawalScreen via /withdrawal route.
    // This shell exists only so existing imports don't break.
    return const SizedBox.shrink();
  }
}
