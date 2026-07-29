<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function isEligible(?float $lat, ?float $lng, string $adresse = '', string $commune = '', string $codePostal = ''): bool
    {
        $r = $this->check($lat, $lng, $adresse, $commune, $codePostal);
        return $r['qp_2024'] || $r['qp_2015'] || $r['zfu'];
    }

    // ════════════════════════════════════════════════════════════
    // APPELS API
    // ════════════════════════════════════════════════════════════
    private function fetchFromApi(?float $lat, ?float $lng, string $adresse, string $commune, string $codePostal): array
    {
        try {
            if ($lat !== null && $lng !== null) {
                return $this->callXyEndpoint($lat, $lng);
            }
            return $this->callAddressEndpoint($adresse, $commune, $codePostal);
        } catch (\Throwable $e) {
            Log::warning('QpvEligibilityService erreur: ' . $e->getMessage());
            return $this->emptyResult();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // ENDPOINT XY — /api/xy.json
    // ⚠️ L'API XY n'accepte PAS les types en virgule ni en tableau.
    //    Il faut faire 3 appels séparés, un par type de quartier.
    // ─────────────────────────────────────────────────────────────
    private function callXyEndpoint(float $lat, float $lng): array
    {
        $result = $this->emptyResult();

        foreach (['QP' => 'qp_2024', 'QP_2015' => 'qp_2015', 'ZFU' => 'zfu'] as $typeApi => $resultKey) {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout($this->timeout)
                    ->get($this->baseUrl . '/api/xy.json', [
                        'x'             => $lng,
                        'y'             => $lat,
                        'type_quartier' => $typeApi,
                    ]);

                if ($response->failed()) {
                    Log::warning("SIG Ville /api/xy.json [{$typeApi}] → HTTP {$response->status()}");
                    continue;
                }

                $data     = $response->json();
                $reponses = $data['reponses'] ?? [];

                // Métadonnées (une seule fois)
                if ($result['loccom_ref'] === null) {
                    $result['loccom_ref'] = $data['loccom_ref'] ?? null;
                }

                foreach ($reponses as $rep) {
                    if (strtoupper(trim($rep['code_reponse'] ?? '')) === 'OUI') {
                        $result[$resultKey]              = true;
                        $result['matches'][$resultKey]   = [
                            'found'     => true,
                            'code'      => $rep['code_quartier'] ?? null,
                            'nom'       => $rep['nom_quartier']  ?? null,
                            'bande_300' => false,
                            'bande_500' => false,
                        ];
                    }
                }

            } catch (\Throwable $e) {
                Log::warning("SIG Ville /api/xy.json [{$typeApi}] erreur: " . $e->getMessage());
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // ENDPOINT V2 — /api/v2.json (format WSA avec crochets)
    // Validé 29/07/2026 — type_quartier[] fonctionne avec v2
    // ─────────────────────────────────────────────────────────────
    private function callAddressEndpoint(string $adresse, string $commune, string $codePostal): array
    {
        if (empty($adresse) && empty($commune)) return $this->emptyResult();

        $result = $this->emptyResult();

        foreach (['QP' => 'qp_2024', 'QP_2015' => 'qp_2015', 'ZFU' => 'zfu'] as $typeApi => $resultKey) {
            try {
                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout($this->timeout)
                    ->get($this->baseUrl . '/api/v2.json', [
                        'type_adresse'         => 'WSA',
                        'adresse[num_voie]'    => $this->extractNumero($adresse),
                        'adresse[nom_voie]'    => $this->extractNomVoie($adresse),
                        'adresse[code_postal]' => $codePostal,
                        'adresse[nom_commune]' => $commune,
                        'type_quartier'        => $typeApi,
                    ]);

                if ($response->failed()) {
                    Log::warning("SIG Ville /api/v2.json [{$typeApi}] → HTTP {$response->status()}");
                    continue;
                }

                $data     = $response->json();
                $reponses = $data['reponses'] ?? [];

                if ($result['loccom_ref'] === null) {
                    $result['loccom_ref']  = $data['loccom_ref']       ?? null;
                    $result['similitude']  = $data['adresse']['score'] ?? null;
                }

                foreach ($reponses as $rep) {
                    if (strtoupper(trim($rep['code_reponse'] ?? '')) === 'OUI') {
                        $result[$resultKey]            = true;
                        $result['matches'][$resultKey] = [
                            'found'     => true,
                            'code'      => $rep['code_quartier'] ?? null,
                            'nom'       => $rep['nom_quartier']  ?? null,
                            'bande_300' => false,
                            'bande_500' => false,
                        ];
                    }
                }

            } catch (\Throwable $e) {
                Log::warning("SIG Ville /api/v2.json [{$typeApi}] erreur: " . $e->getMessage());
            }
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
    // HELPERS
    // ════════════════════════════════════════════════════════════
    private function extractNumero(string $adresse): ?string
    {
        preg_match('/^\d+/', trim($adresse), $m);
        return $m[0] ?? null;
    }

    private function extractNomVoie(string $adresse): string
    {
        return trim(preg_replace('/^\d+\s*/', '', $adresse));
    }
}
