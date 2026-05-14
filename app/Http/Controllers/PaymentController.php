<?php

namespace App\Http\Controllers;

use App\Models\Back\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function process(Request $request)
    {
        $request->validate([
            'pack_key' => 'required|string',
            'card_number' => 'required|string',
            'card_expiry' => 'required|string',
            'card_cvv' => 'required|string',
            'card_holder' => 'required|string',
        ]);

        $user = auth()->user();

        // Définition des packs
        $packs = [
            'decouverte' => ['credits' => 10, 'price' => 49],
            'pro' => ['credits' => 100, 'price' => 399],
            'business' => ['credits' => 500, 'price' => 1499],
        ];

        if (!isset($packs[$request->pack_key])) {
            return response()->json(['success' => false, 'message' => 'Pack invalide'], 400);
        }

        $pack = $packs[$request->pack_key];
        $amount = $pack['price'];
        $creditsToAdd = $pack['credits'];

        // Simulation de paiement (à remplacer par Stripe plus tard)
        // Pour tester : toutes les cartes sauf celles avec 1111
        if (strpos($request->card_number, '1111') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement refusé. Vérifiez vos informations bancaires.'
            ], 400);
        }

        // Si paiement OK : ajout des crédits
        DB::transaction(function () use ($user, $creditsToAdd, $amount, $request) {
            $balanceBefore = $user->credits;
            
            // Ajout des crédits
            $user->increment('credits', $creditsToAdd);
            $user->refresh();
            
            // Enregistrement de la transaction
            CreditTransaction::create([
                'user_id' => $user->id,
                'admin_id' => null,
                'amount' => $creditsToAdd,
                'type' => 'payment_add',
                'reason' => 'Achat du pack ' . $request->pack_key,
                'payment_reference' => 'PAY_' . uniqid(),
                'balance_before' => $balanceBefore,
                'balance_after' => $user->credits,
                'meta' => [
                    'pack' => $request->pack_key,
                    'price' => $amount,
                    'card_last4' => substr($request->card_number, -4),
                ],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Paiement accepté ! {$creditsToAdd} crédits ont été ajoutés à votre compte."
        ]);
    }
}