<?php

namespace App\Services\Api;

use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Recherche;
use App\Models\Back\Syndic;
use Illuminate\Support\Facades\Auth;
use App\Models\Back\RneEntreprise;

class DataRocketEngineService
{
    public function __construct(
        protected AdresseApiService $adresseApi,
        protected BdnbApiService $bdnbApi,
        protected CadastreApiService $cadastreApi,
        protected CoproprieteApiService $coproprieteApi,
        protected SireneApiService $sireneApi,
        protected PappersApiService $pappersApi,
        protected QpvEligibilityService $qpvEligibilityService,
        protected RnbApiService $rnbApi,
    ) {}

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
                'qpv' => null,
                'rnb' => null,
            ];
        }

        $qpvChecks = $this->checkQpvForBanCandidates($geo);

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

        $rnb = $this->rnbApi->searchSmart([
            'address' => $geo['adresse_complete'] ?? $query,
            'latitude' => $geo['latitude'] ?? null,
            'longitude' => $geo['longitude'] ?? null,
            'plot_id' => $cadastre[0]['id_parcelle'] ?? null,
            'cle_interop_ban' => $geo['cle_interop_ban'] ?? null,
        ]);

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
        $proprietairesBdnb = $this->enrichProprietairesWithRne($proprietairesBdnb);
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

        $statut = count($batiments) || count($coproprietes) || !empty($rnb['batiments'])
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
                'adresse' => [
                    'adresse_complete' => $geo['adresse_complete'] ?? null,
                    'ville' => $geo['ville'] ?? null,
                    'code_postal' => $geo['code_postal'] ?? null,
                ],

                'resume' => [
                    'batiments' => count($batiments),
                    'coproprietes' => count($coproprietes),
                    'syndics' => count($syndics),
                    'proprietaires' => count($proprietairesBdnb),
                ],

                'eligibilite' => [
                    'eligible' => $qpvChecks['eligible'] ?? null,
                    'message' => $qpvChecks['message'] ?? null,
                ],
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
            'rnb' => $rnb,
            'batiments' => $batiments,
            'coproprietes' => $coproprietes,
            'syndics' => $syndics,
            'proprietaires_bdnb' => $proprietairesBdnb,
            'qpv' => $qpvChecks,
        ];
    }

    private function createOrUpdateSyndic(?string $sirenSyndic, ?string $siretSyndic, ?string $syndicNom, array $coproData): Syndic
    {
        $sirene = $sirenSyndic ? $this->sireneApi->searchBySiren($sirenSyndic) : null;
        $etablissements = $sirenSyndic ? $this->sireneApi->searchEtablissementsBySiren($sirenSyndic) : [];
        $rne = $sirenSyndic
            ? RneEntreprise::where('siren', $sirenSyndic)->first()
            : null;
        $uniqueKey = $sirenSyndic ?: ($siretSyndic ? substr($siretSyndic, 0, 9) : null);

        if (!$uniqueKey && $syndicNom) {
            $uniqueKey = substr(md5($syndicNom), 0, 9);
        }

        return Syndic::updateOrCreate(
            ['siren' => $uniqueKey],
            [
                'nom' => $rne?->denomination
                    ?? $sirene['nom']
                    ?? $syndicNom
                    ?? null,

                'siret' => $rne?->siret_siege
                    ?? $siretSyndic
                    ?? ($etablissements[0]['siret'] ?? null),

                'forme_juridique' => $rne?->forme_juridique
                    ?? $sirene['forme_juridique']
                    ?? null,

                'activite' => $rne?->activite
                    ?? $sirene['activite']
                    ?? null,

                'capital_social' => $rne?->capital_formatted
                    ?? $rne?->capital_social
                    ?? null,

                'chiffre_affaires' => null,
                'resultat' => null,
                'effectif' => null,

                'date_creation' => $rne?->date_creation
                    ?? null,

                'dirigeant_principal' => data_get($rne?->dirigeants, '0.nom')
                    ?? data_get($rne?->dirigeants, '0.prenoms')
                    ?? null,

                'url_pappers' => null,

                'adresse_complete' => $rne?->adresse_complete
                    ?? ($etablissements[0]['adresse_complete'] ?? null),

                'code_postal' => $rne?->code_postal
                    ?? ($etablissements[0]['code_postal'] ?? null),

                'ville' => $rne?->ville
                    ?? ($etablissements[0]['ville'] ?? null),

                'raw_data' => [
                    'rnic' => $coproData,
                    'sirene' => $sirene,
                    'etablissements' => $etablissements,
                    'rne' => $rne?->toArray(),
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

  private function enrichProprietairesWithRne(array $proprietaires): array
{
    return collect($proprietaires)
        ->map(function ($item) {
            $pappers = null;
            $rne = null;

            // Priorité 1 : Pappers (API directe)
            if (!empty($item['siren']) && strlen($item['siren']) === 9) {
                $pappers = $this->pappersApi->searchBySiren($item['siren']);
            }
            
            // Priorité 2 : RNE (si dispo en base)
            if (!empty($item['siren']) && strlen($item['siren']) === 9) {
                $rne = RneEntreprise::where('siren', $item['siren'])->first();
            }

            // Affichage du capital social depuis Pappers
            $capital = $pappers['capital_social'] ?? $rne?->capital_formatted ?? $rne?->capital_social ?? null;

            return [
                'nom' => $pappers['nom'] ?? $rne?->denomination ?? $item['nom'] ?? null,
                'nom_bdnb' => $item['nom'] ?? null,
                'siren' => $item['siren'] ?? null,
                'siret' => $pappers['siret'] ?? $rne?->siret_siege ?? null,
                'forme_juridique' => $pappers['forme_juridique'] ?? $rne?->forme_juridique ?? null,
                'activite' => $pappers['activite'] ?? $rne?->activite ?? null,
                'capital_social' => $capital, // ✅ Capital depuis Pappers
                'chiffre_affaires' => $pappers['chiffre_affaires'] ?? null,
                'resultat' => $pappers['resultat'] ?? null,
                'effectif' => $pappers['effectif'] ?? null,
                'date_creation' => $pappers['date_creation'] ?? optional($rne?->date_creation)->format('Y-m-d'),
                'dirigeant_principal' => $pappers['dirigeant_principal'] ?? data_get($rne?->dirigeants, '0.nom'),
                'url_pappers' => $pappers['url_pappers'] ?? null,
                'raw_data' => $pappers['raw_data'] ?? $rne?->raw_data,
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

        if ($type === 'collectif') {
            $score += 50;
        }

        if ($type === 'individuel') {
            $score += 30;
        }

        if ($type === 'inconnu') {
            $score -= 20;
        }

        if ($logements > 0) {
            $score += min($logements, 50);
        }

        if ($niveaux > 0) {
            $score += $niveaux * 5;
        }

        if ($hauteur > 3) {
            $score += (int) $hauteur;
        }

        if ($surface > 0) {
            $score += min((int) ($surface / 20), 30);
        }

        if ($annee) {
            $score += 10;
        }

        return $score;
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

    private function checkQpvForBanCandidates(array $geo): array
    {
        $candidates = $geo['ban_candidates'] ?? [];

        if (empty($candidates)) {
            $candidates = [[
                'adresse' => $geo['adresse_complete'] ?? null,
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
                'score' => null,
                'source' => 'BAN',
            ]];
        }

        $checks = [];

        foreach ($candidates as $candidate) {
            $check = $this->qpvEligibilityService->check(
                isset($candidate['latitude']) ? (float) $candidate['latitude'] : null,
                isset($candidate['longitude']) ? (float) $candidate['longitude'] : null
            );

            $checks[] = [
                'candidate' => $candidate,
                'result' => $check,
            ];
        }

        $hasZone = collect($checks)->contains(function ($item) {
            return ($item['result']['qp_2024'] ?? false)
                || ($item['result']['qp_2015'] ?? false)
                || ($item['result']['zfu'] ?? false);
        });

        return [
            'eligible' => !$hasZone,
            'message' => $hasZone
                ? 'Adresse non éligible : au moins un point BAN est situé en QPV/ZFU.'
                : 'Adresse éligible : aucun des points BAN testés n’est en QPV/ZFU.',
            'strategy' => 'multi_points_ban',
            'candidates_tested' => count($checks),
            'checks' => $checks,
        ];
    }
}
