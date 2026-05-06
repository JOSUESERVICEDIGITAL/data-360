<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\GiveCreditRequest;
use App\Models\User;
use App\Services\Security\CreditService;
use Illuminate\Http\Request;

class AdminUserCreditController extends Controller
{
    public function __construct(
        protected CreditService $creditService
    ) {
    }

    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->input('q');

                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('back.security.users.index', compact('users'));
    }

    public function giveCredits(GiveCreditRequest $request)
    {
        $user = User::findOrFail($request->input('user_id'));

        $this->creditService->addCredits(
            user: $user,
            amount: (int) $request->input('amount'),
            admin: auth()->user(),
            reason: $request->input('reason') ?: 'Crédits attribués par administrateur'
        );

        return back()->with('success', 'Crédits ajoutés avec succès.');
    }

    public function removeCredits(GiveCreditRequest $request)
    {
        $user = User::findOrFail($request->input('user_id'));

        $this->creditService->removeCredits(
            user: $user,
            amount: (int) $request->input('amount'),
            admin: auth()->user(),
            reason: $request->input('reason') ?: 'Crédits retirés par administrateur'
        );

        return back()->with('success', 'Crédits retirés avec succès.');
    }

    public function toggleActive(User $user)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas suspendre votre propre compte.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with('success', 'Statut utilisateur mis à jour.');
    }

    public function makeAdmin(User $user)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $user->update([
            'is_admin' => true,
            'is_active' => true,
        ]);

        return back()->with('success', 'Utilisateur promu administrateur.');
    }

    public function removeAdmin(User $user)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas retirer vos propres droits admin.');
        }

        $user->update([
            'is_admin' => false,
        ]);

        return back()->with('success', 'Droits administrateur retirés.');
    }
    public function toggleOtpBypass(User $user)
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);

        $user->update([
            'otp_bypass' => !$user->otp_bypass,
        ]);

        return back()->with('success', 'Laissez-passer OTP mis à jour.');
    }
}