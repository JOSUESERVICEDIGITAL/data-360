<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class CadastreApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {}

    public function searchByCoordinates(?float $latitude, ?float $longitude, ?string $codeInsee = null): array
    {
        if (!$latitude || !$longitude) {
            return [];
        }

        $baseUrl = rtrim(config('services.cadastre.base_url'), '/');
        $endpoint = $baseUrl . '/parcelle';

        $geojson = [
            'type' => 'Point',
            'coordinates' => [
                (float) $longitude,
                (float) $latitude,
            ],
        ];

        try {
            $query = [
                'geom' => json_encode($geojson, JSON_UNESCAPED_UNICODE),
            ];

            $response = Http::timeout(20)
                ->acceptJson()
                ->get($endpoint, $query);

            $json = $response->json();

            $this->logger->log(
                'CADASTRE',
                $endpoint,
                $query,
                $response->status(),
                $response->successful(),
                $query,
                $json
            );

            if (!$response->successful()) {
                return [];
            }

            return $this->normalizeMany($json);
        } catch (\Throwable $e) {
            $this->logger->log(
                'CADASTRE',
                $endpoint,
                compact('latitude', 'longitude', 'codeInsee'),
                null,
                false,
                null,
                null,
                $e->getMessage()
            );

            return [];
        }
    }

    private function normalizeMany(mixed $json): array
    {
        $features = $json['features'] ?? [];

        return collect($features)
            ->map(function ($feature) {
                $p = $feature['properties'] ?? [];

                return [
                    'id_parcelle' => $p['idu'] ?? $p['id'] ?? null,
                    'commune' => $p['nom_com'] ?? $p['commune'] ?? null,
                    'code_insee' => $p['code_insee'] ?? null,
                    'section' => $p['section'] ?? null,
                    'numero' => $p['numero'] ?? null,
                    'contenance' => $p['contenance'] ?? null,
                    'raw_data' => $feature,
                ];
            })
            ->values()
            ->toArray();
    }
}