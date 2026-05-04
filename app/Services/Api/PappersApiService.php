<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class PappersApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {}

    public function searchBySiren(?string $siren): ?array
    {
        if (!$siren || !config('services.pappers.api_key')) {
            return null;
        }

        $siren = preg_replace('/\D/', '', $siren);

        if (strlen($siren) !== 9) {
            return null;
        }

        $baseUrl = config('services.pappers.base_url');
        $endpoint = $baseUrl . '/entreprise';

        try {
            $response = Http::timeout(20)->get($endpoint, [
                'api_token' => config('services.pappers.api_key'),
                'siren' => $siren,
            ]);

            $json = $response->json();

            $this->logger->log(
                'PAPPERS',
                $endpoint,
                $siren,
                $response->status(),
                $response->successful(),
                ['siren' => $siren],
                $json
            );

            if (!$response->successful()) {
                return null;
            }

            return [
                'siren' => $json['siren'] ?? $siren,
                'nom' => $json['nom_entreprise'] ?? $json['denomination'] ?? null,
                'forme_juridique' => $json['forme_juridique'] ?? null,
                'capital' => $json['capital'] ?? null,
                'chiffre_affaires' => $json['chiffre_affaires'] ?? null,
                'resultat' => $json['resultat'] ?? null,
                'effectif' => $json['effectif'] ?? null,
                'dirigeants' => $json['representants'] ?? [],
                'siege' => $json['siege'] ?? null,
                'raw_data' => $json,
            ];
        } catch (\Throwable $e) {
            $this->logger->log('PAPPERS', $endpoint, $siren, null, false, ['siren' => $siren], null, $e->getMessage());

            return null;
        }
    }
}