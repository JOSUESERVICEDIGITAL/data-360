<?php

namespace App\Services\Api;

use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Recherche;
use App\Models\Back\Syndic;
use Illuminate\Support\Facades\Auth;

class DataRocketEngineService
{
    public function __construct(
        protected AdresseApiService $adresseApi,
        protected BdnbApiService $bdnbApi,
        protected CadastreApiService $cadastreApi,
        protected CoproprieteApiService $coproprieteApi,
        protected SireneApiService $sireneApi,
        protected PappersApiService $pappersApi,
    ) {
    }

    public function searchByAddress(string $query): array
    {
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
                'proprietaires_bdnb' => [],
            ];
        }

        $adresse = Adresse::updateOrCreate(
            ['adresse_complete' => $geo['adresse_complete']],
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

        $batimentsApi = $this->selectMainBuildings($batimentsApi);

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

        $proprietairesBdnb = $this->extractProprietairesFromBatiments($batiments);
        $proprietairesBdnb = $this->enrichProprietairesWithPappers($proprietairesBdnb);

        $coprosApi = $this->coproprieteApi->searchByAddress(
            $query,
            $geo['code_postal'] ?? null,
            $geo['ville'] ?? null
        );

        if (empty($coprosApi)) {
            $coprosApi = $this->coproprieteApi->searchByAddress(
                $geo['adresse_complete'],
                $geo['code_postal'] ?? null,
                $geo['ville'] ?? null
            );
        }

        $coproprietes = [];
        $syndics = [];

        foreach ($coprosApi as $rawCopro) {
            $coproData = $this->coproprieteApi->normalize($rawCopro);

            $coproUniqueKey = $coproData['numero_immatriculation']
                ?? md5(json_encode($coproData['raw_data'] ?? $coproData));

            $copro = Copropriete::updateOrCreate(
                ['numero_immatriculation' => $coproUniqueKey],
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

            $sirenSyndic = preg_replace('/\D/', '', $coproData['siren_syndic'] ?? '');
            $siretSyndic = preg_replace('/\D/', '', $coproData['siret_syndic'] ?? '');

            if (!$sirenSyndic && strlen($siretSyndic) === 14) {
                $sirenSyndic = substr($siretSyndic, 0, 9);
            }

            $syndicNom = $coproData['syndic_nom']
                ?? $coproData['representant_legal_nom']
                ?? null;

            if ($sirenSyndic || $siretSyndic || $syndicNom) {
                $syndic = $this->createOrUpdateSyndic(
                    $sirenSyndic,
                    $siretSyndic,
                    $syndicNom,
                    $coproData
                );

                $nomRepresentantFinal = $syndic->nom
                    ?? $syndicNom
                    ?? $coproData['representant_legal_nom']
                    ?? null;

                if ($nomRepresentantFinal || $sirenSyndic || $siretSyndic) {
                    $copro->update([
                        'representant_legal_connu' => true,
                        'representant_legal_nom' => $nomRepresentantFinal,
                        'representant_legal_type' => $coproData['representant_legal_type'] ?? 'syndic professionnel',
                        'message_representant' => null,
                    ]);
                }

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

        $syndics = collect($syndics)
            ->filter()
            ->unique(fn($syndic) => $syndic->siret ?: $syndic->siren ?: $syndic->nom)
            ->values()
            ->all();

        $statut = count($batiments) || count($coproprietes) ? 'trouve' : 'partiel';

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
                'proprietaires_bdnb' => $proprietairesBdnb,
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
            'proprietaires_bdnb' => $proprietairesBdnb,
        ];
    }

    private function createOrUpdateSyndic(?string $sirenSyndic, ?string $siretSyndic, ?string $syndicNom, array $coproData): Syndic
    {
        $sirene = $sirenSyndic ? $this->sireneApi->searchBySiren($sirenSyndic) : null;
        $etablissements = $sirenSyndic ? $this->sireneApi->searchEtablissementsBySiren($sirenSyndic) : [];
        $pappers = $sirenSyndic ? $this->pappersApi->searchBySiren($sirenSyndic) : null;

        $uniqueKey = $sirenSyndic ?: ($siretSyndic ? substr($siretSyndic, 0, 9) : null);

        if (!$uniqueKey && $syndicNom) {
            $uniqueKey = substr(md5($syndicNom), 0, 9);
        }

        return Syndic::updateOrCreate(
            ['siren' => $uniqueKey],
            [
                'nom' => $pappers['nom']
                    ?? $sirene['nom']
                    ?? $syndicNom
                    ?? null,

                'siret' => $pappers['siret']
                    ?? $siretSyndic
                    ?? ($etablissements[0]['siret'] ?? null),

                'forme_juridique' => $pappers['forme_juridique']
                    ?? $sirene['forme_juridique']
                    ?? null,

                'activite' => $pappers['activite']
                    ?? $sirene['activite']
                    ?? null,

                'capital_social' => $pappers['capital_social'] ?? null,
                'chiffre_affaires' => $pappers['chiffre_affaires'] ?? null,
                'resultat' => $pappers['resultat'] ?? null,
                'effectif' => $pappers['effectif'] ?? null,
                'date_creation' => $pappers['date_creation'] ?? null,
                'dirigeant_principal' => $pappers['dirigeant_principal'] ?? null,
                'url_pappers' => $pappers['url_pappers'] ?? null,

                'adresse_complete' => $pappers['adresse_complete']
                    ?? ($etablissements[0]['adresse_complete'] ?? null),

                'code_postal' => $pappers['code_postal']
                    ?? ($etablissements[0]['code_postal'] ?? null),

                'ville' => $pappers['ville']
                    ?? ($etablissements[0]['ville'] ?? null),

                'raw_data' => [
                    'rnic' => $coproData,
                    'sirene' => $sirene,
                    'etablissements' => $etablissements,
                    'pappers' => $pappers,
                ],
            ]
        );
    }

    private function extractProprietairesFromBatiments($batiments): array
    {
        $items = [];

        foreach ($batiments as $batiment) {
            $raw = is_array($batiment)
                ? ($batiment['raw_data'] ?? $batiment)
                : ($batiment->raw_data ?? []);

            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }

            $denominations = $raw['l_denomination_proprietaire'] ?? [];
            $sirens = $raw['l_siren'] ?? [];

            if (!is_array($denominations)) {
                $denominations = [$denominations];
            }

            if (!is_array($sirens)) {
                $sirens = [$sirens];
            }

            foreach ($denominations as $index => $nom) {
                $nom = trim((string) $nom);
                $siren = $sirens[$index] ?? null;
                $siren = $siren ? preg_replace('/\D/', '', $siren) : null;

                if (!$nom && !$siren) {
                    continue;
                }

                $items[] = [
                    'nom' => $nom ?: null,
                    'siren' => $siren ?: null,
                ];
            }
        }

        return collect($items)
            ->unique(fn($item) => ($item['siren'] ?? '') . '|' . ($item['nom'] ?? ''))
            ->values()
            ->toArray();
    }

    private function enrichProprietairesWithPappers(array $proprietaires): array
    {
        return collect($proprietaires)
            ->map(function ($item) {
                $pappers = null;

                if (!empty($item['siren']) && strlen($item['siren']) === 9) {
                    $pappers = $this->pappersApi->searchBySiren($item['siren']);
                }

                return [
                    'nom' => $pappers['nom'] ?? $item['nom'] ?? null,
                    'nom_bdnb' => $item['nom'] ?? null,
                    'siren' => $item['siren'] ?? null,
                    'siret' => $pappers['siret'] ?? null,
                    'forme_juridique' => $pappers['forme_juridique'] ?? null,
                    'activite' => $pappers['activite'] ?? null,
                    'capital_social' => $pappers['capital_social'] ?? null,
                    'chiffre_affaires' => $pappers['chiffre_affaires'] ?? null,
                    'resultat' => $pappers['resultat'] ?? null,
                    'effectif' => $pappers['effectif'] ?? null,
                    'date_creation' => $pappers['date_creation'] ?? null,
                    'dirigeant_principal' => $pappers['dirigeant_principal'] ?? null,
                    'url_pappers' => $pappers['url_pappers'] ?? null,
                    'raw_data' => $pappers['raw_data'] ?? null,
                ];
            })
            ->values()
            ->toArray();
    }

    private function selectMainBuildings(array $batiments): array
    {
        if (empty($batiments)) {
            return [];
        }

        $scored = collect($batiments)
            ->map(fn($batiment) => [
                'score' => $this->mainBuildingScore($batiment),
                'data' => $batiment,
            ])
            ->sortByDesc('score')
            ->values();

        $best = $scored->first();

        return $best ? [$best['data']] : [];
    }

    private function mainBuildingScore(array $batiment): int
    {
        $score = 0;

        $type = $batiment['type_batiment'] ?? 'inconnu';
        $logements = (int) ($batiment['nombre_logements'] ?? 0);
        $niveaux = (int) ($batiment['nombre_niveaux'] ?? 0);
        $hauteur = (float) ($batiment['hauteur'] ?? 0);
        $surface = (float) ($batiment['surface_emprise_sol'] ?? 0);
        $annee = $batiment['annee_construction'] ?? null;

        if ($type === 'collectif')
            $score += 50;
        if ($type === 'individuel')
            $score += 30;
        if ($type === 'inconnu')
            $score -= 20;

        if ($logements > 0)
            $score += min($logements, 50);
        if ($niveaux > 0)
            $score += $niveaux * 5;
        if ($hauteur > 3)
            $score += (int) $hauteur;
        if ($surface > 0)
            $score += min((int) ($surface / 20), 30);
        if ($annee)
            $score += 10;

        return $score;
    }

    private function calculateScore(array $batiment): float
    {
        $score = 0;

        if (($batiment['type_batiment'] ?? null) === 'collectif')
            $score += 30;
        if (($batiment['nombre_logements'] ?? 0) >= 10)
            $score += 25;
        if (($batiment['nombre_niveaux'] ?? 0) >= 3)
            $score += 15;
        if (!empty($batiment['annee_construction']) && $batiment['annee_construction'] < 1990)
            $score += 20;
        if (in_array($batiment['classe_dpe'] ?? null, ['E', 'F', 'G'], true))
            $score += 10;

        return min($score, 100);
    }
}