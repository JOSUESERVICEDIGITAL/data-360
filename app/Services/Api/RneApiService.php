<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RneApiService
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.rne.base_url', 'https://api.inpi.fr/v1');
        $this->apiKey  = config('services.rne.api_key');
    }

    /**
     * Récupère les données d'une entreprise par son SIREN
     */
    public function getBySiren(string $siren): ?array
    {
        if (strlen($siren) !== 9) {
            Log::warning('SIREN invalide', ['siren' => $siren]);
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                ])
                ->get($this->baseUrl . '/entreprises/' . $siren);

            if ($response->successful()) {
                $data = $response->json();
                return $this->normalizeApiData($data);
            }

            Log::warning('RNE API error', [
                'siren'  => $siren,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('RNE API exception', [
                'siren' => $siren,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Normalise les données de l'API vers le format attendu par saveRneFromApi()
     */
    protected function normalizeApiData(array $data): array
    {
        // Adapter selon la structure réelle de l'API (exemple)
        return [
            'siren'            => $data['siren'] ?? null,
            'siret_siege'      => $data['siret'] ?? $data['siret_siege'] ?? null,
            'denomination'     => $data['denomination'] ?? $data['nom_raison_sociale'] ?? null,
            'forme_juridique'  => $data['forme_juridique'] ?? $data['formeJuridique'] ?? null,
            'capital_social'   => $data['capital_social'] ?? null,
            'activite'         => $data['activite'] ?? $data['code_ape'] ?? null,
            'date_creation'    => $data['date_creation'] ?? $data['dateCreation'] ?? null,
            'adresse_complete' => $data['adresse'] ?? $data['adresse_complete'] ?? null,
            'code_postal'      => $data['code_postal'] ?? null,
            'ville'            => $data['ville'] ?? null,
            'dirigeants'       => $data['dirigeants'] ?? $data['representants'] ?? [],
        ];
    }
}
