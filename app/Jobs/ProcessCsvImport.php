<?php

namespace App\Jobs;

use App\Models\Back\CsvImport;
use App\Models\Back\Recherche;
use App\Services\Api\DataRocketEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout       = 7200;
    public int $tries         = 1;
    public int $maxExceptions = 1;

    // ════════════════════════════════════════════════════════════
    // COLONNES ENRICHIES
    // ════════════════════════════════════════════════════════════
    private array $enrichedCols = [
        // Historique (violet — 1ère colonne)
        'historique_statut',
        'historique_nb_fois',
        'historique_derniere',

        // Représentant légal (même logique que blade)
        'representant_legal',
        'nom_representant',
        'type_representant',
        'siren_syndic',
        'siret_syndic',
        'immatriculation_copro',
        'nom_residence',
        'nb_lots_habitation',
        'score_rnic',

        // QPV / ZFU (même logique que blade : zone = vert, hors zone = rouge)
        'qpv_statut',
        'qp_2024',
        'qp_2024_nom_quartier',
        'qp_2015',
        'qp_2015_nom_quartier',
        'zfu',
        'zfu_nom_quartier',

        // Bâtiment
        'type_batiment',
        'type_chauffage_principal',
        'energie_chauffage_collectif',
        'annee_construction',
        'nb_logements',
        'nb_niveaux',
        'hauteur',
        'surface_habitable',
        'surface_emprise_sol',
        'classe_dpe',
        'ges',

        // Propriétaires
        'nb_proprietaires',
        'nb_coproprietes',
        'siren_copropriete',

        // Adresse normalisée
        'adresse_normalisee',
        'code_postal',
        'ville',
        'code_insee',
        'latitude',
        'longitude',

        // RNB
        'rnb_id',
        'rnb_statut',
        'rnb_nb_adresses',

        // Syndic
        'syndic_forme_juridique',
        'syndic_capital_social',
        'syndic_chiffre_affaires',
        'syndic_resultat',
        'syndic_effectif',
        'syndic_dirigeant',

        // Statut traitement
        'dr_statut',
        'dr_erreur',
    ];

    private array $enrichedHeaders = [
        'historique_statut'           => '🔍 Historique',
        'historique_nb_fois'          => 'Nb recherches',
        'historique_derniere'         => 'Dernière recherche',
        'representant_legal'          => 'Représentant légal',
        'nom_representant'            => 'Nom représentant / Syndic',
        'type_representant'           => 'Type représentant',
        'siren_syndic'                => 'SIREN Syndic',
        'siret_syndic'                => 'SIRET Syndic',
        'immatriculation_copro'       => 'N° Immatriculation',
        'nom_residence'               => 'Nom résidence',
        'nb_lots_habitation'          => 'Lots habitation',
        'score_rnic'                  => 'Score RNIC',
        'qpv_statut'                  => 'Statut QPV / ZFU',
        'qp_2024'                     => 'QP 2024',
        'qp_2024_nom_quartier'        => 'Nom quartier QP 2024',
        'qp_2015'                     => 'QP 2015',
        'qp_2015_nom_quartier'        => 'Nom quartier QP 2015',
        'zfu'                         => 'ZFU',
        'zfu_nom_quartier'            => 'Nom quartier ZFU',
        'type_batiment'               => 'Type bâtiment',
        'type_chauffage_principal'    => 'Type chauffage principal',
        'energie_chauffage_collectif' => 'Énergie chauffage',
        'annee_construction'          => 'Année construction',
        'nb_logements'                => 'Nb logements',
        'nb_niveaux'                  => 'Nb niveaux',
        'hauteur'                     => 'Hauteur (m)',
        'surface_habitable'           => 'Surface habitable (m²)',
        'surface_emprise_sol'         => 'Emprise sol (m²)',
        'classe_dpe'                  => 'Classe DPE',
        'ges'                         => 'GES',
        'nb_proprietaires'            => 'Nb propriétaires',
        'nb_coproprietes'             => 'Nb copropriétés',
        'siren_copropriete'           => 'SIREN Copropriété',
        'adresse_normalisee'          => 'Adresse normalisée BAN',
        'code_postal'                 => 'Code postal',
        'ville'                       => 'Ville',
        'code_insee'                  => 'Code INSEE',
        'latitude'                    => 'Latitude',
        'longitude'                   => 'Longitude',
        'rnb_id'                      => 'Identifiant RNB',
        'rnb_statut'                  => 'Statut RNB',
        'rnb_nb_adresses'             => 'Nb adresses RNB',
        'syndic_forme_juridique'      => 'Forme juridique syndic',
        'syndic_capital_social'       => 'Capital social',
        'syndic_chiffre_affaires'     => "Chiffre d'affaires",
        'syndic_resultat'             => 'Résultat',
        'syndic_effectif'             => 'Effectif',
        'syndic_dirigeant'            => 'Dirigeant principal',
        'dr_statut'                   => 'Statut traitement',
        'dr_erreur'                   => 'Erreur',
    ];

    private array $groupColors = [
        'historique_statut'           => '4C1D95',
        'historique_nb_fois'          => '4C1D95',
        'historique_derniere'         => '4C1D95',
        'representant_legal'          => '1E3A5F',
        'nom_representant'            => '1E3A5F',
        'type_representant'           => '1E3A5F',
        'siren_syndic'                => '1E3A5F',
        'siret_syndic'                => '1E3A5F',
        'immatriculation_copro'       => '1E3A5F',
        'nom_residence'               => '1E3A5F',
        'nb_lots_habitation'          => '1E3A5F',
        'score_rnic'                  => '1E3A5F',
        'qpv_statut'                  => '713F12',
        'qp_2024'                     => '713F12',
        'qp_2024_nom_quartier'        => '713F12',
        'qp_2015'                     => '713F12',
        'qp_2015_nom_quartier'        => '713F12',
        'zfu'                         => '713F12',
        'zfu_nom_quartier'            => '713F12',
        'type_batiment'               => '1B4332',
        'type_chauffage_principal'    => '1B4332',
        'energie_chauffage_collectif' => '1B4332',
        'annee_construction'          => '1B4332',
        'nb_logements'                => '1B4332',
        'nb_niveaux'                  => '1B4332',
        'hauteur'                     => '1B4332',
        'surface_habitable'           => '1B4332',
        'surface_emprise_sol'         => '1B4332',
        'classe_dpe'                  => '1B4332',
        'ges'                         => '1B4332',
        'nb_proprietaires'            => '4A1942',
        'nb_coproprietes'             => '7C2D12',
        'siren_copropriete'           => '7C2D12',
        'adresse_normalisee'          => '164E63',
        'code_postal'                 => '164E63',
        'ville'                       => '164E63',
        'code_insee'                  => '164E63',
        'latitude'                    => '164E63',
        'longitude'                   => '164E63',
        'rnb_id'                      => '312E81',
        'rnb_statut'                  => '312E81',
        'rnb_nb_adresses'             => '312E81',
        'syndic_forme_juridique'      => '3B0764',
        'syndic_capital_social'       => '3B0764',
        'syndic_chiffre_affaires'     => '3B0764',
        'syndic_resultat'             => '3B0764',
        'syndic_effectif'             => '3B0764',
        'syndic_dirigeant'            => '3B0764',
        'dr_statut'                   => '374151',
        'dr_erreur'                   => '374151',
    ];

    public function __construct(private readonly CsvImport $import) {}

    // ════════════════════════════════════════════════════════════
    // HANDLE
    // ════════════════════════════════════════════════════════════
    public function handle(DataRocketEngineService $engine): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $import = $this->import->fresh();
        if (!$import || $import->statut === 'termine') return;

        $import->update(['statut' => 'en_cours', 'lignes_traitees' => 0, 'progress' => 0]);

        $tmpFile = sys_get_temp_dir() . '/data360_' . $import->id . '_' . time() . '.xlsx';

        try {
            $csvContent = $import->csv_content;
            if (empty($csvContent)) throw new \RuntimeException('csv_content vide en base.');

            $firstReader = Reader::createFromString($csvContent);
            $firstReader->setHeaderOffset(0);
            $allRecords  = iterator_to_array($firstReader->getRecords());
            if (empty($allRecords)) throw new \RuntimeException('CSV vide après parsing.');

            $originalCols = array_keys(reset($allRecords));
            $adresseCol   = $this->detectAdresseColumn($originalCols);
            if (!$adresseCol) throw new \RuntimeException('Colonne "adresse" introuvable.');
            unset($allRecords);

            $total   = $import->total_lignes;
            $allCols = array_merge($originalCols, $this->enrichedCols);

            // Charger l'historique utilisateur
            $historiqueUser = $this->loadUserHistory($import);

            $writer = $this->createWriter($tmpFile);
            $this->writeHeaderRow($writer, $allCols);

            $traites   = 0;
            $rowNumber = 0;

            $reader = Reader::createFromString($csvContent);
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $record) {
                $rowNumber++;
                $adresseQuery   = trim((string) ($record[$adresseCol] ?? ''));
                $historiqueInfo = $this->checkHistorique($adresseQuery, $historiqueUser);

                if (empty($adresseQuery)) {
                    $rowData = $this->buildEmptyRowData($record, $originalCols, 'Adresse vide');
                    $rowData['historique_statut']   = '';
                    $rowData['historique_nb_fois']  = '';
                    $rowData['historique_derniere'] = '';
                } else {
                    try {
                        $result  = $engine->searchByAddress($adresseQuery, $import->user_id);
                        $rowData = $this->mapResultToRowData($result, $record, $originalCols);
                        if ($import->user) $import->user->consumeCredit();
                    } catch (\Throwable $e) {
                        Log::warning("ProcessCsvImport [{$adresseQuery}]: " . $e->getMessage());
                        $rowData = $this->buildEmptyRowData($record, $originalCols, $e->getMessage());
                    }
                    $rowData['historique_statut']   = $historiqueInfo['statut'];
                    $rowData['historique_nb_fois']  = $historiqueInfo['nb_fois'];
                    $rowData['historique_derniere'] = $historiqueInfo['derniere'];
                }

                $this->writeDataRow($writer, $rowData, $allCols, $rowNumber);
                unset($rowData);

                $traites++;
                $this->updateProgress($import, $traites, $total);
                usleep(150_000);
            }

            $writer->close();

            if (!file_exists($tmpFile) || filesize($tmpFile) === 0)
                throw new \RuntimeException('XLSX généré est vide.');

            $xlsxBase64 = $this->fileToBase64Chunked($tmpFile);

            $import->update([
                'statut'          => 'termine',
                'xlsx_content'    => $xlsxBase64,
                'lignes_traitees' => $total,
                'progress'        => 100,
                'csv_content'     => null,
            ]);

        } catch (\Throwable $e) {
            Log::error("ProcessCsvImport [{$import->id}] ERREUR: " . $e->getMessage());
            $import->update(['statut' => 'erreur', 'erreur_message' => $e->getMessage()]);
        } finally {
            if (file_exists($tmpFile)) @unlink($tmpFile);
        }
    }

    // ════════════════════════════════════════════════════════════
    // HISTORIQUE
    // ════════════════════════════════════════════════════════════
    private function loadUserHistory(CsvImport $import): array
    {
        if (!$import->user_id) return [];
        $recherches = Recherche::where('user_id', $import->user_id)
            ->select(['requete', 'created_at'])->orderBy('created_at', 'desc')->get();
        $history = [];
        foreach ($recherches as $r) {
            $key = $this->normalizeAdresse($r->requete);
            if (!isset($history[$key])) {
                $history[$key] = ['count' => 0, 'derniere' => $r->created_at?->format('d/m/Y') ?? ''];
            }
            $history[$key]['count']++;
        }
        return $history;
    }

    private function checkHistorique(string $adresse, array $history): array
    {
        if (empty($adresse)) return ['statut' => '', 'nb_fois' => '', 'derniere' => ''];
        $key = $this->normalizeAdresse($adresse);
        if (isset($history[$key]) && $history[$key]['count'] > 0) {
            return [
                'statut'   => '● Déjà recherchée',
                'nb_fois'  => (string) $history[$key]['count'],
                'derniere' => $history[$key]['derniere'],
            ];
        }
        return ['statut' => '○ Nouvelle', 'nb_fois' => '0', 'derniere' => ''];
    }

    private function normalizeAdresse(string $adresse): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $adresse)));
    }

    // ════════════════════════════════════════════════════════════
    // MAPPING RÉSULTAT — MÊME LOGIQUE QUE LE BLADE
    // ════════════════════════════════════════════════════════════
    private function mapResultToRowData(array $result, array $record, array $originalCols): array
    {
        $row = [];
        foreach ($originalCols as $col) $row[$col] = $record[$col] ?? '';

        if (!($result['success'] ?? false)) {
            return array_merge($row, $this->emptyEnrichedData([
                'dr_statut' => 'ERREUR',
                'dr_erreur' => $result['message'] ?? 'Adresse introuvable',
            ]));
        }

        $adresse  = $result['adresse']           ?? null;
        $batiments= $result['batiments']          ?? [];
        $copros   = collect($result['coproprietes'] ?? []);
        $syndics  = $result['syndics']            ?? [];
        $qpv      = $result['qpv']                ?? null;
        $rnbData  = $result['rnb']                ?? null;
        $proprios = $result['proprietaires_bdnb'] ?? [];

        $batiment = $batiments[0] ?? null;
        $syndic   = $syndics[0]   ?? null;

        // ── QPV — même logique que la blade ──────────────────
        $qpvData = $this->extractQpvData($qpv);

        // ── Représentant — même logique que la blade ─────────
        $repData = $this->extractRepresentantData($copros);

        // RNB
        $rnbId = null; $rnbStatut = null; $rnbAddrs = collect();
        if ($rnbData) $this->extractRnbData($rnbData, $rnbId, $rnbStatut, $rnbAddrs);

        return array_merge($row, [
            // Historique sera injecté après
            'historique_statut'           => '',
            'historique_nb_fois'          => '',
            'historique_derniere'         => '',

            // Représentant légal (logique blade)
            'representant_legal'          => $repData['label'],
            'nom_representant'            => $repData['nom'] ?: $this->v($syndic, ['nom']),
            'type_representant'           => $repData['type'] ?: ($syndic ? 'syndic' : ''),
            'siren_syndic'                => $repData['siren'] ?: $this->v($syndic, ['siren']),
            'siret_syndic'                => $repData['siret'] ?: $this->v($syndic, ['siret']),
            'immatriculation_copro'       => $repData['immatriculation'],
            'nom_residence'               => $repData['nom_copropriete'],
            'nb_lots_habitation'          => $repData['nb_lots'],
            'score_rnic'                  => $repData['score'],

            // QPV (logique blade : zone = bon, hors zone = mauvais)
            'qpv_statut'                  => $qpvData['statut'],
            'qp_2024'                     => $qpvData['qp_2024'],
            'qp_2024_nom_quartier'        => $qpvData['qp_2024_nom'],
            'qp_2015'                     => $qpvData['qp_2015'],
            'qp_2015_nom_quartier'        => $qpvData['qp_2015_nom'],
            'zfu'                         => $qpvData['zfu'],
            'zfu_nom_quartier'            => $qpvData['zfu_nom'],

            // Bâtiment
            'type_batiment'               => $this->v($batiment, ['type_batiment']),
            'type_chauffage_principal'    => $this->v($batiment, ['type_chauffage', 'chauffage_principal']),
            'energie_chauffage_collectif' => $this->v($batiment, ['energie_chauffage', 'energie_principale_chauffage']),
            'annee_construction'          => $this->v($batiment, ['annee_construction']),
            'nb_logements'                => $this->v($batiment, ['nombre_logements']),
            'nb_niveaux'                  => $this->v($batiment, ['nombre_niveaux']),
            'hauteur'                     => $this->v($batiment, ['hauteur']),
            'surface_habitable'           => $this->v($batiment, ['surface_habitable']),
            'surface_emprise_sol'         => $this->v($batiment, ['surface_emprise_sol']),
            'classe_dpe'                  => $this->v($batiment, ['classe_dpe']),
            'ges'                         => $this->v($batiment, ['ges']),

            // Propriétaires
            'nb_proprietaires'            => (string) count($proprios),
            'nb_coproprietes'             => (string) $copros->count(),
            'siren_copropriete'           => $repData['siren_copropriete'],

            // Adresse
            'adresse_normalisee'          => $adresse?->adresse_complete ?? '',
            'code_postal'                 => $adresse?->code_postal       ?? '',
            'ville'                       => $adresse?->ville              ?? '',
            'code_insee'                  => $adresse?->code_insee         ?? '',
            'latitude'                    => $adresse?->latitude  !== null ? (string) $adresse->latitude  : '',
            'longitude'                   => $adresse?->longitude !== null ? (string) $adresse->longitude : '',

            // RNB
            'rnb_id'                      => $rnbId    ?? '',
            'rnb_statut'                  => $rnbStatut ?? '',
            'rnb_nb_adresses'             => (string) $rnbAddrs->count(),

            // Syndic
            'syndic_forme_juridique'      => $this->v($syndic, ['forme_juridique']),
            'syndic_capital_social'       => $this->v($syndic, ['capital_social']),
            'syndic_chiffre_affaires'     => $this->v($syndic, ['chiffre_affaires']),
            'syndic_resultat'             => $this->v($syndic, ['resultat']),
            'syndic_effectif'             => $this->v($syndic, ['effectif']),
            'syndic_dirigeant'            => $this->v($syndic, ['dirigeant_principal']),

            'dr_statut'                   => 'OK — ' . ($result['message'] ?? 'Enrichi'),
            'dr_erreur'                   => '',
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // QPV — MÊME LOGIQUE QUE LA BLADE
    // QPV détecté = BIEN (vert) / Hors zone = ROUGE
    // ════════════════════════════════════════════════════════════
    private function extractQpvData(?array $qpv): array
    {
        $qpvChecks  = collect($qpv['checks'] ?? []);
        $hasQpv2024 = $qpvChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
        $hasQpv2015 = $qpvChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
        $hasZfu     = $qpvChecks->contains(fn($c) => $c['result']['zfu']     ?? false);

        // Noms des quartiers
        $nomQp2024 = $qpvChecks->map(fn($c) => $c['result']['matches']['qp_2024']['nom'] ?? null)->filter()->first() ?? '';
        $nomQp2015 = $qpvChecks->map(fn($c) => $c['result']['matches']['qp_2015']['nom'] ?? null)->filter()->first() ?? '';
        $nomZfu    = $qpvChecks->map(fn($c) => $c['result']['matches']['zfu']['nom']     ?? null)->filter()->first() ?? '';

        // Statut global (même logique blade)
        if (($hasQpv2024 || $hasQpv2015) && $hasZfu) {
            $statut = 'Dans QP & ZFU';
        } elseif ($hasQpv2024 || $hasQpv2015) {
            $statut = 'Dans QP';
        } elseif ($hasZfu) {
            $statut = 'Dans ZFU';
        } else {
            $statut = 'Hors QP & ZFU';
        }

        return [
            'statut'     => $statut,
            'qp_2024'    => $hasQpv2024 ? 'Dans QP 2024' : 'Hors QP 2024',
            'qp_2024_nom'=> $nomQp2024,
            'qp_2015'    => $hasQpv2015 ? 'Dans QP 2015' : 'Hors QP 2015',
            'qp_2015_nom'=> $nomQp2015,
            'zfu'        => $hasZfu     ? 'Dans ZFU'    : 'Hors ZFU',
            'zfu_nom'    => $nomZfu,
        ];
    }

    // ════════════════════════════════════════════════════════════
    // REPRÉSENTANT — MÊME LOGIQUE QUE LA BLADE
    // Helpers : drRepConnu, drMandatExpire, drDateFin
    // ════════════════════════════════════════════════════════════
    private function extractRepresentantData(\Illuminate\Support\Collection $copros): array
    {
        $empty = [
            'label' => 'Sans représentant légal', 'nom' => '', 'type' => '',
            'siren' => '', 'siret' => '', 'immatriculation' => '',
            'nom_copropriete' => '', 'nb_lots' => '', 'score' => '',
            'siren_copropriete' => '',
        ];

        if ($copros->isEmpty()) return $empty;

        // Priorité : copro avec représentant (même logique blade)
        $coprosAvecRep = $copros->filter(fn($c) => $this->cRepConnu($c));
        $coproPrincipale = $coprosAvecRep->sortByDesc(fn($c) => (int) $this->v($c, ['score_match']))->first()
            ?? $copros->filter(fn($c) => !empty($this->v($c, ['numero_immatriculation'])))->sortByDesc(fn($c) => (int) $this->v($c, ['score_match']))->first()
            ?? $copros->first();

        if (!$coproPrincipale) return $empty;

        $repNom   = $this->v($coproPrincipale, ['representant_legal_nom', 'syndic_nom', 'raison_sociale_representant_legal', 'identification_representant_legal']);
        $sirenRep = $this->v($coproPrincipale, ['siren_syndic', 'siren_representant_legal']);
        $siretRep = $this->v($coproPrincipale, ['siret_syndic', 'siret_representant_legal']);
        $typeRep  = $this->v($coproPrincipale, ['representant_legal_type', 'type_syndic']);

        $mandatExpire  = $this->cMandatExpire($coproPrincipale);
        $dateFinMandat = $this->cDateFin($coproPrincipale);
        $repConnu      = $this->cRepConnu($coproPrincipale);

        // Scanner toutes les copros si pas trouvé sur la principale
        if (!$repConnu && $copros->isNotEmpty()) {
            $anyRep = $copros->filter(fn($c) => $this->cRepConnu($c))->first();
            if ($anyRep) {
                $repConnu      = true;
                $repNom        = $repNom ?: $this->v($anyRep, ['representant_legal_nom', 'syndic_nom']);
                $sirenRep      = $sirenRep ?: $this->v($anyRep, ['siren_syndic', 'siren_representant_legal']);
                $mandatExpire  = $mandatExpire ?: $this->cMandatExpire($anyRep);
                $dateFinMandat = $dateFinMandat ?: $this->cDateFin($anyRep);
            }
        }

        // Label (même logique blade)
        if ($repConnu && !empty($repNom) && !$mandatExpire) {
            $label = 'Avec représentant légal';
        } elseif ($repConnu && $mandatExpire) {
            $label = 'Gestionnaire de fait (mandat expiré le ' . $dateFinMandat . ')';
        } elseif ($repConnu) {
            $label = 'Avec représentant légal';
        } else {
            $label = 'Sans représentant légal';
        }

        return [
            'label'            => $label,
            'nom'              => $repNom,
            'type'             => $typeRep,
            'siren'            => $sirenRep,
            'siret'            => $siretRep,
            'immatriculation'  => $this->v($coproPrincipale, ['numero_immatriculation']),
            'nom_copropriete'  => $this->v($coproPrincipale, ['nom_copropriete', 'nom_usage_copropriete']),
            'nb_lots'          => $this->v($coproPrincipale, ['nombre_lots_habitation']),
            'score'            => $this->v($coproPrincipale, ['score_match']),
            'siren_copropriete'=> $this->v($coproPrincipale, ['siren_copropriete']),
        ];
    }

    // ── Helpers représentant (équivalents aux fonctions PHP du blade) ──

    private function cV(mixed $copro, array $keys): string
    {
        foreach ($keys as $key) {
            $val = is_object($copro) ? ($copro->{$key} ?? null) : ($copro[$key] ?? null);
            if ($val !== null && trim((string)$val) !== '') return (string)$val;
            $raw = is_object($copro) ? ($copro->raw_data ?? []) : ($copro['raw_data'] ?? []);
            if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
            $val = $raw[$key] ?? null;
            if ($val !== null && trim((string)$val) !== '') return (string)$val;
        }
        return '';
    }

    private function cDateFin(mixed $copro): string
    {
        $val = $this->cV($copro, ['date_fin_dernier_mandat']);
        if ($val && !in_array($val, ['-', ''], true)) return $val;
        $raw = is_object($copro) ? ($copro->raw_data ?? []) : ($copro['raw_data'] ?? []);
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
        $rnic = $raw['raw_data'] ?? $raw;
        if (is_string($rnic)) $rnic = json_decode($rnic, true) ?: [];
        $val = $rnic['date_fin_dernier_mandat'] ?? '';
        return ($val && !in_array($val, ['-', ''], true)) ? $val : '';
    }

    private function cMandatExpire(mixed $copro): bool
    {
        $statut = $this->cV($copro, ['statut', 'mandat_en_cours']);
        if (!$statut || in_array($statut, ['-', ''], true)) return false;
        $lower  = strtolower($statut);
        $dateFin = $this->cDateFin($copro);
        return (
            str_contains($lower, 'pas de mandat')
            || str_contains($lower, 'mandat expir')
            || str_contains($lower, 'sans successeur')
        ) && !empty($dateFin);
    }

    private function cRepConnu(mixed $copro): bool
    {
        if (!empty($this->cV($copro, ['representant_legal_nom', 'syndic_nom', 'raison_sociale_representant_legal', 'identification_representant_legal']))) return true;
        if (!empty($this->cV($copro, ['siren_syndic', 'siren_representant_legal']))) return true;
        if (!empty($this->cV($copro, ['siret_syndic', 'siret_representant_legal']))) return true;
        $connu = is_object($copro) ? ($copro->representant_legal_connu ?? false) : ($copro['representant_legal_connu'] ?? false);
        if ((bool)$connu) return true;
        if ($this->cMandatExpire($copro)) return true;
        return false;
    }

    // ════════════════════════════════════════════════════════════
    // COLORATION CONDITIONNELLE
    // ════════════════════════════════════════════════════════════
    private function conditionalStyle(string $key, string $value): ?Style
    {
        if ($value === '') return null;

        $green = (new Style())->setFontBold()->setFontSize(9)->setFontColor('15803D')
            ->setBackgroundColor('DCFCE7')->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $red = (new Style())->setFontBold()->setFontSize(9)->setFontColor('B91C1C')
            ->setBackgroundColor('FEE2E2')->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $orange = (new Style())->setFontBold()->setFontSize(9)->setFontColor('92400E')
            ->setBackgroundColor('FEF3C7')->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $lower = mb_strtolower(trim($value));

        return match($key) {

            // ── Historique ───────────────────────────────────
            'historique_statut' => str_contains($lower, 'déjà') ? $red : $green,
            'historique_nb_fois'=> $value !== '0' && $value !== '' ? $orange : null,

            // ── Représentant légal ───────────────────────────
            'representant_legal' => str_contains($lower, 'avec') ? $green
                : (str_contains($lower, 'gestionnaire') ? $orange : $red),

            // ── QPV / ZFU — MÊME LOGIQUE BLADE ──────────────
            // Zone détectée = VERT (bon pour nos clients)
            // Hors zone = ROUGE
            'qpv_statut' =>
                (str_contains($lower, 'dans qp') || str_contains($lower, 'dans zfu'))
                    ? $green : $red,

            'qp_2024' => str_contains($lower, 'dans') ? $green : $red,
            'qp_2015' => str_contains($lower, 'dans') ? $green : $red,
            'zfu'     => str_contains($lower, 'dans') ? $green : $red,

            // ── Type bâtiment ────────────────────────────────
            'type_batiment' => str_contains($lower, 'collectif') ? $red : $green,

            // ── Type chauffage ───────────────────────────────
            'type_chauffage_principal' => str_contains($lower, 'collectif') ? $red : $green,

            // ── Énergie chauffage ────────────────────────────
            'energie_chauffage_collectif' =>
                (str_contains($lower, 'electr') || str_contains($lower, 'électr'))
                    ? $green : $red,

            // ── Statut traitement ────────────────────────────
            'dr_statut' => str_starts_with($lower, 'ok') ? $green : $red,

            default => null,
        };
    }

    // ════════════════════════════════════════════════════════════
    // OPENSPOUT
    // ════════════════════════════════════════════════════════════
    private function createWriter(string $filePath): Writer
    {
        $options = new Options();
        $options->DEFAULT_ROW_HEIGHT        = 20;
        $options->DEFAULT_COLUMN_WIDTH      = 18;
        $options->SHOULD_USE_INLINE_STRINGS = true;
        $writer = new Writer($options);
        $writer->openToFile($filePath);
        return $writer;
    }

    private function writeHeaderRow(Writer $writer, array $allCols): void
    {
        $styleCache = [];
        $cells      = [];
        foreach ($allCols as $key) {
            $label = $this->enrichedHeaders[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $bg    = $this->groupColors[$key] ?? '0F172A';
            if (!isset($styleCache[$bg])) {
                $styleCache[$bg] = (new Style())
                    ->setFontBold()->setFontColor('FFFFFF')->setFontSize(9)
                    ->setBackgroundColor($bg)
                    ->setCellAlignment(CellAlignment::CENTER)
                    ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                    ->setShouldWrapText(true);
            }
            $cells[] = Cell::fromValue($label, $styleCache[$bg]);
        }
        $writer->addRow(new Row($cells));
    }

    private function writeDataRow(Writer $writer, array $rowData, array $allCols, int $rowNumber): void
    {
        $cells  = [];
        $bgBase = ($rowNumber % 2 === 0) ? 'FFFFFF' : 'F8FAFC';
        $baseStyle = (new Style())
            ->setFontSize(9)->setFontColor('1E293B')
            ->setBackgroundColor($bgBase)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        foreach ($allCols as $key) {
            $value = $this->sanitize((string) ($rowData[$key] ?? ''));
            $style = $this->conditionalStyle($key, $value) ?? $baseStyle;
            $cells[] = Cell::fromValue($value, $style);
        }
        $writer->addRow(new Row($cells));
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS GÉNÉRAUX
    // ════════════════════════════════════════════════════════════
    private function v(mixed $model, array $keys, string $default = ''): string
    {
        if ($model === null) return $default;
        foreach ($keys as $key) {
            if (is_object($model) && isset($model->{$key}) && $model->{$key} !== null && $model->{$key} !== '') return (string) $model->{$key};
            if (is_array($model)  && isset($model[$key])   && $model[$key]  !== null && $model[$key]  !== '') return (string) $model[$key];
            $raw = is_object($model) ? ($model->raw_data ?? []) : ($model['raw_data'] ?? []);
            if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
            if (is_array($raw) && isset($raw[$key]) && $raw[$key] !== null) return (string) $raw[$key];
        }
        return $default;
    }

    private function sanitize(?string $value): string
    {
        if ($value === null) return '';
        return mb_convert_encoding(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '', 'UTF-8', 'UTF-8') ?? '';
    }

    private function detectAdresseColumn(array $cols): ?string
    {
        foreach ($cols as $col) {
            if (in_array(strtolower(trim($col)), ['adresse','address','adresse_complete'], true)) return $col;
        }
        return $cols[0] ?? null;
    }

    private function updateProgress(CsvImport $import, int $done, int $total): void
    {
        $import->update([
            'lignes_traitees' => $done,
            'progress'        => $total > 0 ? (int) round($done / $total * 100) : 0,
        ]);
    }

    private function buildEmptyRowData(array $record, array $originalCols, string $erreur = ''): array
    {
        $row = [];
        foreach ($originalCols as $col) $row[$col] = $record[$col] ?? '';
        return array_merge($row, $this->emptyEnrichedData([
            'dr_statut' => 'ERREUR',
            'dr_erreur' => substr($erreur, 0, 200),
        ]));
    }

    private function emptyEnrichedData(array $overrides = []): array
    {
        return array_merge(array_fill_keys($this->enrichedCols, ''), $overrides);
    }

    private function fileToBase64Chunked(string $filePath): string
    {
        $handle = fopen($filePath, 'rb');
        $base64 = '';
        while (!feof($handle)) $base64 .= base64_encode(fread($handle, 1024 * 1024));
        fclose($handle);
        return $base64;
    }

    private function extractRnbData($data, &$rnbId, &$rnbStatut, &$addresses): void
    {
        if (!is_array($data)) return;
        if (isset($data['rnb_id']) && !$rnbId)     $rnbId     = $data['rnb_id'];
        if (isset($data['status']) && !$rnbStatut) $rnbStatut = $data['status'];
        if (isset($data['addresses']) && is_array($data['addresses'])) {
            foreach ($data['addresses'] as $addr) {
                $label = $addr['label'] ?? $addr['adresse'] ?? null;
                if ($label) $addresses->push(['adresse' => $label]);
            }
        }
        foreach ($data as $value) {
            if (is_array($value)) $this->extractRnbData($value, $rnbId, $rnbStatut, $addresses);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessCsvImport [{$this->import->id}] failed: " . $exception->getMessage());
        $this->import->update(['statut' => 'erreur', 'erreur_message' => $exception->getMessage()]);
    }
}
