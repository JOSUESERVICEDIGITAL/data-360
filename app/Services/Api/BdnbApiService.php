<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class BdnbApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {}

    public function searchByParcelle(?string $idParcelle): array
    {
        if (!$idParcelle) {
            return [];
        }

        $idParcelle = $this->normalizeParcelleId($idParcelle);
        $baseUrl = rtrim(config('services.bdnb.base_url'), '/');

        $relations = $this->get(
            $baseUrl . '/v1/bdnb/donnees/rel_batiment_groupe_parcelle',
            [
                'parcelle_id' => 'eq.' . $idParcelle,
                'limit' => 50,
            ],
            'BDNB_REL_PARCELLE'
        );

        $batimentIds = collect($relations)
            ->pluck('batiment_groupe_id')
            ->filter()
            ->unique()
            ->values();

        if ($batimentIds->isEmpty()) {
            return [];
        }

        $batiments = [];

        foreach ($batimentIds as $batimentId) {
            $items = $this->get(
                $baseUrl . '/v1/bdnb/donnees/batiment_groupe_complet',
                [
                    'batiment_groupe_id' => 'eq.' . $batimentId,
                    'limit' => 1,
                ],
                'BDNB_BATIMENT_COMPLET'
            );

            foreach ($items as $item) {
                $batiments[] = $item;
            }
        }

        return $this->normalizeMany($batiments);
    }

    public function searchByAddress(string $adresse): array
    {
        $baseUrl = rtrim(config('services.bdnb.base_url'), '/');

        $items = $this->get(
            $baseUrl . '/v1/bdnb/donnees/batiment_groupe_complet',
            [
                'libelle_adr_principale_ban' => 'ilike.*' . $adresse . '*',
                'limit' => 20,
            ],
            'BDNB_ADRESSE'
        );

        return $this->normalizeMany($items);
    }

    public function searchByCoordinates(?float $latitude, ?float $longitude): array
    {
        return [];
    }

    private function get(string $endpoint, array $query, string $apiName): array
    {
        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->get($endpoint, $query);

            $json = $response->json();

            $this->logger->log(
                $apiName,
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

            return is_array($json) ? $json : [];
        } catch (\Throwable $e) {
            $this->logger->log(
                $apiName,
                $endpoint,
                $query,
                null,
                false,
                $query,
                null,
                $e->getMessage()
            );

            return [];
        }
    }

    private function normalizeMany(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                $data = $item['properties'] ?? $item;

                return [
                    'identifiant_bdnb' => $data['batiment_groupe_id']
                        ?? $data['id_batiment_groupe']
                        ?? $data['id']
                        ?? null,

                    'identifiant_cadastre' => $data['parcelle_id']
                        ?? $data['id_parcelle']
                        ?? $data['cle_interop_adr']
                        ?? null,

                    'type_batiment' => $this->guessTypeBatiment($data),

                    'annee_construction' => $data['annee_construction']
                        ?? $data['annee_construction_estimee']
                        ?? $data['annee_construction_dpe']
                        ?? $data['annee_construction_max']
                        ?? null,

                    'nombre_logements' => $data['nb_log']
                        ?? $data['nb_logements']
                        ?? $data['nombre_logements']
                        ?? null,

                    'nombre_niveaux' => $data['nb_niveau']
                        ?? $data['nb_niveaux']
                        ?? $data['nombre_niveaux']
                        ?? null,

                    'hauteur' => $data['hauteur_mean']
                        ?? $data['hauteur']
                        ?? null,

                    'surface_habitable' => $data['surface_habitable']
                        ?? $data['s_hab']
                        ?? null,

                    'surface_emprise_sol' => $data['surface_emprise_sol']
                        ?? $data['s_emprise_sol']
                        ?? null,

                    'classe_dpe' => $data['classe_dpe']
                        ?? $data['dpe_classe']
                        ?? $data['classe_bilan_dpe']
                        ?? null,

                    'ges' => $data['classe_ges']
                        ?? $data['ges']
                        ?? null,

                    'type_chauffage' => $data['type_chauffage']
                        ?? $data['chauffage']
                        ?? $data['type_installation_chauffage']
                        ?? null,

                    'energie_chauffage' => $data['energie_chauffage']
                        ?? $data['energie_principale_chauffage']
                        ?? null,

                    'raw_data' => $data,
                ];
            })
            ->filter(fn ($item) => !empty($item['identifiant_bdnb']) || !empty($item['raw_data']))
            ->values()
            ->toArray();
    }

    private function normalizeParcelleId(string $id): string
    {
        return strtoupper(trim($id));
    }

    private function guessTypeBatiment(array $data): string
    {
        $usage = strtolower((string) (
            $data['usage_niveau_1_txt']
            ?? $data['usage_principal']
            ?? $data['type_batiment']
            ?? ''
        ));

        $nbLogements = (int) (
            $data['nb_log']
            ?? $data['nb_logements']
            ?? $data['nombre_logements']
            ?? 0
        );

        if (str_contains($usage, 'tertiaire')) {
            return 'tertiaire';
        }

        if ($nbLogements >= 2) {
            return 'collectif';
        }

        if ($nbLogements === 1) {
            return 'individuel';
        }

        return 'inconnu';
    }
}