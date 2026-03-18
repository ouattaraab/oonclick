import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../providers/wallet_provider.dart';

/// Bottom sheet for initiating a mobile money withdrawal.
///
/// Validates amount, operator selection, and mobile phone number
/// before submitting to `POST /wallet/withdraw`.
class WithdrawalSheet extends ConsumerStatefulWidget {
  const WithdrawalSheet({super.key, required this.currentBalance});

  final int currentBalance;

  @override
  ConsumerState<WithdrawalSheet> createState() =>
      _WithdrawalSheetState();
}

class _WithdrawalSheetState extends ConsumerState<WithdrawalSheet> {
  final _formKey = GlobalKey<FormState>();
  final _amountCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();

  String? _selectedOperator;
  bool _isLoading = false;

  static const _operators = ['MTN', 'Moov', 'Orange'];

  @override
  void dispose() {
    _amountCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  int get _enteredAmount {
    return int.tryParse(_amountCtrl.text.replaceAll(' ', '')) ?? 0;
  }

  bool get _isKycSatisfied {
    final user = ref.read(currentUserProvider);
    return user != null && user.kycLevel >= 1;
  }

  Future<void> _confirm() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedOperator == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez sélectionner un opérateur'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }

    final confirmed = await _showConfirmDialog();
    if (!confirmed) return;

    setState(() => _isLoading = true);

    try {
      await ref.read(walletProvider.notifier).withdraw(
            amount: _enteredAmount,
            operator: _selectedOperator!,
            phone: _phoneCtrl.text.trim(),
          );

      if (!mounted) return;
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Retrait initié avec succès !'),
          backgroundColor: AppTheme.success,
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          backgroundColor: AppTheme.error,
        ),
      );
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<bool> _showConfirmDialog() async {
    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20)),
        title: const Text(
          'Confirmer le retrait',
          style: TextStyle(fontWeight: FontWeight.w800),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _ConfirmRow(
              label: 'Montant',
              value: Formatters.currency(_enteredAmount),
            ),
            const SizedBox(height: 8),
            _ConfirmRow(
              label: 'Opérateur',
              value: _selectedOperator ?? '',
            ),
            const SizedBox(height: 8),
            _ConfirmRow(
              label: 'Numéro',
              value: _phoneCtrl.text.trim(),
            ),
            const Divider(height: 24),
            _ConfirmRow(
              label: 'Solde après retrait',
              value: Formatters.currency(
                  widget.currentBalance - _enteredAmount),
              isHighlighted: true,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(
              minimumSize: const Size(100, 40),
            ),
            child: const Text('Confirmer'),
          ),
        ],
      ),
    );
    return result ?? false;
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;

    return Container(
      decoration: const BoxDecoration(
        color: AppTheme.bgCard,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.fromLTRB(24, 0, 24, 24 + bottomInset),
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
                color: AppTheme.divider,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),

          // Title
          Row(
            children: [
              const Text(
                'Retirer mes gains',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: AppTheme.textPrimary,
                ),
              ),
              const Spacer(),
              IconButton(
                icon: const Icon(Icons.close_rounded,
                    color: AppTheme.textSecondary),
                onPressed: () => Navigator.of(context).pop(),
              ),
            ],
          ),

          // KYC warning
          if (!_isKycSatisfied) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppTheme.warning.withAlpha(20),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                    color: AppTheme.warning.withAlpha(60)),
              ),
              child: const Row(
                children: [
                  Icon(Icons.warning_amber_rounded,
                      color: AppTheme.warning, size: 18),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Vérification d\'identité requise pour retirer. '
                      'Complétez votre profil.',
                      style: TextStyle(
                        fontSize: 12,
                        color: AppTheme.textSecondary,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: 20),

          // Form
          Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Amount
                const _FieldLabel('Montant (FCFA)'),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _amountCtrl,
                  keyboardType: TextInputType.number,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                  ],
                  onChanged: (_) => setState(() {}),
                  decoration: InputDecoration(
                    hintText:
                        'Min. ${Formatters.currency(AppConfig.minWithdrawal)}',
                    suffixText: 'FCFA',
                    suffixStyle: const TextStyle(
                      color: AppTheme.textSecondary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) {
                      return 'Veuillez saisir un montant';
                    }
                    final amount = int.tryParse(v);
                    if (amount == null || amount <= 0) {
                      return 'Montant invalide';
                    }
                    if (amount < AppConfig.minWithdrawal) {
                      return 'Minimum ${Formatters.currency(AppConfig.minWithdrawal)}';
                    }
                    if (amount > widget.currentBalance) {
                      return 'Solde insuffisant';
                    }
                    return null;
                  },
                ),

                const SizedBox(height: 16),

                // Operator selector
                const _FieldLabel('Opérateur'),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 10,
                  children: _operators.map((op) {
                    final isSelected = _selectedOperator == op;
                    return GestureDetector(
                      onTap: () =>
                          setState(() => _selectedOperator = op),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 180),
                        padding: const EdgeInsets.symmetric(
                            horizontal: 20, vertical: 10),
                        decoration: BoxDecoration(
                          color: isSelected
                              ? AppTheme.primary.withAlpha(20)
                              : AppTheme.bgPage,
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(
                            color: isSelected
                                ? AppTheme.primary
                                : AppTheme.divider,
                            width: isSelected ? 2 : 1,
                          ),
                        ),
                        child: Text(
                          op,
                          style: TextStyle(
                            fontWeight: FontWeight.w700,
                            color: isSelected
                                ? AppTheme.primary
                                : AppTheme.textSecondary,
                          ),
                        ),
                      ),
                    );
                  }).toList(),
                ),

                const SizedBox(height: 16),

                // Phone
                const _FieldLabel('Numéro Mobile Money'),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _phoneCtrl,
                  keyboardType: TextInputType.phone,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(10),
                  ],
                  decoration: const InputDecoration(
                    prefixText: '+225 ',
                    prefixStyle: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: AppTheme.textPrimary,
                    ),
                    hintText: '07 01 23 45 67',
                  ),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) {
                      return 'Veuillez saisir votre numéro';
                    }
                    if (v.trim().length < 9) {
                      return 'Numéro invalide';
                    }
                    return null;
                  },
                ),

                const SizedBox(height: 16),

                // Summary
                if (_enteredAmount >= AppConfig.minWithdrawal)
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppTheme.bgPage,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppTheme.divider),
                    ),
                    child: Row(
                      mainAxisAlignment:
                          MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Montant net reçu',
                          style: TextStyle(
                            color: AppTheme.textSecondary,
                            fontSize: 13,
                          ),
                        ),
                        Text(
                          Formatters.currency(_enteredAmount),
                          style: const TextStyle(
                            color: AppTheme.success,
                            fontWeight: FontWeight.w800,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),

                const SizedBox(height: 24),

                // Submit
                ElevatedButton.icon(
                  onPressed: (_isLoading || !_isKycSatisfied)
                      ? null
                      : _confirm,
                  icon: _isLoading
                      ? const SizedBox(
                          height: 18,
                          width: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Icon(Icons.send_to_mobile_rounded,
                          size: 18),
                  label: const Text('Confirmer le retrait'),
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
// Helper widgets
// ---------------------------------------------------------------------------

class _FieldLabel extends StatelessWidget {
  const _FieldLabel(this.text);
  final String text;

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        fontWeight: FontWeight.w600,
        fontSize: 14,
        color: AppTheme.textPrimary,
      ),
    );
  }
}

class _ConfirmRow extends StatelessWidget {
  const _ConfirmRow({
    required this.label,
    required this.value,
    this.isHighlighted = false,
  });

  final String label;
  final String value;
  final bool isHighlighted;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(
            color: AppTheme.textSecondary,
            fontSize: 14,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.w700,
            fontSize: 14,
            color: isHighlighted
                ? AppTheme.success
                : AppTheme.textPrimary,
          ),
        ),
      ],
    );
  }
}
