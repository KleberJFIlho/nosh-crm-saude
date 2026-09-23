<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        ClinicAccess::allow('users.view');
        $clinicId = ClinicAccess::clinicId();
        $users = User::where('clinic_id', $clinicId)->orderBy('name')->get();

        $stats = [
            'total' => $users->count(),
            'active' => $users->where('active', true)->count(),
            'admins' => $users->where('role', 'admin')->where('active', true)->count(),
            'clinical' => $users->where('role', 'health_professional')->where('active', true)->count(),
        ];

        return view('users.index', [
            'users' => $users,
            'roles' => config('noshcrm.roles'),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ClinicAccess::allow('users.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(config('noshcrm.roles')))],
        ]);
        $user = User::create($data + ['clinic_id' => ClinicAccess::clinicId(), 'active' => true]);
        ClinicAccess::audit('user.created', User::class, $user->id, ['role' => $user->role]);
        return back()->with('success', 'Utilizador criado para esta clínica.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        ClinicAccess::allow('users.manage');
        abort_unless((int) $user->clinic_id === ClinicAccess::clinicId(), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(config('noshcrm.roles')))],
            'active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if (blank($data['password'] ?? null)) unset($data['password']);
        if ($user->is(auth()->user()) && array_key_exists('active', $data)) $data['active'] = true;
        $user->update($data);
        ClinicAccess::audit('user.updated', User::class, $user->id, ['role' => $user->role, 'active' => $user->active]);
        return back()->with('success', 'Utilizador atualizado.');
    }
}
