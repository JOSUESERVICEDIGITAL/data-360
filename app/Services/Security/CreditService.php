<?php

namespace App\Services\Security;

use App\Models\Back\CreditTransaction;
use App\Models\Back\VisitorDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public const FREE_SEARCH_LIMIT = 2;

    public function canSearch(?User $user, VisitorDevice $device): array
    {
        if ($user && !$user->is_active) {
            return [
                'allowed' => false,
                'status' => 'blocked',
                'message' => 'Votre compte est suspendu.',
            ];
        }

        if ($user && $user->is_admin) {
            return [
                'allowed' => true,
                'status' => 'admin',
                'message' => 'Accès administrateur illimité.',
            ];
        }

        if ($user) {
            if ((int) $user->credits > 0) {
                return [
                    'allowed' => true,
                    'status' => 'credit',
                    'message' => 'Recherche autorisée.',
                ];
            }

            return [
                'allowed' => false,
                'status' => 'no_credit',
                'message' => 'Vous n’avez plus de crédits. Veuillez payer ou contacter l’administrateur.',
            ];
        }

        if ((int) $device->free_searches_used < self::FREE_SEARCH_LIMIT) {
            return [
                'allowed' => true,
                'status' => 'free',
                'message' => 'Recherche gratuite autorisée.',
            ];
        }

        return [
            'allowed' => false,
            'status' => 'free_limit_reached',
            'message' => 'Vos 2 recherches gratuites sont épuisées. Connectez-vous ou achetez des crédits.',
        ];
    }

    public function consumeAfterSearch(?User $user, VisitorDevice $device, array $resultat): void
    {
        if (empty($resultat['success'])) {
            return;
        }

        if (!$user) {
            $device->increment('free_searches_used');
            return;
        }

        if ($user->is_admin) {
            return;
        }

        if ((int) $user->credits <= 0) {
            return;
        }

        DB::transaction(function () use ($user) {
            $freshUser = User::lockForUpdate()->find($user->id);

            if (!$freshUser || $freshUser->credits <= 0) {
                return;
            }

            $before = (int) $freshUser->credits;
            $freshUser->decrement('credits');
            $after = $before - 1;

            CreditTransaction::create([
                'user_id' => $freshUser->id,
                'admin_id' => null,
                'amount' => -1,
                'type' => 'search_consume',
                'reason' => 'Recherche consommée',
                'balance_before' => $before,
                'balance_after' => $after,
            ]);
        });
    }

    public function addCredits(User $user, int $amount, ?User $admin = null, ?string $reason = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $admin, $reason) {
            $freshUser = User::lockForUpdate()->findOrFail($user->id);

            $before = (int) $freshUser->credits;
            $freshUser->increment('credits', $amount);
            $after = $before + $amount;

            CreditTransaction::create([
                'user_id' => $freshUser->id,
                'admin_id' => $admin?->id,
                'amount' => $amount,
                'type' => 'admin_add',
                'reason' => $reason ?: 'Crédits ajoutés',
                'balance_before' => $before,
                'balance_after' => $after,
            ]);
        });
    }

    public function removeCredits(User $user, int $amount, ?User $admin = null, ?string $reason = null): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $admin, $reason) {
            $freshUser = User::lockForUpdate()->findOrFail($user->id);

            $before = (int) $freshUser->credits;
            $newBalance = max(0, $before - $amount);

            $freshUser->update([
                'credits' => $newBalance,
            ]);

            CreditTransaction::create([
                'user_id' => $freshUser->id,
                'admin_id' => $admin?->id,
                'amount' => -$amount,
                'type' => 'admin_remove',
                'reason' => $reason ?: 'Crédits retirés',
                'balance_before' => $before,
                'balance_after' => $newBalance,
            ]);
        });
    }
}