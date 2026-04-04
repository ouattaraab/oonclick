<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminUsersController extends Controller
{
    public function index()
    {
        $users = User::with('profile', 'roles')->latest()->paginate(15);

        return view('panel.admin.users', compact('users'));
    }

    public function show(User $user)
    {
        $user->load('profile', 'roles', 'wallet.transactions');

        $transactions = $user->wallet?->transactions()->latest()->take(10)->get() ?? collect();

        return view('panel.admin.user-detail', compact('user', 'transactions'));
    }

    public function suspend(User $user)
    {
        $user->update(['is_suspended' => true, 'is_active' => false]);

        return back()->with('success', "Utilisateur \"{$user->name}\" suspendu.");
    }

    public function unsuspend(User $user)
    {
        $user->update(['is_suspended' => false, 'is_active' => true]);

        return back()->with('success', "Utilisateur \"{$user->name}\" réactivé.");
    }
}
