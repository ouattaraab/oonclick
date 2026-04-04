<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class AdminWithdrawalsController extends Controller
{
    public function index()
    {
        $withdrawals     = Withdrawal::with('user')->latest()->paginate(20);
        $totalWithdrawals = Withdrawal::count();
        $pendingCount    = Withdrawal::where('status', 'pending')->count();
        $completedCount  = Withdrawal::where('status', 'completed')->count();
        $totalAmount     = Withdrawal::where('status', 'completed')->sum('net_amount');

        return view('panel.admin.withdrawals', compact(
            'withdrawals', 'totalWithdrawals', 'pendingCount', 'completedCount', 'totalAmount'
        ));
    }

    public function approve(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut pas être approuvé.');
        }

        $withdrawal->update([
            'status'       => 'completed',
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Retrait approuvé avec succès.');
    }

    public function reject(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Ce retrait ne peut pas être rejeté.');
        }

        $withdrawal->update([
            'status'         => 'failed',
            'failure_reason' => 'Rejeté par un administrateur.',
            'processed_at'   => now(),
        ]);

        return back()->with('success', 'Retrait rejeté.');
    }

    /**
     * POST /panel/admin/withdrawals/batch-approve
     *
     * Approuve en masse une liste de retraits en attente (US-044).
     * Seuls les retraits avec statut "pending" sont traités.
     */
    public function batchApprove(Request $request)
    {
        $request->validate([
            'withdrawal_ids'   => ['required', 'array'],
            'withdrawal_ids.*' => ['integer', 'exists:withdrawals,id'],
        ]);

        $ids      = $request->input('withdrawal_ids');
        $approved = 0;

        foreach ($ids as $id) {
            $withdrawal = Withdrawal::find($id);

            if ($withdrawal && $withdrawal->status === 'pending') {
                $withdrawal->update([
                    'status'       => 'completed',
                    'processed_at' => now(),
                ]);
                $approved++;
            }
        }

        return back()->with('success', "{$approved} retrait(s) approuvé(s) avec succès.");
    }
}
