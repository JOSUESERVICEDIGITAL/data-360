<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function create()
    {
        return view('back.security.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone' => ['nullable', 'string', 'max:30', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'credits' => ['nullable', 'integer', 'min:0'],
            'plan' => ['nullable', 'string', 'in:free,premium,enterprise'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_admin' => $request->has('is_admin'),
            'is_active' => $request->has('is_active'),
            'credits' => $request->credits ?? 0,
            'plan' => $request->plan ?? 'free',
        ]);

        return redirect()->route('admin.security.users.index')
            ->with('success', "Utilisateur {$user->name} créé avec succès.");
    }

    public function edit(User $user)
    {
        return view('back.security.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique(User::class)->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'credits' => ['nullable', 'integer', 'min:0'],
            'plan' => ['nullable', 'string', 'in:free,premium,enterprise'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_admin' => $request->has('is_admin'),
            'is_active' => $request->has('is_active'),
            'credits' => $request->credits ?? $user->credits,
            'plan' => $request->plan ?? $user->plan,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('admin.security.users.index')
            ->with('success', "Utilisateur {$user->name} mis à jour avec succès.");
    }
}