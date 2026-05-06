<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Back\BlockedIdentity;
use App\Models\Back\SearchAttempt;
use App\Models\Back\VisitorDevice;
use App\Services\Api\DataRocketEngineService;
use App\Services\Security\CreditService;
use App\Services\Security\FingerprintService;
use App\Services\Security\VpnDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RechercheController extends Controller
{
    public function index(
        Request $request,
        DataRocketEngineService $engine,
        FingerprintService $fingerprintService,
        VpnDetectionService $vpnDetectionService,
        CreditService $creditService
    ) {
        $q = trim((string) $request->query('q'));

        if (!$q) {
            return redirect()->route('front.home');
        }

        $user = Auth::user();
        $ip = $request->ip();
        $fingerprintHash = $fingerprintService->makeHash($request);
        $userAgent = (string) $request->userAgent();

        $vpnData = $vpnDetectionService->check($ip);

        $device = VisitorDevice::updateOrCreate(
            [
                'fingerprint_hash' => $fingerprintHash,
                'ip_address' => $ip,
            ],
            [
                'user_id' => $user?->id,
                'user_agent_hash' => hash('sha256', $userAgent),
                'user_agent' => $userAgent,
                'timezone' => $request->query('timezone'),
                'language' => $request->query('language'),
                'is_vpn' => $vpnData['is_vpn'] ?? false,
                'is_proxy' => $vpnData['is_proxy'] ?? false,
                'is_tor' => $vpnData['is_tor'] ?? false,
                'is_datacenter' => $vpnData['is_datacenter'] ?? false,
                'risk_score' => $vpnData['risk_score'] ?? 0,
                'last_seen_at' => now(),
                'raw_data' => [
                    'vpn' => $vpnData,
                    'screen' => $request->query('screen'),
                ],
            ]
        );

        $blockedReason = $this->detectBlockedAccess($user, $device, $fingerprintHash, $ip);

        if ($blockedReason) {
            $this->logAttempt($user, $device, $q, false, 'blocked', $blockedReason, $vpnData);

            return view('front.recherche.access-blocked', [
                'q' => $q,
                'message' => $blockedReason,
                'requiresAuth' => true,
            ]);
        }

        $permission = $creditService->canSearch($user, $device);

        if (!$permission['allowed']) {
            $this->logAttempt(
                $user,
                $device,
                $q,
                false,
                $permission['status'] ?? 'no_credit',
                $permission['message'] ?? 'Crédits épuisés.',
                $vpnData
            );

            return view('front.recherche.access-blocked', [
                'q' => $q,
                'message' => $permission['message'] ?? 'Vos recherches gratuites sont épuisées.',
                'requiresAuth' => true,
                'showPaymentPopup' => true,
            ]);
        }

        $resultat = $engine->searchByAddress($q);

        $creditService->consumeAfterSearch($user, $device, $resultat);

        $this->logAttempt(
            $user,
            $device,
            $q,
            !empty($resultat['success']),
            'allowed',
            null,
            $vpnData,
            [
                'success' => $resultat['success'] ?? false,
                'message' => $resultat['message'] ?? null,
            ]
        );

        return view('front.recherche.result', [
            'q' => $q,
            'resultat' => $resultat,
            'adresse' => $resultat['adresse'] ?? null,
        ]);
    }

    private function detectBlockedAccess($user, VisitorDevice $device, string $fingerprintHash, ?string $ip): ?string
    {
        if ($user && !$user->is_active) {
            return 'Votre compte est suspendu.';
        }

        if ($device->is_blocked) {
            return $device->block_reason ?: 'Cet appareil est bloqué.';
        }

        $blocked = BlockedIdentity::active()
            ->where(function ($query) use ($user, $fingerprintHash, $ip) {
                if ($ip) {
                    $query->orWhere(fn ($q) => $q->where('type', 'ip')->where('value', $ip));
                }

                $query->orWhere(fn ($q) => $q->where('type', 'fingerprint')->where('value', $fingerprintHash));

                if ($user) {
                    $query->orWhere(fn ($q) => $q->where('type', 'user')->where('value', (string) $user->id));
                }
            })
            ->first();

        if ($blocked) {
            return $blocked->reason ?: 'Accès bloqué.';
        }

        if (!$user && ($device->is_vpn || $device->is_proxy || $device->is_tor || $device->is_datacenter)) {
            return 'Connexion VPN/proxy détectée. Veuillez vous authentifier pour continuer.';
        }

        return null;
    }

    private function logAttempt($user, VisitorDevice $device, string $q, bool $success, string $status, ?string $reason, array $vpnData, array $summary = []): void
    {
        SearchAttempt::create([
            'user_id' => $user?->id,
            'visitor_device_id' => $device->id,
            'query' => $q,
            'ip_address' => $device->ip_address,
            'fingerprint_hash' => $device->fingerprint_hash,
            'is_authenticated' => (bool) $user,
            'is_free_search' => !$user,
            'credit_consumed' => $success && $user && !$user->is_admin,
            'success' => $success,
            'status' => $status,
            'is_vpn' => $device->is_vpn,
            'is_proxy' => $device->is_proxy,
            'is_tor' => $device->is_tor,
            'is_datacenter' => $device->is_datacenter,
            'risk_score' => $device->risk_score,
            'block_reason' => $reason,
            'result_summary' => $summary,
            'raw_security_data' => $vpnData,
        ]);
    }
}