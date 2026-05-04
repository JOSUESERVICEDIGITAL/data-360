<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class SireneApiService
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

        $baseUrl = config('services.sirene.base_url');
        $endpoint = $baseUrl . '/siren/' . $siren;

        try {
            $request = Http::timeout(20)->acceptJson();

            if (config('services.sirene.token')) {
                $request = $request->withToken(config('services.sirene.token'));
            }

            $response = $request->get($endpoint);
            $json = $response->json();

            $this->logger->log(
                'SIRENE',
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

            $unite = $json['uniteLegale'] ?? $json;

            return [
                'siren' => $siren,
                'nom' => $unite['denominationUniteLegale']
                    ?? trim(($unite['prenom1UniteLegale'] ?? '') . ' ' . ($unite['nomUniteLegale'] ?? ''))
                    ?: null,
                'forme_juridique' => $unite['categorieJuridiqueUniteLegale'] ?? null,
                'activite' => $unite['activitePrincipaleUniteLegale'] ?? null,
                'etat_administratif' => $unite['etatAdministratifUniteLegale'] ?? null,
                'raw_data' => $json,
            ];
        } catch (\Throwable $e) {
            $this->logger->log('SIRENE', $endpoint, $siren, null, false, ['siren' => $siren], null, $e->getMessage());

            return null;
        }
    }

    public function searchEtablissementsBySiren(?string $siren): array
    {
        if (!$siren) {
            return [];
        }

        $siren = preg_replace('/\D/', '', $siren);

        $baseUrl = config('services.sirene.base_url');
        $endpoint = $baseUrl . '/siret';

        try {
            $request = Http::timeout(20)->acceptJson();

            if (config('services.sirene.token')) {
                $request = $request->withToken(config('services.sirene.token'));
            }

            $response = $request->get($endpoint, [
                'q' => 'siren:' . $siren,
                'nombre' => 20,
            ]);

            $json = $response->json();

            $this->logger->log(
                'SIRENE_ETABLISSEMENTS',
                $endpoint,
                $siren,
                $response->status(),
                $response->successful(),
                ['q' => 'siren:' . $siren],
                $json
            );

            if (!$response->successful()) {
                return [];
            }

            return collect($json['etablissements'] ?? [])
                ->map(function ($etab) {
                    $adresse = $etab['adresseEtablissement'] ?? [];

                    return [
                        'siret' => $etab['siret'] ?? null,
                        'siren' => $etab['siren'] ?? null,
                        'adresse_complete' => trim(
                            ($adresse['numeroVoieEtablissement'] ?? '') . ' ' .
                            ($adresse['typeVoieEtablissement'] ?? '') . ' ' .
                            ($adresse['libelleVoieEtablissement'] ?? '') . ' ' .
                            ($adresse['codePostalEtablissement'] ?? '') . ' ' .
                            ($adresse['libelleCommuneEtablissement'] ?? '')
                        ),
                        'code_postal' => $adresse['codePostalEtablissement'] ?? null,
                        'ville' => $adresse['libelleCommuneEtablissement'] ?? null,
                        'etat_administratif' => $etab['etatAdministratifEtablissement'] ?? null,
                        'raw_data' => $etab,
                    ];
                })
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            $this->logger->log('SIRENE_ETABLISSEMENTS', $endpoint, $siren, null, false, null, null, $e->getMessage());

            return [];
        }
    }
}