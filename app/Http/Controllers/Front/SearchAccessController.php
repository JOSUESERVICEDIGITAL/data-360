<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\SearchAccessRequest;
use App\Models\Back\BlockedIdentity;
use App\Models\Back\SearchAttempt;
use App\Models\Back\VisitorDevice;
use App\Services\Api\DataRocketEngineService;
use App\Services\Security\CreditService;
use App\Services\Security\FingerprintService;
use App\Services\Security\VpnDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchAccessController extends Controller
{
    public function __construct(
        protected DataRocketEngineService $engine,
        protected FingerprintService $fingerprintService,
        protected VpnDetectionService $vpnDetectionService,
        protected CreditService $creditService,
    ) {}

    public function search(SearchAccessRequest $request)
    {
        $user = Auth::user();
        $query = $request->queryText();

        $fingerprintHash = $this->fingerprintService->makeHash($request);
        $ip = $request->ip();
        $userAgent = (string) $request->userAgent();
        $userAgentHash = hash('sha256', $userAgent);

        $vpnData = $this->vpnDetectionService->check($ip);

        $device = VisitorDevice::updateOrCreate(
            [
                'fingerprint_hash' => $fingerprintHash,
                'ip_address' => $ip,
            ],
            [
                'user_id' => $user?->id,
                'user_agent_hash' => $userAgentHash,
                'user_agent' => $userAgent,
                'browser' => $request->header('Browser'),
                'platform' => $request->header('Platform'),
                'timezone' => $request->input('timezone'),
                'language' => $request->input('language'),
                'is_vpn' => $vpnData['is_vpn'] ?? false,
                'is_proxy' => $vpnData['is_proxy'] ?? false,
                'is_tor' => $vpnData['is_tor'] ?? false,
                'is_datacenter' => $vpnData['is_datacenter'] ?? false,
                'risk_score' => $vpnData['risk_score'] ?? 0,
                'last_seen_at' => now(),
                'raw_data' => [
                    'vpn' => $vpnData,
                    'headers' => [
                        'user_agent' => $userAgent,
                        'language' => $request->input('language'),
                        'timezone' => $request->input('timezone'),
                        'screen' => $request->input('screen'),
                    ],
                ],
            ]
        );

        $blockedReason = $this->detectBlock($user, $device, $fingerprintHash, $ip);

        if ($blockedReason) {
            SearchAttempt::create([
                'user_id' => $user?->id,
                'visitor_device_id' => $device->id,
                'query' => $query,
                'ip_address' => $ip,
                'fingerprint_hash' => $fingerprintHash,
                'is_authenticated' => (bool) $user,
                'success' => false,
                'status' => 'blocked',
                'is_vpn' => $device->is_vpn,
                'is_proxy' => $device->is_proxy,
                'is_tor' => $device->is_tor,
                'is_datacenter' => $device->is_datacenter,
                'risk_score' => $device->risk_score,
                'block_reason' => $blockedReason,
                'raw_security_data' => $vpnData,
            ]);

            return response()->json([
                'success' => false,
                'requires_auth' => true,
                'message' => $blockedReason,
            ], 403);
        }

        $permission = $this->creditService->canSearch($user, $device);

        if (!$permission['allowed']) {
            SearchAttempt::create([
                'user_id' => $user?->id,
                'visitor_device_id' => $device->id,
                'query' => $query,
                'ip_address' => $ip,
                'fingerprint_hash' => $fingerprintHash,
                'is_authenticated' => (bool) $user,
                'success' => false,
                'status' => $permission['status'] ?? 'no_credit',
                'block_reason' => $permission['message'] ?? null,
                'raw_security_data' => $vpnData,
            ]);

            return response()->json([
                'success' => false,
                'requires_auth' => true,
                'show_payment_popup' => true,
                'message' => $permission['message'] ?? 'Vous n’avez plus de crédits.',
            ], 402);
        }

        $resultat = $this->engine->searchByAddress($query);

        $this->creditService->consumeAfterSearch($user, $device, $resultat);

        SearchAttempt::create([
            'user_id' => $user?->id,
            'visitor_device_id' => $device->id,
            'query' => $query,
            'normalized_address' => $resultat['adresse']?->adresse_complete ?? null,
            'ip_address' => $ip,
            'fingerprint_hash' => $fingerprintHash,
            'is_authenticated' => (bool) $user,
            'is_free_search' => !$user,
            'credit_consumed' => $user && !$user->is_admin,
            'success' => !empty($resultat['success']),
            'status' => 'allowed',
            'is_vpn' => $device->is_vpn,
            'is_proxy' => $device->is_proxy,
            'is_tor' => $device->is_tor,
            'is_datacenter' => $device->is_datacenter,
            'risk_score' => $device->risk_score,
            'result_summary' => [
                'success' => $resultat['success'] ?? false,
                'message' => $resultat['message'] ?? null,
            ],
            'raw_security_data' => $vpnData,
        ]);

        return view('front.recherche.result', [
            'q' => $query,
            'resultat' => $resultat,
            'adresse' => $resultat['adresse'] ?? null,
        ]);
    }

    private function detectBlock($user, VisitorDevice $device, string $fingerprintHash, ?string $ip): ?string
    {
        if ($user && !$user->is_active) {
            return 'Votre compte est suspendu.';
        }

        if ($device->is_blocked) {
            return $device->block_reason ?: 'Cet appareil est bloqué.';
        }

        $blocked = BlockedIdentity::active()
            ->where(function ($query) use ($user, $fingerprintHash, $ip) {
                $query->where(function ($q) use ($ip) {
                    if ($ip) {
                        $q->where('type', 'ip')->where('value', $ip);
                    }
                })
                ->orWhere(function ($q) use ($fingerprintHash) {
                    $q->where('type', 'fingerprint')->where('value', $fingerprintHash);
                });

                if ($user) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('type', 'user')->where('value', (string) $user->id);
                    });
                }
            })
            ->first();

        if ($blocked) {
            return $blocked->reason ?: 'Accès bloqué pour cette identité.';
        }

        if (!$user && ($device->is_vpn || $device->is_proxy || $device->is_tor || $device->is_datacenter)) {
            return 'Connexion VPN/proxy détectée. Veuillez vous authentifier ou désactiver le VPN.';
        }

        return null;
    }
}