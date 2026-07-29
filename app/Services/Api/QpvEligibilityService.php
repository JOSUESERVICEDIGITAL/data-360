<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * QpvEligibilityService — SIG Ville WSA API v2
 * ─────────────────────────────────────────────────────────────
 * Base URL  : https://wsa.sig.ville.gouv.fr
 * Auth      : HTTP Basic Auth (email + password)
 * Endpoints :
 *   /api/xy.json  → coordonnées GPS  (PRINCIPAL)
 *   /api/v2.json  → adresse WSA      (FALLBACK)
 *
 * Format réponse API v2 (validé 29/07/2026) :
 *   reponses[].type_quartier  : QP | QP_2015 | ZFU
 *   reponses[].code_reponse   : OUI | NON
 *   reponses[].nom_quartier   : nom du quartier si OUI
 *   reponses[].code_quartier  : code du quartier si OUI
 * ─────────────────────────────────────────────────────────────
 */
class QpvEligibilityService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int    $timeout = 10;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.sigville.url', 'https://wsa.sig.ville.gouv.fr'), '/');
        $this->username = config('services.sigville.username', '');
        $this->password = config('services.sigville.password', '');
    }

    // ════════════════════════════════════════════════════════════
    // POINT D'ENTRÉE PRINCIPAL
    // ════════════════════════════════════════════════════════════

    public function check(
        ?float $lat        = null,
        ?float $lng        = null,
        string $adresse    = '',
        string $commune    = '',
        string $codePostal = ''
    ): array {
        $cacheKey = ($lat !== null && $lng !== null)
            ? 'qpv_xy_'  . round($lat, 5) . '_' . round($lng, 5)
            : 'qpv_adr_' . md5($adresse . $codePostal . $commune);

        return Cache::remember($cacheKey, 604800, function () use ($lat, $lng, $adresse, $commune, $codePostal) {
            return $this->fetchFromApi($lat, $lng, $adresse, $commune, $codePostal);
        });
    }

    public function isEligible(
        ?float $lat        = null,
        ?float $lng        = null,
        string $adresse    = '',
        string $commune    = '',
        string $codePostal = ''
    ): bool {
        $r = $this->check($lat, $lng, $adresse, $commune, $codePostal);
        return !($r['qp_2024'] || $r['qp_2015'] || $r['zfu']);
    }

    // ════════════════════════════════════════════════════════════
    // APPELS API
    // ════════════════════════════════════════════════════════════

    private function fetchFromApi(
        ?float $lat,
        ?float $lng,
        string $adresse,
        string $commune,
        string $codePostal
    ): array {
        try {
            // Priorité : coordonnées GPS (plus précis)
            if ($lat !== null && $lng !== null) {
                return $this->callXyEndpoint($lat, $lng);
            }
            // Fallback : adresse texte format WSA
            return $this->callAddressEndpoint($adresse, $commune, $codePostal);

        } catch (\Throwable $e) {
            Log::warning('QpvEligibilityService erreur: ' . $e->getMessage());
            return $this->emptyResult();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ENDPOINT XY — /api/xy.json
    // Géoréférencement inverse par coordonnées GPS
    // ─────────────────────────────────────────────────────────────
    private function callXyEndpoint(float $lat, float $lng): array
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/api/xy.json', [
                'x'              => $lng,               // longitude = X
                'y'              => $lat,               // latitude  = Y
                'type_quartier[]'=> ['QP', 'QP_2015', 'ZFU'],
            ]);

        if ($response->failed()) {
            Log::warning("SIG Ville /api/xy.json → HTTP {$response->status()} — {$response->body()}");
            return $this->emptyResult();
        }

        return $this->parseResponse($response->json());
    }

    // ─────────────────────────────────────────────────────────────
    // ENDPOINT V2 — /api/v2.json
    // Géoréférencement par adresse (format WSA avec crochets)
    // Validé le 29/07/2026 — format correct pour l'API SIG Ville
    // ─────────────────────────────────────────────────────────────
    private function callAddressEndpoint(
        string $adresse,
        string $commune,
        string $codePostal
    ): array {
        if (empty($adresse) && empty($commune)) {
            return $this->emptyResult();
        }

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/api/v2.json', [
                'type_adresse'         => 'WSA',
                'adresse[num_voie]'    => $this->extractNumero($adresse),
                'adresse[nom_voie]'    => $this->extractNomVoie($adresse),
                'adresse[code_postal]' => $codePostal,
                'adresse[nom_commune]' => $commune,
                'type_quartier[]'      => ['QP', 'QP_2015', 'ZFU'],
            ]);

        if ($response->failed()) {
            Log::warning("SIG Ville /api/v2.json → HTTP {$response->status()} — {$response->body()}");
            return $this->emptyResult();
        }

        return $this->parseResponse($response->json());
    }

    // ════════════════════════════════════════════════════════════
    // PARSING DE LA RÉPONSE SIG Ville (format validé 29/07/2026)
    //
    // Structure retournée par l'API :
    // {
    //   "loccom_ref" : "COMMUNE_AVEC_QUARTIER",
    //   "adresse"    : { "score": 97.03, ... },
    //   "reponses"   : [
    //     { "type_quartier": "QP",     "code_reponse": "NON", ... },
    //     { "type_quartier": "QP_2015","code_reponse": "NON", ... },
    //     { "type_quartier": "ZFU",    "code_reponse": "NON", ... },
    //   ]
    // }
    // ════════════════════════════════════════════════════════════
    private function parseResponse(mixed $data): array
    {
        if (empty($data)) return $this->emptyResult();

        $result   = $this->emptyResult();
        $reponses = $data['reponses'] ?? [];

        // Métadonnées de localisation
        $result['loccom_ref']  = $data['loccom_ref']       ?? null;
        $result['locvoie_ref'] = $data['code_reponse']     ?? null;
        $result['similitude']  = $data['adresse']['score'] ?? null;

        // Parser chaque réponse par type de quartier
        foreach ($reponses as $rep) {
            $type   = strtoupper(trim($rep['type_quartier'] ?? ''));
            $trouve = strtoupper(trim($rep['code_reponse']  ?? '')) === 'OUI';

            if (!$trouve) continue;

            $match = [
                'found'     => true,
                'code'      => $rep['code_quartier'] ?? null,
                'nom'       => $rep['nom_quartier']  ?? null,
                'bande_300' => false,
                'bande_500' => false,
            ];

            match ($type) {
                'QP'      => [$result['qp_2024'] = true, $result['matches']['qp_2024'] = $match],
                'QP_2015' => [$result['qp_2015'] = true, $result['matches']['qp_2015'] = $match],
                'ZFU'     => [$result['zfu']     = true, $result['matches']['zfu']     = $match],
                default   => null,
            };
        }

        return $result;
    }

    // ════════════════════════════════════════════════════════════
    // RÉSULTAT VIDE
    // ════════════════════════════════════════════════════════════
    private function emptyResult(): array
    {
        return [
            'qp_2024'     => false,
            'qp_2015'     => false,
            'zfu'         => false,
            'matches'     => [],
            'loccom_ref'  => null,
            'locvoie_ref' => null,
            'similitude'  => null,
        ];
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS — Découpage d'adresse pour format WSA
    // ════════════════════════════════════════════════════════════

    private function extractNumero(string $adresse): ?string
    {
        preg_match('/^\d+/', trim($adresse), $matches);
        return $matches[0] ?? null;
    }

    private function extractNomVoie(string $adresse): string
    {
        return trim(preg_replace('/^\d+\s*/', '', $adresse));
    }
}
