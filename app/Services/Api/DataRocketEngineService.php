<?php

namespace App\Services\Api;

use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Recherche;
use App\Models\Back\Syndic;
use App\Models\Back\RneEntreprise;
use Illuminate\Support\Facades\Auth;

class DataRocketEngineService
{
    // ─────────────────────────────────────────────────────────────
    // CONSTRUCTEUR
    // ─────────────────────────────────────────────────────────────
    public function __construct(
        protected AdresseApiService      $adresseApi,
        protected BdnbApiService         $bdnbApi,
        protected CadastreApiService     $cadastreApi,
        protected CoproprieteApiService  $coproprieteApi,
        protected QpvEligibilityService  $qpvEligibilityService,
        protected RnbApiService          $rnbApi,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // POINT D'ENTRÉE PRINCIPAL
    // ─────────────────────────────────────────────────────────────
    public function searchByAddress(string $query, ?int $userId = null): array
    {
        set_time_limit(120);
        ini_set('max_execution_time', 120);

        // ════════════════════════════════════════════════════════
        // 1. CACHE BASE — Adresse déjà connue ?
        // ════════════════════════════════════════════════════════
        $hashQuery    = Adresse::makeHash($query);
        $adresseCache = Adresse::where('adresse_hash', $hashQuery)
            ->with(['batiments', 'coproprietes.syndics'])
            ->first();

        if ($adresseCache && $adresseCache->batiments->isNotEmpty()) {
           return $this->buildResultFromCache($adresseCache, $query, $userId);
        }

        // ════════════════════════════════════════════════════════
        // 2. GÉOCODAGE BAN
        // ════════════════════════════════════════════════════════
        $geo = $this->adresseApi->search($query);

        if (!$geo) {
            $recherche = Recherche::create([
                'user_id' => $userId ?? Auth::id(),
                'requete' => $query,
                'statut'  => 'introuvable',
                'message' => 'Adresse introuvable au géocodage.',
                'resultat'=> null,
            ]);

            return [
                'success'           => false,
                'message'           => 'Adresse introuvable.',
                'recherche'         => $recherche,
                'adresse'           => null,
                'cadastre'          => [],
                'batiments'         => [],
                'coproprietes'      => [],
                'syndics'           => [],
                'proprietaires_bdnb'=> [],
                'qpv'               => null,
                'rnb'               => null,
            ];
        }

        // ════════════════════════════════════════════════════════
        // 3. QPV / ZFU — SIG Ville WSA API (Basic Auth)
        //    Remplace l'ancien ray casting local
        // ════════════════════════════════════════════════════════
        $qpvChecks = $this->checkQpvViaSigVille($geo);

        // ════════════════════════════════════════════════════════
        // 4. UPSERT ADRESSE
        // ════════════════════════════════════════════════════════
        $adresseComplete = $geo['adresse_complete'];
        $adresseHash     = Adresse::makeHash($adresseComplete);

        $adresse = Adresse::updateOrCreate(
            ['adresse_hash' => $adresseHash],
            [
                'adresse_complete' => $adresseComplete,
                'numero'           => $geo['numero']      ?? null,
                'voie'             => $geo['voie']        ?? null,
                'code_postal'      => $geo['code_postal'] ?? null,
                'ville'            => $geo['ville']       ?? null,
                'code_insee'       => $geo['code_insee']  ?? null,
                'latitude'         => $geo['latitude']    ?? null,
                'longitude'        => $geo['longitude']   ?? null,
                'source'           => $geo['source']      ?? 'geocodage',
                'raw_data'         => $geo['raw_data']    ?? $geo,
            ]
        );

        // ════════════════════════════════════════════════════════
        // 5. CADASTRE
        // ════════════════════════════════════════════════════════
        $cadastre = $this->cadastreApi->searchByCoordinates(
            $geo['latitude']   ?? null,
            $geo['longitude']  ?? null,
            $geo['code_insee'] ?? null
        );

        // ════════════════════════════════════════════════════════
        // 6. RNB — Référentiel National des Bâtiments
        // ════════════════════════════════════════════════════════
        $rnb = $this->rnbApi->searchSmart([
            'address'         => $adresseComplete,
            'latitude'        => $geo['latitude']              ?? null,
            'longitude'       => $geo['longitude']             ?? null,
            'plot_id'         => $cadastre[0]['id_parcelle']   ?? null,
            'cle_interop_ban' => $geo['cle_interop_ban']       ?? null,
        ]);

        // ════════════════════════════════════════════════════════
        // 7. BDNB — Bâtiments
        // ════════════════════════════════════════════════════════
        $batimentsApi = [];
        $parcelleId   = $cadastre[0]['id_parcelle'] ?? null;

        if ($parcelleId) {
            $batimentsApi = $this->bdnbApi->searchByParcelle($parcelleId);
        }
        if (empty($batimentsApi)) {
            $batimentsApi = $this->bdnbApi->searchByAddress($adresseComplete);
        }

        $batimentsApi = $this->selectMainBuildings($batimentsApi);

        $batiments = [];
        foreach ($batimentsApi as $batimentData) {
            $uniqueKey = $batimentData['identifiant_bdnb']
                ?? md5(json_encode($batimentData['raw_data'] ?? $batimentData));

            $batiments[] = Batiment::updateOrCreate(
                [
                    'adresse_id'       => $adresse->id,
                    'identifiant_bdnb' => $uniqueKey,
                ],
                [
                    'identifiant_cadastre' => $batimentData['identifiant_cadastre'] ?? null,
                    'type_batiment'        => $batimentData['type_batiment']        ?? 'inconnu',
                    'annee_construction'   => $batimentData['annee_construction']   ?? null,
                    'nombre_logements'     => $batimentData['nombre_logements']     ?? null,
                    'nombre_niveaux'       => $batimentData['nombre_niveaux']       ?? null,
                    'hauteur'              => $batimentData['hauteur']              ?? null,
                    'surface_habitable'    => $batimentData['surface_habitable']    ?? null,
                    'surface_emprise_sol'  => $batimentData['surface_emprise_sol']  ?? null,
                    'classe_dpe'           => $batimentData['classe_dpe']           ?? null,
                    'ges'                  => $batimentData['ges']                  ?? null,
                    'type_chauffage'       => $batimentData['type_chauffage']       ?? null,
                    'energie_chauffage'    => $batimentData['energie_chauffage']    ?? null,
                    'score_opportunite'    => $this->calculateScore($batimentData),
                    'raw_data'             => $batimentData['raw_data']             ?? $batimentData,
                ]
            );
        }

        // ════════════════════════════════════════════════════════
        // 8. PROPRIÉTAIRES BDNB — enrichissement RNE local
        // ════════════════════════════════════════════════════════
        $proprietairesBdnb = $this->extractProprietairesFromBatiments($batiments);
        $proprietairesBdnb = $this->enrichProprietairesLocal($proprietairesBdnb);

        // ════════════════════════════════════════════════════════
        // 9. COPROPRIÉTÉS RNIC
        // ════════════════════════════════════════════════════════
        $coprosApi = $this->coproprieteApi->searchByAddress(
            $query,
            $geo['code_postal'] ?? null,
            $geo['ville']       ?? null
        );

        if (empty($coprosApi)) {
            $coprosApi = $this->coproprieteApi->searchByAddress(
                $adresseComplete,
                $geo['code_postal'] ?? null,
                $geo['ville']       ?? null
            );
        }

        $coproprietes = [];
        $syndics      = [];

        foreach ($coprosApi as $rawCopro) {
            $coproData = $this->coproprieteApi->normalize($rawCopro);

            $coproUniqueKey = $coproData['numero_immatriculation']
                ?? md5(json_encode($coproData['raw_data'] ?? $coproData));

            $copro = Copropriete::updateOrCreate(
                ['numero_immatriculation' => $coproUniqueKey],
                [
                    'adresse_id'               => $adresse->id,
                    'batiment_id'              => $batiments[0]->id              ?? null,
                    'nom_copropriete'          => $coproData['nom_copropriete']          ?? null,
                    'siren_copropriete'        => $coproData['siren_copropriete']        ?? null,
                    'nombre_lots_total'        => $coproData['nombre_lots_total']        ?? null,
                    'nombre_lots_habitation'   => $coproData['nombre_lots_habitation']   ?? null,
                    'nombre_batiments'         => $coproData['nombre_batiments']         ?? null,
                    'nombre_adresses_associees'=> $coproData['nombre_adresses_associees']?? null,
                    'statut'                   => $coproData['statut']                   ?? null,
                    'date_immatriculation'     => $coproData['date_immatriculation']     ?? null,
                    'representant_legal_nom'   => $coproData['representant_legal_nom']   ?? null,
                    'representant_legal_type'  => $coproData['representant_legal_type']  ?? null,
                    'representant_legal_connu' => $coproData['representant_legal_connu'] ?? false,
                    'message_representant'     => $coproData['message_representant']
                                               ?? 'Pas de représentant légal connu',
                    'raw_data'                 => $coproData['raw_data']                 ?? $coproData,
                ]
            );

            // ── Syndic ─────────────────────────────────────────
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
                        'representant_legal_nom'   => $nomRepresentantFinal,
                        'representant_legal_type'  => $coproData['representant_legal_type']
                                                   ?? 'syndic professionnel',
                        'message_representant'     => null,
                    ]);
                }

                $copro->syndics()->syncWithoutDetaching([
                    $syndic->id => [
                        'role'       => 'representant_legal',
                        'date_debut' => null,
                        'date_fin'   => null,
                    ],
                ]);

                $syndics[] = $syndic;
            }

            $coproprietes[] = $copro;
        }

        $syndics = collect($syndics)
            ->filter()
            ->unique(fn($s) => $s->siret ?: $s->siren ?: $s->nom)
            ->values()
            ->all();

        // ════════════════════════════════════════════════════════
        // 10. SAUVEGARDE RECHERCHE
        // ════════════════════════════════════════════════════════
        $statut = count($batiments) || count($coproprietes) || !empty($rnb['batiments'])
            ? 'trouve'
            : 'partiel';

        $message = $statut === 'trouve'
            ? 'Adresse enrichie avec les sources disponibles.'
            : 'Adresse trouvée, mais données bâtiment/copropriété encore incomplètes.';

        $recherche = Recherche::create([
            'user_id' => $userId ?? Auth::id(),
            'adresse_id' => $adresse->id,
            'requete'    => $query,
            'statut'     => $statut,
            'message'    => $message,
            'resultat'   => [
                'adresse' => [
                    'adresse_complete' => $adresseComplete,
                    'ville'            => $geo['ville']       ?? null,
                    'code_postal'      => $geo['code_postal'] ?? null,
                ],
                'resume' => [
                    'batiments'     => count($batiments),
                    'coproprietes'  => count($coproprietes),
                    'syndics'       => count($syndics),
                    'proprietaires' => count($proprietairesBdnb),
                ],
                'eligibilite' => [
                    'eligible' => $qpvChecks['eligible'] ?? null,
                    'message'  => $qpvChecks['message']  ?? null,
                ],
            ],
        ]);

        return [
            'success'           => true,
            'message'           => $message,
            'recherche'         => $recherche,
            'adresse'           => $adresse->fresh([
                'batiments.coproprietes.syndics',
                'coproprietes.syndics',
            ]),
            'cadastre'          => $cadastre,
            'rnb'               => $rnb,
            'batiments'         => $batiments,
            'coproprietes'      => $coproprietes,
            'syndics'           => $syndics,
            'proprietaires_bdnb'=> $proprietairesBdnb,
            'qpv'               => $qpvChecks,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // CACHE — Retour rapide depuis la base
    // ─────────────────────────────────────────────────────────────
    private function buildResultFromCache(Adresse $adresse, string $query, ?int $userId = null): array
    {
        $coproprietes = $adresse->coproprietes;
        $syndics      = $coproprietes
            ->flatMap(fn($c) => $c->syndics)
            ->filter()
            ->unique('id')
            ->values();

        $recherche = Recherche::create([
            'user_id'    => $userId ?? Auth::id(),
            'adresse_id' => $adresse->id,
            'requete'    => $query,
            'statut'     => 'trouve',
            'message'    => 'Adresse servie depuis le cache base de données.',
            'resultat'   => [
                'adresse' => [
                    'adresse_complete' => $adresse->adresse_complete,
                    'ville'            => $adresse->ville,
                    'code_postal'      => $adresse->code_postal,
                ],
                'resume' => [
                    'batiments'     => $adresse->batiments->count(),
                    'coproprietes'  => $coproprietes->count(),
                    'syndics'       => $syndics->count(),
                    'proprietaires' => 0,
                ],
                'cache' => true,
            ],
        ]);

        return [
            'success'           => true,
            'message'           => 'Adresse servie depuis le cache.',
            'recherche'         => $recherche,
            'adresse'           => $adresse,
            'cadastre'          => [],
            'rnb'               => null,
            'batiments'         => $adresse->batiments->all(),
            'coproprietes'      => $coproprietes->all(),
            'syndics'           => $syndics->all(),
            'proprietaires_bdnb'=> [],
            'qpv'               => null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // QPV / ZFU — SIG Ville WSA API (remplace l'ancien ray casting)
    //
    // Stratégie :
    //   1. Appel principal avec coordonnées GPS (BAN → /api/xy.json)
    //   2. Si plusieurs candidats BAN disponibles, on les teste aussi
    //   3. Format de sortie identique à l'ancien : checks[] attendu
    //      par result.blade.php (aucun changement côté vue)
    // ─────────────────────────────────────────────────────────────
    private function checkQpvViaSigVille(array $geo): array
    {
        // Candidats BAN (multi-points si disponibles, sinon point unique)
        $candidates = $geo['ban_candidates'] ?? [];

        if (empty($candidates)) {
            $candidates = [[
                'adresse'   => $geo['adresse_complete'] ?? null,
                'latitude'  => $geo['latitude']         ?? null,
                'longitude' => $geo['longitude']        ?? null,
                'score'     => null,
                'source'    => 'BAN',
            ]];
        }

        $checks = [];

        foreach ($candidates as $candidate) {
            // ── Appel SIG Ville WSA — coordonnées en priorité ──
            // Si pas de coords sur ce candidat, on passe l'adresse texte
            $apiResult = $this->qpvEligibilityService->check(
                lat        : isset($candidate['latitude'])  ? (float) $candidate['latitude']  : null,
                lng        : isset($candidate['longitude']) ? (float) $candidate['longitude'] : null,
                adresse    : $geo['adresse_complete'] ?? '',
                commune    : $geo['ville']            ?? '',
                codePostal : $geo['code_postal']      ?? '',
            );

            // Transformer matches API → format attendu par la blade
            // blade lit : $check['result']['matches']['qp_2024']['found']
            //                                                    ['nom']
            //                                                    ['code']
            $matchesBlade = [];
            foreach (['qp_2024', 'qp_2015', 'zfu'] as $type) {
                $m = $apiResult['matches'][$type] ?? null;
                $matchesBlade[$type] = [
                    'found'     => $apiResult[$type],
                    'code'      => $m['code'] ?? null,
                    'nom'       => $m['nom']  ?? null,
                    'bande_300' => $m['bande_300'] ?? false,
                    'bande_500' => $m['bande_500'] ?? false,
                ];
            }

            $checks[] = [
                'candidate' => [
                    'adresse'   => $candidate['adresse']   ?? $geo['adresse_complete'] ?? null,
                    'score'     => $candidate['score']     ?? $apiResult['similitude'] ?? null,
                    'latitude'  => $candidate['latitude']  ?? null,
                    'longitude' => $candidate['longitude'] ?? null,
                    'source'    => $candidate['source']    ?? 'BAN',
                    // Infos SIG Ville pour debug
                    'loccom_ref'  => $apiResult['loccom_ref']  ?? null,
                    'locvoie_ref' => $apiResult['locvoie_ref'] ?? null,
                ],
                'result' => [
                    'qp_2024' => $apiResult['qp_2024'],
                    'qp_2015' => $apiResult['qp_2015'],
                    'zfu'     => $apiResult['zfu'],
                    'matches' => $matchesBlade,
                ],
            ];
        }

        $hasZone = collect($checks)->contains(fn($item) =>
            ($item['result']['qp_2024'] ?? false)
            || ($item['result']['qp_2015'] ?? false)
            || ($item['result']['zfu']    ?? false)
        );

        return [
            'eligible'          => !$hasZone,
            'message'           => $hasZone
                ? 'Adresse non éligible : située en QPV/ZFU selon SIG Ville.'
                : 'Adresse éligible : aucun point BAN en QPV/ZFU.',
            'strategy'          => 'sigville_wsa_api',
            'candidates_tested' => count($checks),
            'checks'            => $checks,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // ENRICHISSEMENT PROPRIÉTAIRES — RNE LOCAL
    // ─────────────────────────────────────────────────────────────
    private function enrichProprietairesLocal(array $proprietaires): array
    {
        if (empty($proprietaires)) return [];

        $sirens = collect($proprietaires)
            ->pluck('siren')
            ->filter(fn($s) => $s && strlen(preg_replace('/\D/', '', (string) $s)) === 9)
            ->map(fn($s) => preg_replace('/\D/', '', $s))
            ->unique()
            ->values()
            ->all();

        $rneMap = !empty($sirens)
            ? RneEntreprise::whereIn('siren', $sirens)->get()->keyBy('siren')
            : collect();

        return collect($proprietaires)
            ->map(function (array $item) use ($rneMap) {
                $siren = preg_replace('/\D/', '', $item['siren'] ?? '');
                $rne   = $siren ? $rneMap->get($siren) : null;

                return [
                    'nom'                 => $rne?->denomination      ?? $item['nom'] ?? null,
                    'nom_bdnb'            => $item['nom']             ?? null,
                    'siren'               => $siren                   ?: null,
                    'siret'               => $rne?->siret_siege       ?? null,
                    'capital_social'      => $rne?->capital_formatted ?? $rne?->capital_social ?? null,
                    'forme_juridique'     => $rne?->forme_juridique   ?? null,
                    'activite'            => $rne?->activite          ?? null,
                    'chiffre_affaires'    => null,
                    'resultat'            => null,
                    'effectif'            => null,
                    'date_creation'       => optional($rne?->date_creation)->format('Y-m-d'),
                    'dirigeant_principal' => data_get($rne?->dirigeants, '0.nom')
                                         ?? data_get($rne?->dirigeants, '0.prenoms')
                                         ?? null,
                    'url_pappers'         => $siren
                        ? "https://www.pappers.fr/entreprise/{$siren}"
                        : null,
                    'source'              => $rne ? 'rne_local' : 'bdnb_only',
                    'raw_data'            => $rne?->raw_data,
                ];
            })
            ->values()
            ->all();
    }

    // ─────────────────────────────────────────────────────────────
    // SYNDIC — RNE LOCAL
    // ─────────────────────────────────────────────────────────────
    private function createOrUpdateSyndic(
        ?string $sirenSyndic,
        ?string $siretSyndic,
        ?string $syndicNom,
        array   $coproData
    ): Syndic {
        $rne = $sirenSyndic
            ? RneEntreprise::where('siren', $sirenSyndic)->first()
            : null;

        $uniqueKey = $sirenSyndic
            ?: ($siretSyndic ? substr($siretSyndic, 0, 9) : null)
            ?: ($syndicNom   ? substr(md5($syndicNom), 0, 9) : null);

        return Syndic::updateOrCreate(
            ['siren' => $uniqueKey],
            [
                'nom'                 => $rne?->denomination      ?? $syndicNom   ?? null,
                'siret'               => $rne?->siret_siege       ?? $siretSyndic ?? null,
                'forme_juridique'     => $rne?->forme_juridique   ?? null,
                'activite'            => $rne?->activite          ?? null,
                'capital_social'      => $rne?->capital_formatted ?? $rne?->capital_social ?? null,
                'chiffre_affaires'    => null,
                'resultat'            => null,
                'effectif'            => null,
                'date_creation'       => $rne?->date_creation     ?? null,
                'dirigeant_principal' => data_get($rne?->dirigeants, '0.nom')
                                      ?? data_get($rne?->dirigeants, '0.prenoms')
                                      ?? null,
                'url_pappers'         => $sirenSyndic
                    ? "https://www.pappers.fr/entreprise/{$sirenSyndic}"
                    : null,
                'adresse_complete'    => $rne?->adresse_complete ?? null,
                'code_postal'         => $rne?->code_postal      ?? null,
                'ville'               => $rne?->ville            ?? null,
                'raw_data'            => [
                    'rnic' => $coproData,
                    'rne'  => $rne?->toArray(),
                ],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────
    // EXTRACTION PROPRIÉTAIRES depuis données BDNB brutes
    // ─────────────────────────────────────────────────────────────
    private function extractProprietairesFromBatiments(array $batiments): array
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
            $sirens        = $raw['l_siren']                     ?? [];

            if (!is_array($denominations)) $denominations = [$denominations];
            if (!is_array($sirens))        $sirens        = [$sirens];

            foreach ($denominations as $index => $nom) {
                $nom   = trim((string) $nom);
                $siren = $sirens[$index] ?? null;
                $siren = $siren ? preg_replace('/\D/', '', $siren) : null;

                if (!$nom && !$siren) continue;

                $items[] = ['nom' => $nom ?: null, 'siren' => $siren ?: null];
            }
        }

        return collect($items)
            ->unique(fn($item) => ($item['siren'] ?? '') . '|' . ($item['nom'] ?? ''))
            ->values()
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // SÉLECTION BÂTIMENT PRINCIPAL
    // ─────────────────────────────────────────────────────────────
    private function selectMainBuildings(array $batiments): array
    {
        if (empty($batiments)) return [];

        $best = collect($batiments)
            ->map(fn($b) => ['score' => $this->mainBuildingScore($b), 'data' => $b])
            ->sortByDesc('score')
            ->first();

        return $best ? [$best['data']] : [];
    }

    private function mainBuildingScore(array $batiment): int
    {
        $score     = 0;
        $type      = $batiment['type_batiment']     ?? 'inconnu';
        $logements = (int)   ($batiment['nombre_logements']    ?? 0);
        $niveaux   = (int)   ($batiment['nombre_niveaux']      ?? 0);
        $hauteur   = (float) ($batiment['hauteur']             ?? 0);
        $surface   = (float) ($batiment['surface_emprise_sol'] ?? 0);

        if ($type === 'collectif')  $score += 50;
        if ($type === 'individuel') $score += 30;
        if ($type === 'inconnu')    $score -= 20;
        if ($logements > 0)         $score += min($logements, 50);
        if ($niveaux > 0)           $score += $niveaux * 5;
        if ($hauteur > 3)           $score += (int) $hauteur;
        if ($surface > 0)           $score += min((int) ($surface / 20), 30);
        if ($batiment['annee_construction'] ?? null) $score += 10;

        return $score;
    }

    private function calculateScore(array $batiment): float
    {
        $score = 0;
        if (($batiment['type_batiment']    ?? null) === 'collectif')       $score += 30;
        if (($batiment['nombre_logements'] ?? 0) >= 10)                    $score += 25;
        if (($batiment['nombre_niveaux']   ?? 0) >= 3)                     $score += 15;
        if (!empty($batiment['annee_construction'])
            && $batiment['annee_construction'] < 1990)                     $score += 20;
        if (in_array($batiment['classe_dpe'] ?? null, ['E','F','G'], true)) $score += 10;
        return min($score, 100);
    }
}
