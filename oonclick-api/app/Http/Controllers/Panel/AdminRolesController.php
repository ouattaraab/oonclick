<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

class AdminRolesController extends Controller
{
    public function index()
    {
        $roles      = Role::with('permissions', 'users')->orderBy('name')->get();
        $totalRoles = $roles->count();
        $totalPerms = \Spatie\Permission\Models\Permission::count();

        return view('panel.admin.roles', compact('roles', 'totalRoles', 'totalPerms'));
    }
}
