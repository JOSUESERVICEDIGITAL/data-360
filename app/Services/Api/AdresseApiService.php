<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class AdresseApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {}

    public function search(string $adresse): ?array
    {
        $baseUrl = config('services.geocodage.base_url');
        $endpoint = $baseUrl . '/search';

        try {
            $response = Http::timeout(15)->get($endpoint, [
                'q' => $adresse,
                'limit' => 5,
            ]);

            $json = $response->json();

            $this->logger->log(
                'GEOCODAGE',
                $endpoint,
                $adresse,
                $response->status(),
                $response->successful(),
                ['q' => $adresse],
                $json
            );

            if (!$response->successful()) {
                return null;
            }

            $feature = $json['features'][0] ?? null;

            if (!$feature) {
                return null;
            }

            $properties = $feature['properties'] ?? [];
            $coordinates = $feature['geometry']['coordinates'] ?? [null, null];

            return [
                'adresse_complete' => $properties['label'] ?? $adresse,
                'numero' => $properties['housenumber'] ?? null,
                'voie' => $properties['street'] ?? ($properties['name'] ?? null),
                'code_postal' => $properties['postcode'] ?? null,
                'ville' => $properties['city'] ?? null,
                'code_insee' => $properties['citycode'] ?? null,
                'longitude' => $coordinates[0] ?? null,
                'latitude' => $coordinates[1] ?? null,
                'score' => $properties['score'] ?? null,
                'source' => 'geocodage',
                'raw_data' => $feature,
                'suggestions' => $json['features'] ?? [],
            ];
        } catch (\Throwable $e) {
            $this->logger->log('GEOCODAGE', $endpoint, $adresse, null, false, ['q' => $adresse], null, $e->getMessage());

            return null;
        }
    }
}