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
    // ORDRE DES COLONNES ENRICHIES
    // HISTORIQUE en PREMIER → facile à filtrer dans Excel
    // ════════════════════════════════════════════════════════════
    private array $enrichedCols = [
        // ── Historique utilisateur (1ère colonne → filtre facile) ──
        'historique_statut',     // Nouvelle / Déjà recherchée
        'historique_nb_fois',    // Nombre de fois recherchée
        'historique_derniere',   // Date de la dernière recherche

        // ── Représentant / RNIC ──
        'representant_legal',
        'nom_representant',
        'type_representant',
        'siren_syndic',
        'siret_syndic',
        'immatriculation_copro',
        'nom_residence',
        'nb_lots_habitation',
        'score_rnic',

        // ── Bâtiment (prioritaires) ──
        'type_batiment',
        'type_chauffage_principal',
        'energie_chauffage_collectif',

        // ── Bâtiment (reste) ──
        'annee_construction',
        'nb_logements',
        'nb_niveaux',
        'hauteur',
        'surface_habitable',
        'surface_emprise_sol',
        'classe_dpe',
        'ges',

        // ── Propriétaires / Copropriétés ──
        'nb_proprietaires',
        'nb_coproprietes',
        'siren_copropriete',

        // ── Adresse normalisée ──
        'adresse_normalisee',
        'code_postal',
        'ville',
        'code_insee',
        'latitude',
        'longitude',

        // ── QPV / ZFU ──
        'qpv_eligible',
        'qp_2024',
        'qp_2015',
        'zfu',

        // ── RNB ──
        'rnb_id',
        'rnb_statut',
        'rnb_nb_adresses',

        // ── Syndic ──
        'syndic_forme_juridique',
        'syndic_capital_social',
        'syndic_chiffre_affaires',
        'syndic_resultat',
        'syndic_effectif',
        'syndic_dirigeant',

        // ── Statut traitement ──
        'dr_statut',
        'dr_erreur',
    ];

    private array $enrichedHeaders = [
        // Historique
        'historique_statut'           => '🔍 Historique',
        'historique_nb_fois'          => 'Nb recherches',
        'historique_derniere'         => 'Dernière recherche',

        // Représentant
        'representant_legal'          => 'Représentant légal',
        'nom_representant'            => 'Nom représentant / Syndic',
        'type_representant'           => 'Type représentant',
        'siren_syndic'                => 'SIREN Syndic',
        'siret_syndic'                => 'SIRET Syndic',
        'immatriculation_copro'       => 'N° Immatriculation',
        'nom_residence'               => 'Nom résidence',
        'nb_lots_habitation'          => 'Lots habitation',
        'score_rnic'                  => 'Score RNIC',

        // Bâtiment
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

        // Propriétaires
        'nb_proprietaires'            => 'Nb propriétaires',
        'nb_coproprietes'             => 'Nb copropriétés',
        'siren_copropriete'           => 'SIREN Copropriété',

        // Adresse
        'adresse_normalisee'          => 'Adresse normalisée BAN',
        'code_postal'                 => 'Code postal',
        'ville'                       => 'Ville',
        'code_insee'                  => 'Code INSEE',
        'latitude'                    => 'Latitude',
        'longitude'                   => 'Longitude',

        // QPV
        'qpv_eligible'                => 'QPV Éligible',
        'qp_2024'                     => 'QP 2024',
        'qp_2015'                     => 'QP 2015',
        'zfu'                         => 'ZFU',

        // RNB
        'rnb_id'                      => 'Identifiant RNB',
        'rnb_statut'                  => 'Statut RNB',
        'rnb_nb_adresses'             => 'Nb adresses RNB',

        // Syndic
        'syndic_forme_juridique'      => 'Forme juridique syndic',
        'syndic_capital_social'       => 'Capital social',
        'syndic_chiffre_affaires'     => "Chiffre d'affaires",
        'syndic_resultat'             => 'Résultat',
        'syndic_effectif'             => 'Effectif',
        'syndic_dirigeant'            => 'Dirigeant principal',

        // Statut
        'dr_statut'                   => 'Statut traitement',
        'dr_erreur'                   => 'Erreur',
    ];

    private array $groupColors = [
        // Historique — violet distinctif pour trouver la colonne en un coup d'œil
        'historique_statut'           => '4C1D95',
        'historique_nb_fois'          => '4C1D95',
        'historique_derniere'         => '4C1D95',

        // Représentant
        'representant_legal'          => '1E3A5F',
        'nom_representant'            => '1E3A5F',
        'type_representant'           => '1E3A5F',
        'siren_syndic'                => '1E3A5F',
        'siret_syndic'                => '1E3A5F',
        'immatriculation_copro'       => '1E3A5F',
        'nom_residence'               => '1E3A5F',
        'nb_lots_habitation'          => '1E3A5F',
        'score_rnic'                  => '1E3A5F',

        // Bâtiment
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

        // Propriétaires
        'nb_proprietaires'            => '4A1942',
        'nb_coproprietes'             => '7C2D12',
        'siren_copropriete'           => '7C2D12',

        // Adresse
        'adresse_normalisee'          => '164E63',
        'code_postal'                 => '164E63',
        'ville'                       => '164E63',
        'code_insee'                  => '164E63',
        'latitude'                    => '164E63',
        'longitude'                   => '164E63',

        // QPV
        'qpv_eligible'                => '713F12',
        'qp_2024'                     => '713F12',
        'qp_2015'                     => '713F12',
        'zfu'                         => '713F12',

        // RNB
        'rnb_id'                      => '312E81',
        'rnb_statut'                  => '312E81',
        'rnb_nb_adresses'             => '312E81',

        // Syndic
        'syndic_forme_juridique'      => '3B0764',
        'syndic_capital_social'       => '3B0764',
        'syndic_chiffre_affaires'     => '3B0764',
        'syndic_resultat'             => '3B0764',
        'syndic_effectif'             => '3B0764',
        'syndic_dirigeant'            => '3B0764',

        // Statut
        'dr_statut'                   => '374151',
        'dr_erreur'                   => '374151',
    ];

    public function __construct(
        private readonly CsvImport $import
    ) {}

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

            // ── Colonnes originales ───────────────────────────
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

            // ════════════════════════════════════════════════════
            // ✅ NOUVEAU — Charger l'historique de recherches
            //    de l'utilisateur AVANT la boucle
            //    Format : ['adresse normalisée' => [count, last_date]]
            // ════════════════════════════════════════════════════
            $historiqueUser = $this->loadUserHistory($import);

            // ── Writer XLSX ───────────────────────────────────
            $writer = $this->createWriter($tmpFile);
            $this->writeHeaderRow($writer, $allCols);

            $traites   = 0;
            $rowNumber = 0;

            $reader = Reader::createFromString($csvContent);
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $record) {
                $rowNumber++;
                $adresseQuery = trim((string) ($record[$adresseCol] ?? ''));

                // ── Recherche dans l'historique ───────────────
                $historiqueInfo = $this->checkHistorique($adresseQuery, $historiqueUser);

                if (empty($adresseQuery)) {
                    $rowData = $this->buildEmptyRowData($record, $originalCols, 'Adresse vide');
                    $rowData['historique_statut']   = '';
                    $rowData['historique_nb_fois']  = '';
                    $rowData['historique_derniere'] = '';
                } else {
                    try {
                        $result  = $engine->searchByAddress($adresseQuery);
                        $rowData = $this->mapResultToRowData($result, $record, $originalCols);

                        if ($import->user) $import->user->consumeCredit();
                    } catch (\Throwable $e) {
                        Log::warning("ProcessCsvImport [{$adresseQuery}]: " . $e->getMessage());
                        $rowData = $this->buildEmptyRowData($record, $originalCols, $e->getMessage());
                    }

                    // ── Injecter l'historique dans la ligne ───
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

            Log::info("ProcessCsvImport [{$import->id}] terminé — {$total} adresses");

        } catch (\Throwable $e) {
            Log::error("ProcessCsvImport [{$import->id}] ERREUR: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            $import->update(['statut' => 'erreur', 'erreur_message' => $e->getMessage()]);
        } finally {
            if (file_exists($tmpFile)) @unlink($tmpFile);
        }
    }

    // ════════════════════════════════════════════════════════════
    // ✅ NOUVEAU — Charger l'historique de l'utilisateur
    // Retourne un tableau indexé par adresse normalisée
    // ════════════════════════════════════════════════════════════
    private function loadUserHistory(CsvImport $import): array
    {
        if (!$import->user_id) return [];

        $recherches = Recherche::where('user_id', $import->user_id)
            ->select(['requete', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        $history = [];
        foreach ($recherches as $r) {
            $key = $this->normalizeAdresse($r->requete);
            if (!isset($history[$key])) {
                $history[$key] = [
                    'count'    => 0,
                    'derniere' => $r->created_at?->format('d/m/Y') ?? '',
                ];
            }
            $history[$key]['count']++;
        }

        return $history;
    }

    // ════════════════════════════════════════════════════════════
    // ✅ NOUVEAU — Vérifier l'historique pour une adresse
    // ════════════════════════════════════════════════════════════
    private function checkHistorique(string $adresse, array $history): array
    {
        if (empty($adresse)) {
            return ['statut' => '', 'nb_fois' => '', 'derniere' => ''];
        }

        $key = $this->normalizeAdresse($adresse);

        if (isset($history[$key]) && $history[$key]['count'] > 0) {
            $n = $history[$key]['count'];
            return [
                // 🔴 Rouge dans Excel = déjà recherchée → à exclure pour filtrer
                'statut'   => '● Déjà recherchée',
                'nb_fois'  => (string) $n,
                'derniere' => $history[$key]['derniere'],
            ];
        }

        return [
            // ⚪ Vide / neutre = nouvelle adresse → à garder
            'statut'   => '○ Nouvelle',
            'nb_fois'  => '0',
            'derniere' => '',
        ];
    }

    private function normalizeAdresse(string $adresse): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $adresse)));
    }

    // ════════════════════════════════════════════════════════════
    // OPENSPOUT — WRITER
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
    // COLORATION CONDITIONNELLE
    // ════════════════════════════════════════════════════════════
    private function conditionalStyle(string $key, string $value): ?Style
    {
        if ($value === '') return null;

        $green = (new Style())->setFontBold()->setFontSize(9)->setFontColor('15803D')
            ->setBackgroundColor('DCFCE7')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $red = (new Style())->setFontBold()->setFontSize(9)->setFontColor('B91C1C')
            ->setBackgroundColor('FEE2E2')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $orange = (new Style())->setFontBold()->setFontSize(9)->setFontColor('92400E')
            ->setBackgroundColor('FEF3C7')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $lower = mb_strtolower(trim($value));

        return match($key) {

            // ── ✅ Historique — colonne la plus importante ────
            // 🔴 Rouge = déjà recherchée → à exclure dans le filtre Excel
            // 🟢 Vert  = nouvelle adresse → à garder
            'historique_statut' => str_contains($lower, 'déjà') ? $red : $green,
            'historique_nb_fois'=> $value !== '0' && $value !== '' ? $orange : null,

            // ── Représentant légal ───────────────────────────
            'representant_legal' => str_contains($lower, 'avec') ? $green : $red,

            // ── Type bâtiment ────────────────────────────────
            'type_batiment' => str_contains($lower, 'collectif') ? $red : $green,

            // ── Type chauffage ───────────────────────────────
            'type_chauffage_principal' => str_contains($lower, 'collectif') ? $red : $green,

            // ── Énergie chauffage ────────────────────────────
            'energie_chauffage_collectif' =>
                (str_contains($lower, 'electr') || str_contains($lower, 'électr'))
                    ? $green : $red,

            // ── QPV ──────────────────────────────────────────
            'qpv_eligible' => $lower === 'éligible' ? $green : $red,
            'qp_2024', 'qp_2015', 'zfu' => $lower === 'oui' ? $red : $green,

            // ── Statut traitement ────────────────────────────
            'dr_statut' => str_starts_with($lower, 'ok') ? $green : $red,

            default => null,
        };
    }

    // ════════════════════════════════════════════════════════════
    // MAPPING RÉSULTAT → LIGNE
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
        $copros   = $result['coproprietes']       ?? [];
        $syndics  = $result['syndics']            ?? [];
        $qpv      = $result['qpv']                ?? null;
        $rnbData  = $result['rnb']                ?? null;
        $proprios = $result['proprietaires_bdnb'] ?? [];

        $batiment = $batiments[0] ?? null;
        $copro    = $copros[0]    ?? null;
        $syndic   = $syndics[0]   ?? null;

        $qpvChecks = collect($qpv['checks'] ?? []);
        $hasQp2024 = $qpvChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
        $hasQp2015 = $qpvChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
        $hasZfu    = $qpvChecks->contains(fn($c) => $c['result']['zfu']     ?? false);

        $rnbId = null; $rnbStatut = null; $rnbAddrs = collect();
        if ($rnbData) $this->extractRnbData($rnbData, $rnbId, $rnbStatut, $rnbAddrs);

        $repNom    = $this->v($copro, ['representant_legal_nom','syndic_nom']);
        $sirenSynd = $this->v($copro, ['siren_syndic','siren_representant_legal']);
        $siretSynd = $this->v($copro, ['siret_syndic','siret_representant_legal']);

        // Vérifier mandat expiré comme dans la blade
        $statutCopro   = $this->v($copro, ['statut', 'mandat_en_cours']);
        $dateFinMandat = $this->getDateFinMandat($copro);
        $mandatExpire  = empty($repNom) && empty($sirenSynd) && empty($siretSynd) && !empty($dateFinMandat);

        $repConnu = $syndic !== null || !empty($repNom) || !empty($sirenSynd) || $mandatExpire;

        $repLabel = $repConnu
            ? ($mandatExpire
                ? 'Avec représentant légal (mandat expiré le ' . $dateFinMandat . ')'
                : 'Avec représentant légal')
            : 'Sans représentant légal';

        return array_merge($row, [
            // Historique sera injecté après dans handle()
            'historique_statut'           => '',
            'historique_nb_fois'          => '',
            'historique_derniere'         => '',

            'representant_legal'          => $repLabel,
            'nom_representant'            => $repNom ?: $this->v($syndic, ['nom']),
            'type_representant'           => $this->v($copro, ['representant_legal_type','type_syndic']) ?: ($syndic?'syndic professionnel':''),
            'siren_syndic'                => $sirenSynd ?: $this->v($syndic, ['siren']),
            'siret_syndic'                => $siretSynd ?: $this->v($syndic, ['siret']),
            'immatriculation_copro'       => $this->v($copro, ['numero_immatriculation']),
            'nom_residence'               => $this->v($copro, ['nom_copropriete','nom_usage_copropriete']),
            'nb_lots_habitation'          => $this->v($copro, ['nombre_lots_habitation']),
            'score_rnic'                  => $this->v($copro, ['score_match']),
            'type_batiment'               => $this->v($batiment, ['type_batiment']),
            'type_chauffage_principal'    => $this->v($batiment, ['type_chauffage','chauffage_principal']),
            'energie_chauffage_collectif' => $this->v($batiment, ['energie_chauffage','energie_principale_chauffage']),
            'annee_construction'          => $this->v($batiment, ['annee_construction']),
            'nb_logements'                => $this->v($batiment, ['nombre_logements']),
            'nb_niveaux'                  => $this->v($batiment, ['nombre_niveaux']),
            'hauteur'                     => $this->v($batiment, ['hauteur']),
            'surface_habitable'           => $this->v($batiment, ['surface_habitable']),
            'surface_emprise_sol'         => $this->v($batiment, ['surface_emprise_sol']),
            'classe_dpe'                  => $this->v($batiment, ['classe_dpe']),
            'ges'                         => $this->v($batiment, ['ges']),
            'nb_proprietaires'            => (string) count($proprios),
            'nb_coproprietes'             => (string) count($copros),
            'siren_copropriete'           => $this->v($copro, ['siren_copropriete']),
            'adresse_normalisee'          => $adresse?->adresse_complete ?? '',
            'code_postal'                 => $adresse?->code_postal      ?? '',
            'ville'                       => $adresse?->ville             ?? '',
            'code_insee'                  => $adresse?->code_insee        ?? '',
            'latitude'                    => $adresse?->latitude  !== null ? (string) $adresse->latitude  : '',
            'longitude'                   => $adresse?->longitude !== null ? (string) $adresse->longitude : '',
            'qpv_eligible'                => !($hasQp2024||$hasQp2015||$hasZfu) ? 'Éligible' : 'Non éligible',
            'qp_2024'                     => $hasQp2024 ? 'Oui' : 'Non',
            'qp_2015'                     => $hasQp2015 ? 'Oui' : 'Non',
            'zfu'                         => $hasZfu    ? 'Oui' : 'Non',
            'rnb_id'                      => $rnbId    ?? '',
            'rnb_statut'                  => $rnbStatut ?? '',
            'rnb_nb_adresses'             => (string) $rnbAddrs->count(),
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
    // HELPERS
    // ════════════════════════════════════════════════════════════
    private function getDateFinMandat(mixed $copro): ?string
    {
        if (!$copro) return null;
        $val = $this->v($copro, ['date_fin_dernier_mandat']);
        if ($val && $val !== '-') return $val;
        // Chercher dans raw_data imbriqué
        $raw = is_object($copro) ? ($copro->raw_data ?? []) : ($copro['raw_data'] ?? []);
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
        $rnic = $raw['raw_data'] ?? $raw;
        if (is_string($rnic)) $rnic = json_decode($rnic, true) ?: [];
        $val = $rnic['date_fin_dernier_mandat'] ?? null;
        return ($val && $val !== '-') ? $val : null;
    }

    private function fileToBase64Chunked(string $filePath): string
    {
        $handle = fopen($filePath, 'rb');
        $base64 = '';
        while (!feof($handle)) $base64 .= base64_encode(fread($handle, 1024 * 1024));
        fclose($handle);
        return $base64;
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
