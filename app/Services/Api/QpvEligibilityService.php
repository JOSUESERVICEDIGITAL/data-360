<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * QpvEligibilityService — SIG Ville WSA API v2
 * ─────────────────────────────────────────────────────────────
 * Base URL  : https://wsa.sig.ville.gouv.fr
 * Auth      : HTTP Basic Auth (username + password)
 * Endpoints :
 *   /api/xy.json  → coordonnées GPS (PRINCIPAL)
 *   /api/v2.json  → adresse texte   (FALLBACK)
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
        $this->baseUrl  = rtrim(config('services.sigville.url',      'https://wsa.sig.ville.gouv.fr'), '/');
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
        // Cache 7 jours
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
        return !($r['qp_2024'] || $r['qp_2015'] || $r['zfu']);
    }

    // ════════════════════════════════════════════════════════════
    // APPELS API
    // ════════════════════════════════════════════════════════════

    private function fetchFromApi(?float $lat, ?float $lng, string $adresse, string $commune, string $codePostal): array
    {
        try {
            return ($lat !== null && $lng !== null)
                ? $this->callXyEndpoint($lat, $lng)
                : $this->callAddressEndpoint($adresse, $commune, $codePostal);
        } catch (\Throwable $e) {
            Log::warning('QpvEligibilityService erreur: ' . $e->getMessage());
            return $this->emptyResult();
        }
    }

    /**
     * /api/xy.json — géoréférencement par coordonnées GPS
     * ✅ Méthode principale (on a lat/lng depuis la BAN)
     */
    private function callXyEndpoint(float $lat, float $lng): array
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->get($this->baseUrl . '/api/xy.json', [
                'x'             => $lng,            // longitude
                'y'             => $lat,            // latitude
                'type_quartier' => 'QP,QP_2015,ZFU',
            ]);

        if ($response->failed()) {
            Log::warning("SIG Ville /api/xy.json → HTTP {$response->status()}");
            return $this->emptyResult();
        }

        return $this->parseResponse($response->json());
    }

    /**
     * /api/v2.json — géoréférencement par adresse texte (BAN post-2024)
     * ✅ Fallback si pas de coordonnées
     */
   private function callAddressEndpoint(string $adresse, string $commune, string $codePostal): array
{
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
        Log::warning("SIG Ville /api/v2.json → HTTP {$response->status()}");
        return $this->emptyResult();
    }

    return $this->parseResponse($response->json());
}

private function parseResponse(mixed $data): array
{
    if (empty($data)) return $this->emptyResult();

    $result   = $this->emptyResult();
    $reponses = $data['reponses'] ?? [];

    foreach ($reponses as $rep) {
        $type   = strtoupper($rep['type_quartier'] ?? '');
        $trouve = strtoupper($rep['code_reponse']  ?? '') === 'OUI';

        if (!$trouve) continue;

        $match = [
            'found' => true,
            'code'  => $rep['code_quartier'] ?? null,
            'nom'   => $rep['nom_quartier']  ?? null,
        ];

        match ($type) {
            'QP'      => [$result['qp_2024'] = true, $result['matches']['qp_2024'] = $match],
            'QP_2015' => [$result['qp_2015'] = true, $result['matches']['qp_2015'] = $match],
            'ZFU'     => [$result['zfu']     = true, $result['matches']['zfu']     = $match],
            default   => null,
        };
    }

    $result['loccom_ref']  = $data['loccom_ref']                  ?? null;
    $result['similitude']  = $data['adresse']['score']            ?? null;
    $result['locvoie_ref'] = $data['code_reponse']                ?? null;

    return $result;
}

private function extractNumero(string $adresse): ?string
{
    preg_match('/^\d+/', trim($adresse), $m);
    return $m[0] ?? null;
}

private function extractNomVoie(string $adresse): string
{
    return trim(preg_replace('/^\d+\s*/', '', $adresse));
}
    // ════════════════════════════════════════════════════════════
    // PARSING RÉPONSE
    // Variables issues du PDF guide utilisateur SIG Ville 2024
    // ════════════════════════════════════════════════════════════

    private function parseResponse(mixed $data): array
    {
        if (empty($data)) return $this->emptyResult();

        $items  = isset($data[0]) ? $data : [$data];
        $result = $this->emptyResult();

        foreach ($items as $item) {
            $locadr = strtoupper(trim($item['LOCADR_REF']    ?? ''));
            $type   = strtoupper(trim($item['TYPE_QUARTIER'] ?? ''));

            // Stocker les métadonnées de localisation (une seule fois)
            if ($result['loccom_ref'] === null) {
                $result['loccom_ref']  = $item['LOCCOM_REF']      ?? null;
                $result['locvoie_ref'] = $item['LOCVOIE_REF']     ?? null;
                $result['similitude']  = $item['SIMILITUDE_VOIE'] ?? null;
            }

            if ($locadr !== 'OUI') continue;

            // Adresse dans un quartier → mapper sur le bon type
            $match = [
                'found'     => true,
                'code'      => $item['CODE_QUARTIER'] ?? null,
                'nom'       => $item['NOM_QUARTIER']  ?? null,
                'bande_300' => strtoupper($item['QP_BANDE_300'] ?? '') === 'OUI',
                'bande_500' => strtoupper($item['QP_BANDE_500'] ?? '') === 'OUI',
                'score'     => $item['SCORE']         ?? null,
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
}
