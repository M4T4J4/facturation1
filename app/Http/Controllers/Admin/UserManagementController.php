<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->paginate(20),
            'roles' => UserRole::cases(),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = UserRole::from($request->validated('role'));

        abort_if($request->user()->is($user) && $role !== UserRole::Administrator, 422);

        $user->update(['role' => $role]);

        return back()->with('status', 'role-updated');
    }
}
