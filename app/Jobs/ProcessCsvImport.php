<?php

namespace App\Jobs;

use App\Models\Back\CsvImport;
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
    // Représentant → Type bâtiment → Type chauffage → Énergie → reste
    // ════════════════════════════════════════════════════════════
    private array $enrichedCols = [
        // ── Bloc Représentant légal / RNIC ──
        'representant_legal',
        'nom_representant',
        'type_representant',
        'siren_syndic',
        'siret_syndic',
        'immatriculation_copro',
        'nom_residence',
        'nb_lots_habitation',
        'score_rnic',

        // ── Bloc Bâtiment — colonnes clés en PREMIER ──────────
        'type_batiment',           // ← juste après representant_legal group
        'type_chauffage_principal',// ← juste après type_batiment
        'energie_chauffage_collectif', // ← juste après type_chauffage

        // ── Reste des données bâtiment ──
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
        'representant_legal'          => 'Représentant légal',
        'nom_representant'            => 'Nom représentant / Syndic',
        'type_representant'           => 'Type représentant',
        'siren_syndic'                => 'SIREN Syndic',
        'siret_syndic'                => 'SIRET Syndic',
        'immatriculation_copro'       => 'N° Immatriculation',
        'nom_residence'               => 'Nom résidence',
        'nb_lots_habitation'          => 'Lots habitation',
        'score_rnic'                  => 'Score RNIC',
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
        'qpv_eligible'                => 'QPV Éligible',
        'qp_2024'                     => 'QP 2024',
        'qp_2015'                     => 'QP 2015',
        'zfu'                         => 'ZFU',
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
            if (empty($csvContent)) {
                throw new \RuntimeException('csv_content vide en base.');
            }

            // Détecter les colonnes originales
            $firstReader  = Reader::createFromString($csvContent);
            $firstReader->setHeaderOffset(0);
            $allRecords   = iterator_to_array($firstReader->getRecords());
            if (empty($allRecords)) throw new \RuntimeException('CSV vide après parsing.');

            $originalCols = array_keys(reset($allRecords));
            $adresseCol   = $this->detectAdresseColumn($originalCols);
            unset($allRecords);

            if (!$adresseCol) throw new \RuntimeException('Colonne "adresse" introuvable dans le CSV.');

            $total   = $import->total_lignes;
            $allCols = array_merge($originalCols, $this->enrichedCols);

            // Ouvrir le writer OpenSpout
            $writer = $this->createWriter($tmpFile);
            $this->writeHeaderRow($writer, $allCols);

            $traites   = 0;
            $rowNumber = 0;

            $reader = Reader::createFromString($csvContent);
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $record) {
                $rowNumber++;
                $adresseQuery = trim((string) ($record[$adresseCol] ?? ''));

                if (empty($adresseQuery)) {
                    $rowData = $this->buildEmptyRowData($record, $originalCols, 'Adresse vide');
                    $this->writeDataRow($writer, $rowData, $allCols, $rowNumber);
                    unset($rowData);
                } else {
                    try {
                        $result  = $engine->searchByAddress($adresseQuery);
                        $rowData = $this->mapResultToRowData($result, $record, $originalCols);
                        $this->writeDataRow($writer, $rowData, $allCols, $rowNumber);
                        unset($result, $rowData);

                        if ($import->user) $import->user->consumeCredit();

                    } catch (\Throwable $e) {
                        Log::warning("ProcessCsvImport [{$adresseQuery}]: " . $e->getMessage());
                        $rowData = $this->buildEmptyRowData($record, $originalCols, $e->getMessage());
                        $this->writeDataRow($writer, $rowData, $allCols, $rowNumber);
                        unset($rowData);
                    }
                }

                $traites++;
                $this->updateProgress($import, $traites, $total);
                usleep(150_000);
            }

            $writer->close();

            if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
                throw new \RuntimeException('Fichier XLSX généré est vide.');
            }

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
                    ->setFontBold()
                    ->setFontColor('FFFFFF')
                    ->setFontSize(9)
                    ->setBackgroundColor($bg)
                    ->setCellAlignment(CellAlignment::CENTER)
                    ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                    ->setShouldWrapText(true);
            }

            $cells[] = Cell::fromValue($label, $styleCache[$bg]);
        }

        $writer->addRow(new Row($cells));
    }

    private function writeDataRow(
        Writer $writer,
        array  $rowData,
        array  $allCols,
        int    $rowNumber
    ): void {
        $cells   = [];
        $bgBase  = ($rowNumber % 2 === 0) ? 'FFFFFF' : 'F8FAFC';

        $baseStyle = (new Style())
            ->setFontSize(9)
            ->setFontColor('1E293B')
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

        $green = (new Style())
            ->setFontBold()->setFontSize(9)->setFontColor('15803D')
            ->setBackgroundColor('DCFCE7')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $red = (new Style())
            ->setFontBold()->setFontSize(9)->setFontColor('B91C1C')
            ->setBackgroundColor('FEE2E2')
            ->setCellAlignment(CellAlignment::CENTER)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $lower = mb_strtolower(trim($value));

        return match($key) {

            // ── Représentant légal ────────────────────────────
            // Avec représentant = vert, Sans = rouge
            'representant_legal' =>
                str_contains($lower, 'avec') ? $green : $red,

            // ── Type bâtiment ─────────────────────────────────
            // collectif = ROUGE / autre = VERT
            'type_batiment' =>
                str_contains($lower, 'collectif') ? $red : $green,

            // ── Type chauffage ────────────────────────────────
            // collectif = ROUGE / autre = VERT
            'type_chauffage_principal' =>
                str_contains($lower, 'collectif') ? $red : $green,

            // ── Énergie de chauffage ──────────────────────────
            // électrique = VERT / autre = ROUGE
            'energie_chauffage_collectif' =>
                (str_contains($lower, 'electr') || str_contains($lower, 'électr'))
                    ? $green
                    : $red,

            // ── QPV éligibilité ───────────────────────────────
            'qpv_eligible' =>
                $lower === 'éligible' ? $green : $red,

            // ── Zones QPV / ZFU ───────────────────────────────
            'qp_2024', 'qp_2015', 'zfu' =>
                $lower === 'oui' ? $red : $green,

            // ── Statut traitement ─────────────────────────────
            'dr_statut' =>
                str_starts_with($lower, 'ok') ? $green : $red,

            default => null,
        };
    }

    // ════════════════════════════════════════════════════════════
    // MAPPING RÉSULTAT → LIGNE
    // ════════════════════════════════════════════════════════════
    private function mapResultToRowData(
        array $result,
        array $record,
        array $originalCols
    ): array {
        $row = [];
        foreach ($originalCols as $col) {
            $row[$col] = $record[$col] ?? '';
        }

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

        // QPV
        $qpvChecks = collect($qpv['checks'] ?? []);
        $hasQp2024 = $qpvChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
        $hasQp2015 = $qpvChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
        $hasZfu    = $qpvChecks->contains(fn($c) => $c['result']['zfu']     ?? false);

        // RNB
        $rnbId = null; $rnbStatut = null; $rnbAddrs = collect();
        if ($rnbData) $this->extractRnbData($rnbData, $rnbId, $rnbStatut, $rnbAddrs);

        $repNom    = $this->v($copro, ['representant_legal_nom', 'syndic_nom']);
        $repType   = $this->v($copro, ['representant_legal_type', 'type_syndic']);
        $sirenSynd = $this->v($copro, ['siren_syndic']);
        $siretSynd = $this->v($copro, ['siret_syndic', 'siret_representant_legal']);
        $repConnu  = $syndic !== null || !empty($repNom) || !empty($sirenSynd);

        return array_merge($row, [
            // ── Représentant ──
            'representant_legal'          => $repConnu ? 'Avec représentant légal' : 'Sans représentant légal',
            'nom_representant'            => $repNom ?: $this->v($syndic, ['nom']),
            'type_representant'           => $repType ?: ($syndic ? 'syndic professionnel' : ''),
            'siren_syndic'                => $sirenSynd ?: $this->v($syndic, ['siren']),
            'siret_syndic'                => $siretSynd ?: $this->v($syndic, ['siret']),
            'immatriculation_copro'       => $this->v($copro, ['numero_immatriculation']),
            'nom_residence'               => $this->v($copro, ['nom_copropriete', 'nom_usage_copropriete']),
            'nb_lots_habitation'          => $this->v($copro, ['nombre_lots_habitation']),
            'score_rnic'                  => $this->v($copro, ['score_match']),

            // ── Bâtiment — colonnes clés ──
            'type_batiment'               => $this->v($batiment, ['type_batiment']),
            'type_chauffage_principal'    => $this->v($batiment, ['type_chauffage', 'chauffage_principal']),
            'energie_chauffage_collectif' => $this->v($batiment, ['energie_chauffage', 'energie_principale_chauffage']),

            // ── Bâtiment — reste ──
            'annee_construction'          => $this->v($batiment, ['annee_construction']),
            'nb_logements'                => $this->v($batiment, ['nombre_logements']),
            'nb_niveaux'                  => $this->v($batiment, ['nombre_niveaux']),
            'hauteur'                     => $this->v($batiment, ['hauteur']),
            'surface_habitable'           => $this->v($batiment, ['surface_habitable']),
            'surface_emprise_sol'         => $this->v($batiment, ['surface_emprise_sol']),
            'classe_dpe'                  => $this->v($batiment, ['classe_dpe']),
            'ges'                         => $this->v($batiment, ['ges']),

            // ── Propriétaires ──
            'nb_proprietaires'            => (string) count($proprios),
            'nb_coproprietes'             => (string) count($copros),
            'siren_copropriete'           => $this->v($copro, ['siren_copropriete']),

            // ── Adresse ──
            'adresse_normalisee'          => $adresse?->adresse_complete ?? '',
            'code_postal'                 => $adresse?->code_postal      ?? '',
            'ville'                       => $adresse?->ville             ?? '',
            'code_insee'                  => $adresse?->code_insee        ?? '',
            'latitude'                    => $adresse?->latitude  !== null ? (string) $adresse->latitude  : '',
            'longitude'                   => $adresse?->longitude !== null ? (string) $adresse->longitude : '',

            // ── QPV / ZFU ──
            'qpv_eligible'                => !($hasQp2024 || $hasQp2015 || $hasZfu) ? 'Éligible' : 'Non éligible',
            'qp_2024'                     => $hasQp2024 ? 'Oui' : 'Non',
            'qp_2015'                     => $hasQp2015 ? 'Oui' : 'Non',
            'zfu'                         => $hasZfu    ? 'Oui' : 'Non',

            // ── RNB ──
            'rnb_id'                      => $rnbId    ?? '',
            'rnb_statut'                  => $rnbStatut ?? '',
            'rnb_nb_adresses'             => (string) $rnbAddrs->count(),

            // ── Syndic ──
            'syndic_forme_juridique'      => $this->v($syndic, ['forme_juridique']),
            'syndic_capital_social'       => $this->v($syndic, ['capital_social']),
            'syndic_chiffre_affaires'     => $this->v($syndic, ['chiffre_affaires']),
            'syndic_resultat'             => $this->v($syndic, ['resultat']),
            'syndic_effectif'             => $this->v($syndic, ['effectif']),
            'syndic_dirigeant'            => $this->v($syndic, ['dirigeant_principal']),

            // ── Statut ──
            'dr_statut'                   => 'OK — ' . ($result['message'] ?? 'Enrichi'),
            'dr_erreur'                   => '',
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════

    private function fileToBase64Chunked(string $filePath): string
    {
        $handle    = fopen($filePath, 'rb');
        $base64    = '';
        $chunkSize = 1024 * 1024; // 1 Mo
        while (!feof($handle)) {
            $base64 .= base64_encode(fread($handle, $chunkSize));
        }
        fclose($handle);
        return $base64;
    }

    private function detectAdresseColumn(array $cols): ?string
    {
        foreach ($cols as $col) {
            if (in_array(strtolower(trim($col)), ['adresse', 'address', 'adresse_complete'], true)) {
                return $col;
            }
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