<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Affiche la liste des utilisateurs
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Recherche
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Tri et pagination
        $users = $query->orderBy('id', 'desc')->paginate(20);

        return view('back.security.users.index', compact('users'));
    }

    /**
     * Affiche le formulaire de création
     */
    public function create()
    {
        return view('back.security.users.create');
    }

    /**
     * Enregistre un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'credits' => ['nullable', 'integer', 'min:0'],
            'plan' => ['nullable', 'string', 'in:free,premium,enterprise'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'is_admin' => $request->has('is_admin'),
            'is_active' => $request->has('is_active'),
            'credits' => $request->credits ?? 0,
            'plan' => $request->plan ?? 'free',
        ]);

        return redirect()->route('back.security.users.index')
            ->with('success', "Utilisateur {$user->name} créé avec succès.");
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(User $user)
    {
        return view('back.security.users.edit', compact('user'));
    }

    /**
     * Met à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
            $user->update(['password' => bcrypt($request->password)]);
        }

        return redirect()->route('back.security.users.index')
            ->with('success', "Utilisateur {$user->name} mis à jour avec succès.");
    }

    // ============================================
    // MÉTHODES SUPPLÉMENTAIRES POUR LES ACTIONS
    // ============================================

    public function giveCredits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->increment('credits', $request->amount);

        return redirect()->back()->with('success', "{$request->amount} crédits ajoutés à {$user->name}");
    }

    public function removeCredits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer|min:1',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->decrement('credits', $request->amount);

        return redirect()->back()->with('success', "{$request->amount} crédits retirés à {$user->name}");
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'réactivé' : 'suspendu';

        return redirect()->back()->with('success', "Utilisateur {$user->name} {$status}.");
    }

    public function makeAdmin(User $user)
    {
        $user->update(['is_admin' => true]);
        return redirect()->back()->with('success', "{$user->name} est maintenant administrateur.");
    }

    public function removeAdmin(User $user)
    {
        $user->update(['is_admin' => false]);
        return redirect()->back()->with('success', "{$user->name} n'est plus administrateur.");
    }

    public function ban(User $user)
    {
        // Bloquer l'utilisateur
        \App\Models\Back\BlockedIdentity::updateOrCreate(
            ['type' => 'user', 'value' => $user->id],
            [
                'user_id' => auth()->id(),
                'reason' => 'Banni depuis le backoffice',
                'expires_at' => null,
                'is_active' => true,
            ]
        );

        $user->update(['is_active' => false]);

        return redirect()->back()->with('success', "Utilisateur {$user->name} banni avec succès.");
    }

    public function verifyEmail(User $user)
    {
        $user->update(['email_verified_at' => now()]);
        return redirect()->back()->with('success', "Email de {$user->name} vérifié.");
    }

    public function toggleOtpBypass(User $user)
    {
        $user->update(['otp_bypass' => !$user->otp_bypass]);
        $status = $user->otp_bypass ? 'activé' : 'désactivé';

        return redirect()->back()->with('success', "Laissez-passer OTP {$status} pour {$user->name}.");
    }
}
