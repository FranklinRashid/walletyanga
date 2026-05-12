<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    public function index(): View
    {
        return view('admin.staff.index', [
            'staffUsers' => User::query()
                ->whereIn('role', UserRoles::adminRoles())
                ->latest()
                ->get(),
            'roles' => UserRoles::staffRoles(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', [
            'roles' => UserRoles::staffRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(UserRoles::staffRoles()))],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'status' => 'active',
        ]);

        AdminAction::query()->create([
            'admin_user_id' => $request->user()->id,
            'action' => 'staff_user.created',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'after' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.staff.index')->with('status', 'Staff user created.');
    }
}
