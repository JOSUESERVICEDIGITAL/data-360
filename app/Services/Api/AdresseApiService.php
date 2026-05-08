<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class AdresseApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {
    }

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

                'ban_candidates' => collect($json['features'] ?? [])
                    ->take(3)
                    ->map(function ($feature) {
                        $props = $feature['properties'] ?? [];
                        $coords = $feature['geometry']['coordinates'] ?? [];

                        return [
                            'adresse' => $props['label'] ?? null,
                            'score' => $props['score'] ?? null,
                            'latitude' => $coords[1] ?? null,
                            'longitude' => $coords[0] ?? null,
                            'code_postal' => $props['postcode'] ?? null,
                            'ville' => $props['city'] ?? null,
                            'source' => 'BAN',
                        ];
                    })
                    ->filter(fn($item) => $item['latitude'] && $item['longitude'])
                    ->values()
                    ->toArray(),
            ];
        } catch (\Throwable $e) {
            $this->logger->log('GEOCODAGE', $endpoint, $adresse, null, false, ['q' => $adresse], null, $e->getMessage());

            return null;
        }
    }
}