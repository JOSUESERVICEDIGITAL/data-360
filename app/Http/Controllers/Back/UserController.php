<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\BlockedIdentity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('q')) {
            $search = trim($request->q);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('back.security.users.index', compact('users'));
    }

    public function create()
    {
        return view('back.security.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'otp_bypass' => ['nullable', 'boolean'],
            'email_verified' => ['nullable', 'boolean'],

            'credits' => ['nullable', 'integer', 'min:0'],
            'plan' => ['nullable', Rule::in(['free', 'premium', 'enterprise'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),

            'is_admin' => $request->boolean('is_admin'),
            'is_active' => $request->boolean('is_active'),
            'otp_bypass' => $request->boolean('otp_bypass'),
            'email_verified_at' => $request->boolean('email_verified') ? now() : null,

            'credits' => $data['credits'] ?? 0,
            'plan' => $data['plan'] ?? 'free',
        ]);

        return redirect()
            ->route('admin.security.users.index')
            ->with('success', "Utilisateur {$user->name} créé avec succès.");
    }

    public function edit(User $user)
    {
        return view('back.security.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],

            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'otp_bypass' => ['nullable', 'boolean'],
            'email_verified' => ['nullable', 'boolean'],

            'credits' => ['nullable', 'integer', 'min:0'],
            'plan' => ['nullable', Rule::in(['free', 'premium', 'enterprise'])],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,

            'is_admin' => $request->boolean('is_admin'),
            'is_active' => $request->boolean('is_active'),
            'otp_bypass' => $request->boolean('otp_bypass'),

            'credits' => $data['credits'] ?? 0,
            'plan' => $data['plan'] ?? 'free',
        ];

        if ($request->boolean('email_verified')) {
            $payload['email_verified_at'] = $user->email_verified_at ?? now();
        } else {
            $payload['email_verified_at'] = null;
        }

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return redirect()
            ->route('admin.security.users.index')
            ->with('success', "Utilisateur {$user->name} mis à jour avec succès.");
    }

    public function giveCredits(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $user->increment('credits', $data['amount']);

        return back()->with('success', "{$data['amount']} crédits ajoutés à {$user->name}.");
    }

    public function removeCredits(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        $user = User::findOrFail($data['user_id']);

        $newCredits = max(0, ((int) $user->credits) - (int) $data['amount']);

        $user->update([
            'credits' => $newCredits,
        ]);

        return back()->with('success', "{$data['amount']} crédits retirés à {$user->name}.");
    }

    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? "Utilisateur {$user->name} réactivé."
                : "Utilisateur {$user->name} suspendu."
        );
    }

    public function makeAdmin(User $user)
    {
        $user->update([
            'is_admin' => true,
        ]);

        return back()->with('success', "{$user->name} est maintenant administrateur.");
    }

    public function removeAdmin(User $user)
    {
        $user->update([
            'is_admin' => false,
        ]);

        return back()->with('success', "{$user->name} n’est plus administrateur.");
    }

    public function verifyEmail(User $user)
    {
        $user->update([
            'email_verified_at' => now(),
        ]);

        return back()->with('success', "Email de {$user->name} vérifié.");
    }

    public function toggleOtpBypass(User $user)
    {
        $user->update([
            'otp_bypass' => !$user->otp_bypass,
        ]);

        return back()->with(
            'success',
            $user->otp_bypass
                ? "Laissez-passer OTP activé pour {$user->name}."
                : "Laissez-passer OTP désactivé pour {$user->name}."
        );
    }

    public function ban(User $user)
    {
        BlockedIdentity::updateOrCreate(
            [
                'type' => 'user',
                'value' => (string) $user->id,
            ],
            [
                'user_id' => auth()->id(),
                'reason' => 'Banni depuis le backoffice',
                'expires_at' => null,
                'is_active' => true,
            ]
        );

        $user->update([
            'is_active' => false,
        ]);

        return back()->with('success', "Utilisateur {$user->name} banni avec succès.");
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.security.users.index')
            ->with('success', "Utilisateur supprimé avec succès.");
    }
}