<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RnbApiService
{
    protected string $baseUrl;
    protected ?string $from;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.rnb.base_url', 'https://rnb-api.beta.gouv.fr/api/alpha'),
            '/'
        );


        $this->from = config('services.rnb.from');
    }

    public function searchByAddress(string $address, float $minScore = 0.60, int $limit = 20): array
    {
        if (!$address) {
            return $this->emptyResult('missing_address');
        }

        return $this->get('/buildings/address/', [
            'q' => $address,
            'min_score' => $minScore,
            'limit' => $limit,  // Ajout explicite de la limite
        ]);
    }
    public function searchByBanKey(string $cleInteropBan): array
    {
        if (!$cleInteropBan) {
            return $this->emptyResult('missing_cle_interop_ban');
        }

        return $this->get('/buildings/address/', [
            'cle_interop_ban' => $cleInteropBan,
        ]);
    }

    public function searchClosest(float $latitude, float $longitude, int $radius = 80): array
    {
        $radius = max(1, min($radius, 1000));

        return $this->get('/buildings/closest/', [
            'point' => $latitude . ',' . $longitude,
            'radius' => $radius,
        ]);
    }
    public function searchByPlot(?string $plotId): array
    {
        if (!$plotId) {
            return $this->emptyResult('missing_plot_id');
        }

        return $this->get('/buildings/plot/' . rawurlencode($plotId) . '/', []);
    }

    public function getBuilding(string $rnbId, bool $withPlots = true): array
    {
        if (!$rnbId) {
            return $this->emptyResult('missing_rnb_id');
        }

        return $this->get('/buildings/' . rawurlencode($rnbId) . '/', [
            'withPlots' => $withPlots ? 'true' : 'false',
        ]);
    }
 public function searchSmart(array $data): array
{
    $address = $data['address'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $plotId = $data['plot_id'] ?? null;
    $cleInteropBan = $data['cle_interop_ban'] ?? null;

    $results = [];
    $bestBatiments = [];

    // PRIORITÉ 1 : Recherche par adresse (la plus précise)
    if ($address) {
        $addressResult = $this->get('/buildings/address/', [
            'q' => $address,
            'min_score' => 0.50,
            'limit' => 50,
        ]);
        
        if (!empty($addressResult['results'])) {
            $results['by_address'] = $addressResult;
            $bestBatiments = $addressResult['results'];
        }
    }

    // PRIORITÉ 2 : Recherche par clé BAN (très précise aussi)
    if (empty($bestBatiments) && $cleInteropBan) {
        $banResult = $this->get('/buildings/address/', [
            'cle_interop_ban' => $cleInteropBan,
            'limit' => 50,
        ]);
        
        if (!empty($banResult['results'])) {
            $results['by_ban_key'] = $banResult;
            $bestBatiments = $banResult['results'];
        }
    }

    // PRIORITÉ 3 : Recherche par coordonnées avec rayon réduit (seulement si pas de résultat)
    if (empty($bestBatiments) && $latitude && $longitude) {
        $closestResult = $this->get('/buildings/closest/', [
            'point' => $latitude . ',' . $longitude,
            'radius' => 50,  // Rayon réduit à 50m au lieu de 200m
            'limit' => 20,
        ]);
        
        if (!empty($closestResult['results'])) {
            $results['closest'] = $closestResult;
            $bestBatiments = $closestResult['results'];
        }
    }

    // PRIORITÉ 4 : Recherche par parcelle (dernier recours)
    if (empty($bestBatiments) && $plotId) {
        $plotResult = $this->get('/buildings/plot/' . rawurlencode($plotId) . '/', [
            'limit' => 20,
        ]);
        
        if (!empty($plotResult['results'])) {
            $results['by_plot'] = $plotResult;
            $bestBatiments = $plotResult['results'];
        }
    }

    // Normalisation des bâtiments trouvés
    $batiments = collect($bestBatiments)
        ->filter()
        ->unique(fn ($item) => $item['rnb_id'] ?? $item['id'] ?? md5(json_encode($item)))
        ->values()
        ->map(fn ($item) => $this->normalizeBuilding($item))
        ->toArray();

    return [
        'success' => count($batiments) > 0,
        'status' => count($batiments) > 0 ? 'ok' : 'empty',
        'batiments' => $batiments,
        'raw' => $results,
    ];
}

    public function searchByRnbId(string $rnbId): array
    {
        if (!$rnbId) {
            return $this->emptyResult('missing_rnb_id');
        }

        return $this->get('/buildings/' . rawurlencode($rnbId) . '/', [
            'withPlots' => 'true',
        ]);
    }
    protected function get(string $endpoint, array $query = []): array
    {
        try {
            if ($this->from) {
                $query['from'] = $this->from;
            }

            $url = $this->baseUrl . $endpoint;

            $response = Http::timeout(15)
                ->retry(2, 300)
                ->acceptJson()
                ->get($url, $query);

            if (!$response->successful()) {
                Log::warning('RNB API error', [
                    'url' => $url,
                    'query' => $query,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'status' => 'api_error',
                    'http_status' => $response->status(),
                    'results' => [],
                    'raw' => $response->json(),
                ];
            }

            $json = $response->json();

            if (isset($json['rnb_id']) || isset($json['id'])) {
                return [
                    'success' => true,
                    'status' => 'ok',
                    'results' => [$json],
                    'raw' => $json,
                ];
            }

            return [
                'success' => true,
                'status' => $json['status'] ?? 'ok',
                'next' => $json['next'] ?? null,
                'previous' => $json['previous'] ?? null,
                'cle_interop_ban' => $json['cle_interop_ban'] ?? null,
                'score_ban' => $json['score_ban'] ?? null,
                'results' => $json['results'] ?? [],
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            Log::error('RNB API exception', [
                'endpoint' => $endpoint,
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'exception',
                'message' => $e->getMessage(),
                'results' => [],
                'raw' => null,
            ];
        }
    }
    protected function normalizeBuilding(array $item): array
    {
        $rnbId = $item['rnb_id'] ?? $item['id'] ?? $item['rnbId'] ?? null;

        $addresses = $item['addresses'] ?? $item['adresses'] ?? [];

        $normalizedAddresses = [];
        foreach ($addresses as $addr) {
            // Reconstruction de l'adresse complète
            $numero = $addr['street_number'] ?? $addr['numero'] ?? null;
            $voie = $addr['street'] ?? $addr['voie'] ?? $addr['libelle_voie'] ?? null;
            $codePostal = $addr['city_zipcode'] ?? $addr['code_postal'] ?? null;
            $ville = $addr['city_name'] ?? $addr['ville'] ?? $addr['nom_commune'] ?? null;

            // Construction de l'adresse formatée
            $parts = [];
            if ($numero)
                $parts[] = $numero;
            if ($voie)
                $parts[] = $voie;
            $adresseComplete = implode(' ', $parts);

            // Ajout de la ville et code postal
            if ($codePostal || $ville) {
                $villeStr = '';
                if ($codePostal)
                    $villeStr .= $codePostal;
                if ($ville)
                    $villeStr .= ($villeStr ? ' ' : '') . $ville;
                if ($villeStr) {
                    $adresseComplete .= ', ' . $villeStr;
                }
            }

            $normalizedAddresses[] = [
                'label' => $adresseComplete,
                'adresse' => $adresseComplete,
                'cle_interop_ban' => $addr['id'] ?? $addr['cle_interop_ban'] ?? null,
                'ban_id' => $addr['ban_id'] ?? null,
                'street_number' => $numero,
                'street' => $voie,
                'city_zipcode' => $codePostal,
                'city_name' => $ville,
            ];
        }

        return [
            'rnb_id' => $rnbId,
            'id' => $rnbId,
            'status' => $item['status'] ?? null,
            'adresse' => $normalizedAddresses[0]['label'] ?? null,
            'addresses' => $normalizedAddresses,
            'latitude' => $this->extractLatitude($item),
            'longitude' => $this->extractLongitude($item),
            'plots' => $item['plots'] ?? [],
            'raw_data' => $item,
        ];
    }
protected function normalizeAddresses(array $addresses): array
{
    return collect($addresses)
        ->map(function ($address) {
            // Si c'est une chaîne simple
            if (is_string($address)) {
                return [
                    'label' => $address,
                    'adresse' => $address,
                    'cle_interop_ban' => null,
                    'ban_id' => null,
                ];
            }
            
            // Reconstruction de l'adresse complète depuis les composants
            $numero = $address['street_number'] ?? $address['numero'] ?? null;
            $voie = $address['street'] ?? $address['voie'] ?? $address['libelle_voie'] ?? null;
            $codePostal = $address['city_zipcode'] ?? $address['code_postal'] ?? null;
            $ville = $address['city_name'] ?? $address['ville'] ?? $address['nom_commune'] ?? null;
            
            $parts = [];
            if ($numero) $parts[] = $numero;
            if ($voie) $parts[] = $voie;
            $label = implode(' ', $parts);
            
            if ($codePostal || $ville) {
                $villeStr = '';
                if ($codePostal) $villeStr .= $codePostal;
                if ($ville) $villeStr .= ($villeStr ? ' ' : '') . $ville;
                if ($villeStr) {
                    $label .= ', ' . $villeStr;
                }
            }
            
            return [
                'label' => $label,
                'adresse' => $label,
                'cle_interop_ban' => $address['id'] ?? $address['cle_interop_ban'] ?? null,
                'ban_id' => $address['ban_id'] ?? null,
                'street_number' => $numero,
                'street' => $voie,
                'city_zipcode' => $codePostal,
                'city_name' => $ville,
            ];
        })
        ->filter(fn ($address) => !empty($address['label']))
        ->values()
        ->toArray();
}    protected function firstAddressLabel(array $addresses): ?string
    {
        $normalized = $this->normalizeAddresses($addresses);

        return $normalized[0]['label'] ?? null;
    }

    protected function extractLatitude(array $item): ?float
    {
        if (isset($item['point']['coordinates'][1])) {
            return (float) $item['point']['coordinates'][1];
        }

        if (isset($item['geometry']['coordinates'][1])) {
            return (float) $item['geometry']['coordinates'][1];
        }

        if (isset($item['centroid']['coordinates'][1])) {
            return (float) $item['centroid']['coordinates'][1];
        }

        return isset($item['latitude']) ? (float) $item['latitude'] : null;
    }

    protected function extractLongitude(array $item): ?float
    {
        if (isset($item['point']['coordinates'][0])) {
            return (float) $item['point']['coordinates'][0];
        }

        if (isset($item['geometry']['coordinates'][0])) {
            return (float) $item['geometry']['coordinates'][0];
        }

        if (isset($item['centroid']['coordinates'][0])) {
            return (float) $item['centroid']['coordinates'][0];
        }

        return isset($item['longitude']) ? (float) $item['longitude'] : null;
    }

    protected function emptyResult(string $status): array
    {
        return [
            'success' => false,
            'status' => $status,
            'results' => [],
            'raw' => null,
        ];
    }
}