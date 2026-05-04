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
        if (!$siren) {
            return null;
        }

        $siren = preg_replace('/\D/', '', $siren);

        if (strlen($siren) !== 9) {
            return null;
        }

        $apiKey = config('services.pappers.api_key');

        if (!$apiKey) {
            return null;
        }

        $endpoint = rtrim(config('services.pappers.base_url'), '/') . '/entreprise';

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->get($endpoint, [
                    'api_token' => $apiKey,
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

            $dirigeant = $json['representants'][0] ?? null;
            $siege = $json['siege'] ?? [];

            return [
                'siren' => $json['siren'] ?? $siren,
                'siret' => $siege['siret'] ?? null,

                'nom' => $json['nom_entreprise']
                    ?? $json['denomination']
                    ?? $json['denomination_sociale']
                    ?? null,

                'forme_juridique' => $json['forme_juridique'] ?? null,
                'capital_social' => $json['capital'] ?? $json['capital_social'] ?? null,

                'chiffre_affaires' => $json['chiffre_affaires'] ?? null,
                'resultat' => $json['resultat'] ?? null,
                'effectif' => $json['effectif'] ?? null,
                'date_creation' => $json['date_creation'] ?? null,

                'dirigeant_principal' => $dirigeant
                    ? trim(($dirigeant['prenom'] ?? '') . ' ' . ($dirigeant['nom'] ?? ''))
                    : null,

                'adresse_complete' => $siege['adresse_ligne_1']
                    ?? $siege['adresse']
                    ?? null,

                'code_postal' => $siege['code_postal'] ?? null,
                'ville' => $siege['ville'] ?? null,

                'url_pappers' => 'https://www.pappers.fr/entreprise/' . $siren,

                'raw_data' => $json,
            ];
        } catch (\Throwable $e) {
            $this->logger->log(
                'PAPPERS',
                $endpoint,
                $siren,
                null,
                false,
                ['siren' => $siren],
                null,
                $e->getMessage()
            );

            return null;
        }
    }
}