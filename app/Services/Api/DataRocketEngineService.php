<?php

namespace App\Services\Api;

use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Recherche;
use App\Models\Back\Syndic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataRocketEngineService
{
    public function __construct(
        protected AdresseApiService $adresseApi,
        protected BdnbApiService $bdnbApi,
        protected CadastreApiService $cadastreApi,
        protected CoproprieteApiService $coproprieteApi,
        protected SireneApiService $sireneApi,
        protected PappersApiService $pappersApi,
    ) {}

    public function searchByAddress(string $query): array
    {
        return DB::transaction(function () use ($query) {

            $geo = $this->adresseApi->search($query);

            if (!$geo) {
                $recherche = Recherche::create([
                    'user_id' => Auth::id(),
                    'requete' => $query,
                    'statut' => 'introuvable',
                    'message' => 'Adresse introuvable au géocodage.',
                    'resultat' => null,
                ]);

                return [
                    'success' => false,
                    'message' => 'Adresse introuvable.',
                    'recherche' => $recherche,
                    'adresse' => null,
                    'cadastre' => [],
                    'batiments' => [],
                    'coproprietes' => [],
                    'syndics' => [],
                ];
            }

            $adresse = Adresse::updateOrCreate(
                [
                    'adresse_complete' => $geo['adresse_complete'],
                ],
                [
                    'numero' => $geo['numero'] ?? null,
                    'voie' => $geo['voie'] ?? null,
                    'code_postal' => $geo['code_postal'] ?? null,
                    'ville' => $geo['ville'] ?? null,
                    'code_insee' => $geo['code_insee'] ?? null,
                    'latitude' => $geo['latitude'] ?? null,
                    'longitude' => $geo['longitude'] ?? null,
                    'source' => $geo['source'] ?? 'geocodage',
                    'raw_data' => $geo['raw_data'] ?? $geo,
                ]
            );

            $cadastre = $this->cadastreApi->searchByCoordinates(
                $geo['latitude'] ?? null,
                $geo['longitude'] ?? null,
                $geo['code_insee'] ?? null
            );

            $batimentsApi = [];

            $parcelleId = $cadastre[0]['id_parcelle'] ?? null;

            if ($parcelleId) {
                $batimentsApi = $this->bdnbApi->searchByParcelle($parcelleId);
            }

            if (empty($batimentsApi)) {
                $batimentsApi = $this->bdnbApi->searchByAddress($geo['adresse_complete']);
            }

            $batiments = [];

            foreach ($batimentsApi as $batimentData) {
                $uniqueKey = $batimentData['identifiant_bdnb']
                    ?? md5(json_encode($batimentData['raw_data'] ?? $batimentData));

                $batiment = Batiment::updateOrCreate(
                    [
                        'adresse_id' => $adresse->id,
                        'identifiant_bdnb' => $uniqueKey,
                    ],
                    [
                        'identifiant_cadastre' => $batimentData['identifiant_cadastre'] ?? null,
                        'type_batiment' => $batimentData['type_batiment'] ?? 'inconnu',
                        'annee_construction' => $batimentData['annee_construction'] ?? null,
                        'nombre_logements' => $batimentData['nombre_logements'] ?? null,
                        'nombre_niveaux' => $batimentData['nombre_niveaux'] ?? null,
                        'hauteur' => $batimentData['hauteur'] ?? null,
                        'surface_habitable' => $batimentData['surface_habitable'] ?? null,
                        'surface_emprise_sol' => $batimentData['surface_emprise_sol'] ?? null,
                        'classe_dpe' => $batimentData['classe_dpe'] ?? null,
                        'ges' => $batimentData['ges'] ?? null,
                        'type_chauffage' => $batimentData['type_chauffage'] ?? null,
                        'energie_chauffage' => $batimentData['energie_chauffage'] ?? null,
                        'score_opportunite' => $this->calculateScore($batimentData),
                        'raw_data' => $batimentData['raw_data'] ?? $batimentData,
                    ]
                );

                $batiments[] = $batiment;
            }

            $coprosApi = $this->coproprieteApi->searchByAddress(
                $geo['adresse_complete'],
                $geo['code_postal'] ?? null,
                $geo['ville'] ?? null
            );

            $coproprietes = [];
            $syndics = [];

            foreach ($coprosApi as $rawCopro) {
                $coproData = $this->coproprieteApi->normalize($rawCopro);

                $coproUniqueKey = $coproData['numero_immatriculation']
                    ?? md5(json_encode($coproData['raw_data'] ?? $coproData));

                $copro = Copropriete::updateOrCreate(
                    [
                        'numero_immatriculation' => $coproUniqueKey,
                    ],
                    [
                        'adresse_id' => $adresse->id,
                        'batiment_id' => $batiments[0]->id ?? null,

                        'nom_copropriete' => $coproData['nom_copropriete'] ?? null,
                        'siren_copropriete' => $coproData['siren_copropriete'] ?? null,

                        'nombre_lots_total' => $coproData['nombre_lots_total'] ?? null,
                        'nombre_lots_habitation' => $coproData['nombre_lots_habitation'] ?? null,
                        'nombre_batiments' => $coproData['nombre_batiments'] ?? null,
                        'nombre_adresses_associees' => $coproData['nombre_adresses_associees'] ?? null,

                        'statut' => $coproData['statut'] ?? null,
                        'date_immatriculation' => $coproData['date_immatriculation'] ?? null,

                        'representant_legal_nom' => $coproData['representant_legal_nom'] ?? null,
                        'representant_legal_type' => $coproData['representant_legal_type'] ?? null,
                        'representant_legal_connu' => $coproData['representant_legal_connu'] ?? false,
                        'message_representant' => $coproData['message_representant'] ?? 'Pas de représentant légal connu',

                        'raw_data' => $coproData['raw_data'] ?? $coproData,
                    ]
                );

                $sirenSyndic = $coproData['siren_syndic'] ?? null;
                $siretSyndic = $coproData['siret_syndic'] ?? null;

                if ($sirenSyndic) {
                    $sirene = $this->sireneApi->searchBySiren($sirenSyndic);
                    $etablissements = $this->sireneApi->searchEtablissementsBySiren($sirenSyndic);
                    $pappers = $this->pappersApi->searchBySiren($sirenSyndic);

                    $syndic = Syndic::updateOrCreate(
                        [
                            'siren' => $sirenSyndic,
                        ],
                        [
                            'nom' => $pappers['nom']
                                ?? $sirene['nom']
                                ?? $coproData['syndic_nom']
                                ?? $coproData['representant_legal_nom']
                                ?? null,

                            'siret' => $siretSyndic ?? ($etablissements[0]['siret'] ?? null),
                            'forme_juridique' => $pappers['forme_juridique'] ?? $sirene['forme_juridique'] ?? null,
                            'activite' => $sirene['activite'] ?? null,

                            'adresse_complete' => $etablissements[0]['adresse_complete'] ?? null,
                            'code_postal' => $etablissements[0]['code_postal'] ?? null,
                            'ville' => $etablissements[0]['ville'] ?? null,

                            'raw_data' => [
                                'sirene' => $sirene,
                                'etablissements' => $etablissements,
                                'pappers' => $pappers,
                            ],
                        ]
                    );

                    $copro->syndics()->syncWithoutDetaching([
                        $syndic->id => [
                            'role' => 'representant_legal',
                            'date_debut' => null,
                            'date_fin' => null,
                        ],
                    ]);

                    $syndics[] = $syndic;
                }

                $coproprietes[] = $copro;
            }

            $statut = count($batiments) || count($coproprietes)
                ? 'trouve'
                : 'partiel';

            $message = $statut === 'trouve'
                ? 'Adresse enrichie avec les sources disponibles.'
                : 'Adresse trouvée, mais données bâtiment/copropriété encore incomplètes.';

            $recherche = Recherche::create([
                'user_id' => Auth::id(),
                'adresse_id' => $adresse->id,
                'requete' => $query,
                'statut' => $statut,
                'message' => $message,
                'resultat' => [
                    'adresse' => $geo,
                    'cadastre' => $cadastre,
                    'batiments' => collect($batiments)->map->toArray(),
                    'coproprietes' => collect($coproprietes)->map->toArray(),
                    'syndics' => collect($syndics)->map->toArray(),
                ],
            ]);

            return [
                'success' => true,
                'message' => $message,
                'recherche' => $recherche,
                'adresse' => $adresse->fresh([
                    'batiments.coproprietes.syndics',
                    'coproprietes.syndics',
                ]),
                'cadastre' => $cadastre,
                'batiments' => $batiments,
                'coproprietes' => $coproprietes,
                'syndics' => $syndics,
            ];
        });
    }

    private function calculateScore(array $batiment): float
    {
        $score = 0;

        if (($batiment['type_batiment'] ?? null) === 'collectif') {
            $score += 30;
        }

        if (($batiment['nombre_logements'] ?? 0) >= 10) {
            $score += 25;
        }

        if (($batiment['nombre_niveaux'] ?? 0) >= 3) {
            $score += 15;
        }

        if (!empty($batiment['annee_construction']) && $batiment['annee_construction'] < 1990) {
            $score += 20;
        }

        if (in_array($batiment['classe_dpe'] ?? null, ['E', 'F', 'G'], true)) {
            $score += 10;
        }

        return min($score, 100);
    }
}