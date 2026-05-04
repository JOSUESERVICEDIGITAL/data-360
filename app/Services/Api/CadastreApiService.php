<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class CadastreApiService
{
    public function __construct(
        protected ?ApiLoggerService $logger = null
    ) {}

    public function searchByCoordinates(?float $latitude, ?float $longitude, ?string $codeInsee = null): array
    {
        if (!$latitude || !$longitude) {
            return [];
        }

        $endpoint = 'https://apicarto.ign.fr/api/cadastre/parcelle';

        $attempts = [
            [
                'name' => 'point_exact',
                'query' => [
                    'geom' => $this->pointGeoJson($longitude, $latitude),
                ],
            ],
            [
                'name' => 'point_buffer_small',
                'query' => [
                    'geom' => $this->bboxGeoJson($longitude, $latitude, 0.00005),
                ],
            ],
            [
                'name' => 'point_buffer_large',
                'query' => [
                    'geom' => $this->bboxGeoJson($longitude, $latitude, 0.00012),
                ],
            ],
        ];

        foreach ($attempts as $attempt) {
            $result = $this->callCadastre(
                $endpoint,
                $attempt['query'],
                $attempt['name'],
                $latitude,
                $longitude,
                $codeInsee
            );

            if (!empty($result)) {
                return $this->filterByCodeInsee($result, $codeInsee);
            }
        }

        return [];
    }

    private function callCadastre(
        string $endpoint,
        array $query,
        string $attemptName,
        float $latitude,
        float $longitude,
        ?string $codeInsee
    ): array {
        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->retry(2, 300)
                ->get($endpoint, $query);

            $json = $response->json();

            $this->log(
                'CADASTRE_' . strtoupper($attemptName),
                $endpoint,
                [
                    'attempt' => $attemptName,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'code_insee' => $codeInsee,
                    'query' => $query,
                ],
                $response->status(),
                $response->successful(),
                $query,
                $json
            );

            if (!$response->successful() || empty($json['features'])) {
                return [];
            }

            return $this->normalizeMany($json);
        } catch (\Throwable $e) {
            $this->log(
                'CADASTRE_' . strtoupper($attemptName),
                $endpoint,
                [
                    'attempt' => $attemptName,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'code_insee' => $codeInsee,
                ],
                null,
                false,
                $query,
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

                $codeInsee = $p['code_insee'] ?? null;

                if (!$codeInsee && !empty($p['code_dep']) && !empty($p['code_com'])) {
                    $codeInsee = $p['code_dep'] . $p['code_com'];
                }

                return [
                    'id_parcelle' => $p['idu'] ?? $p['id'] ?? null,
                    'commune' => $p['nom_com'] ?? $p['commune'] ?? null,
                    'code_insee' => $codeInsee,
                    'section' => $p['section'] ?? null,
                    'numero' => $p['numero'] ?? null,
                    'contenance' => $p['contenance'] ?? null,
                    'raw_data' => $feature,
                ];
            })
            ->filter(fn ($item) => !empty($item['id_parcelle']) || !empty($item['section']))
            ->unique(fn ($item) => ($item['id_parcelle'] ?? '') . '-' . ($item['section'] ?? '') . '-' . ($item['numero'] ?? ''))
            ->values()
            ->toArray();
    }

    private function filterByCodeInsee(array $parcelles, ?string $codeInsee): array
    {
        if (!$codeInsee) {
            return $parcelles;
        }

        $filtered = collect($parcelles)
            ->filter(fn ($p) => ($p['code_insee'] ?? null) === $codeInsee)
            ->values()
            ->toArray();

        return !empty($filtered) ? $filtered : $parcelles;
    }

    private function pointGeoJson(float $longitude, float $latitude): string
    {
        return json_encode([
            'type' => 'Point',
            'coordinates' => [
                $longitude,
                $latitude,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function bboxGeoJson(float $longitude, float $latitude, float $delta): string
    {
        return json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [$longitude - $delta, $latitude - $delta],
                [$longitude + $delta, $latitude - $delta],
                [$longitude + $delta, $latitude + $delta],
                [$longitude - $delta, $latitude + $delta],
                [$longitude - $delta, $latitude - $delta],
            ]],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function log(
        string $apiName,
        string $endpoint,
        mixed $query,
        ?int $statusCode,
        bool $success,
        mixed $requestData = null,
        mixed $responseData = null,
        ?string $errorMessage = null
    ): void {
        if (!$this->logger) {
            return;
        }

        $this->logger->log(
            $apiName,
            $endpoint,
            $query,
            $statusCode,
            $success,
            $requestData,
            $responseData,
            $errorMessage
        );
    }
}