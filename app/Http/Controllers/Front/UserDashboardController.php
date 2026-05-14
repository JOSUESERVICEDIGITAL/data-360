<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Back\Recherche;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $recherches = Recherche::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        $stats = [
            'credits' => (int) ($user->credits ?? 0),
            'recherches_total' => Recherche::where('user_id', $user->id)->count(),
            'recherches_mois' => Recherche::where('user_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'compte_actif' => (bool) $user->is_active,
            'otp_bypass' => (bool) $user->otp_bypass,
            'email_verifie' => !is_null($user->email_verified_at),
        ];

        $achats = collect(); // Plus tard : CreditPurchase::where('user_id', $user->id)->latest()->get();

        $notifications = collect([
            [
                'type' => $user->is_active ? 'success' : 'danger',
                'title' => $user->is_active ? 'Compte actif' : 'Compte suspendu',
                'message' => $user->is_active
                    ? 'Votre compte est actuellement actif.'
                    : 'Votre compte est suspendu. Contactez l’administration.',
            ],
        ]);

        return view('front.dashboard.index', compact(
            'user',
            'stats',
            'recherches',
            'achats',
            'notifications'
        ));
    }
}