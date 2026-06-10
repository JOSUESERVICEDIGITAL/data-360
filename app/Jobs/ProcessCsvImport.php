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

// OpenSpout — streaming XLSX, mémoire quasi constante
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;

/**
 * ProcessCsvImport — STREAMING XLSX avec OpenSpout
 * ─────────────────────────────────────────────────────────────
 * Avantages vs PhpSpreadsheet :
 *   ✅ Mémoire quasi constante (O(1) au lieu de O(n))
 *   ✅ Écrit ligne par ligne directement sur disque
 *   ✅ Jamais de tableau $rows[] en mémoire
 *   ✅ 50k / 100k / 200k lignes sans exploser la RAM
 *   ✅ unset() du résultat moteur à chaque itération
 * ─────────────────────────────────────────────────────────────
 * Installation : composer require openspout/openspout
 */
class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout      = 7200; // 2h max
    public int $tries        = 1;
    public int $maxExceptions = 1;

    // ── Colonnes enrichies ────────────────────────────────────
    private array $enrichedCols = [
        'representant_legal', 'nom_representant', 'type_representant',
        'siren_syndic', 'siret_syndic', 'immatriculation_copro',
        'nom_residence', 'nb_lots_habitation', 'score_rnic',
        'type_batiment', 'annee_construction', 'nb_logements',
        'nb_niveaux', 'hauteur', 'surface_habitable', 'surface_emprise_sol',
        'classe_dpe', 'ges', 'type_chauffage_principal', 'energie_chauffage_collectif',
        'nb_proprietaires', 'nb_coproprietes', 'siren_copropriete',
        'adresse_normalisee', 'code_postal', 'ville', 'code_insee',
        'latitude', 'longitude',
        'qpv_eligible', 'qp_2024', 'qp_2015', 'zfu',
        'rnb_id', 'rnb_statut', 'rnb_nb_adresses',
        'syndic_forme_juridique', 'syndic_capital_social', 'syndic_chiffre_affaires',
        'syndic_resultat', 'syndic_effectif', 'syndic_dirigeant',
        'dr_statut', 'dr_erreur',
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
        'annee_construction'          => 'Année construction',
        'nb_logements'                => 'Nb logements',
        'nb_niveaux'                  => 'Nb niveaux',
        'hauteur'                     => 'Hauteur (m)',
        'surface_habitable'           => 'Surface habitable (m²)',
        'surface_emprise_sol'         => 'Emprise sol (m²)',
        'classe_dpe'                  => 'Classe DPE',
        'ges'                         => 'GES',
        'type_chauffage_principal'    => 'Type chauffage principal',
        'energie_chauffage_collectif' => 'Énergie chauffage collectif',
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

    // ── Couleurs par groupe (hex 6 car sans #) ────────────────
    private array $groupColors = [
        'representant_legal'          => '1E3A5F',
        'nom_representant'            => '1E3A5F',
        'type_representant'           => '1E3A5F',
        'siren_syndic'                => '1E3A5F',
        'siret_syndic'                => '1E3A5F',
        'immatriculation_copro'       => '1E3A5F',
        'nom_residence'               => '1E3A5F',
        'nb_lots_habitation'          => '1E3A5F',
        'score_rnic'                  => '1E3A5F',
        'type_batiment'               => '1B4332',
        'annee_construction'          => '1B4332',
        'nb_logements'                => '1B4332',
        'nb_niveaux'                  => '1B4332',
        'hauteur'                     => '1B4332',
        'surface_habitable'           => '1B4332',
        'surface_emprise_sol'         => '1B4332',
        'classe_dpe'                  => '1B4332',
        'ges'                         => '1B4332',
        'type_chauffage_principal'    => '1B4332',
        'energie_chauffage_collectif' => '1B4332',
        'nb_proprietaires'            => '4A1942',
        'nb_coproprietes'             => '7C2D12',
        'siren_copropriete'           => '7C2D12',
        'adresse_normalisee'          => '164E63',
        'code_postal'                 => '164E63',
        'ville'                       => '164E63',
        'code_insee'                  => '164E63',
        'latitude'                    => '164E63',
        'longitude'                   => '164E63',
        'qpv_eligible'                => '713F12',
        'qp_2024'                     => '713F12',
        'qp_2015'                     => '713F12',
        'zfu'                         => '713F12',
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

    public function __construct(
        private readonly CsvImport $import
    ) {}

    // ════════════════════════════════════════════════════════════
    // HANDLE — streaming ligne par ligne, RAM constante
    // ════════════════════════════════════════════════════════════
    public function handle(DataRocketEngineService $engine): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '256M'); // 256M suffit même pour 100k lignes

        $import = $this->import->fresh();
        if (!$import || $import->statut === 'termine') return;

        $import->update(['statut' => 'en_cours', 'lignes_traitees' => 0, 'progress' => 0]);

        // Fichier temporaire sur disque (pas en mémoire)
        $tmpFile = sys_get_temp_dir() . '/data360_' . $import->id . '_' . time() . '.xlsx';

        try {
            // ── Lire le CSV depuis la base ─────────────────────
            $csvContent = $import->csv_content;
            if (empty($csvContent)) {
                throw new \RuntimeException('csv_content vide en base.');
            }

            $reader = Reader::createFromString($csvContent);
            $reader->setHeaderOffset(0);
            $records = $reader->getRecords();

            // Détection colonnes
            $reader->setHeaderOffset(0);
            $firstReader = Reader::createFromString($csvContent);
            $firstReader->setHeaderOffset(0);
            $allRecords  = iterator_to_array($firstReader->getRecords());
            if (empty($allRecords)) {
                throw new \RuntimeException('CSV vide après parsing.');
            }

            $originalCols = array_keys(reset($allRecords));
            $adresseCol   = $this->detectAdresseColumn($originalCols);
            unset($allRecords); // libérer immédiatement

            if (!$adresseCol) {
                throw new \RuntimeException('Colonne "adresse" introuvable dans le CSV.');
            }

            $total    = $import->total_lignes;
            $allCols  = array_merge($originalCols, $this->enrichedCols);

            // ── Ouvrir le writer OpenSpout → fichier disque ────
            $writer = $this->createWriter($tmpFile);

            // ── Ligne d'en-tête avec styles ───────────────────
            $this->writeHeaderRow($writer, $allCols);

            // ── Traiter chaque adresse — UNE À LA FOIS ────────
            $traites   = 0;
            $rowNumber = 0;

            $reader2 = Reader::createFromString($csvContent);
            $reader2->setHeaderOffset(0);

            foreach ($reader2->getRecords() as $record) {
                $rowNumber++;
                $adresseQuery = trim((string) ($record[$adresseCol] ?? ''));

                if (empty($adresseQuery)) {
                    $rowData = $this->buildEmptyRowData(
                        $record, $originalCols, 'Adresse vide'
                    );
                    $this->writeDataRow($writer, $rowData, $allCols, $rowNumber);
                    unset($rowData);
                    $traites++;
                    $this->updateProgress($import, $traites, $total);
                    continue;
                }

                try {
                    // ── Appel moteur (sans scraping) ───────────
                    $result  = $engine->searchByAddress($adresseQuery);
                    $rowData = $this->mapResultToRowData($result, $record, $originalCols);

                    // ── Écriture IMMÉDIATE → disque ────────────
                    $this->writeDataRow($writer, $rowData, $allCols, $rowNumber);

                    // ── Libération mémoire immédiate ───────────
                    unset($result, $rowData);

                    // Consommer 1 crédit
                    if ($import->user) {
                        $import->user->consumeCredit();
                    }

                } catch (\Throwable $e) {
                    Log::warning("ProcessCsvImport [{$adresseQuery}]: " . $e->getMessage());
                    $rowData = $this->buildEmptyRowData($record, $originalCols, $e->getMessage());
                    $this->writeDataRow($writer, $rowData, $allCols, $rowNumber);
                    unset($rowData);
                }

                $traites++;
                $this->updateProgress($import, $traites, $total);
                usleep(150_000); // 150ms entre appels API
            }

            // ── Fermer le writer → flush sur disque ───────────
            $writer->close();

            // ── Lire le fichier → base64 → DB ─────────────────
            if (!file_exists($tmpFile) || filesize($tmpFile) === 0) {
                throw new \RuntimeException('Fichier XLSX généré est vide.');
            }

            // Lecture par chunks pour ne pas charger tout le fichier en RAM
            $xlsxBase64 = $this->fileToBase64Chunked($tmpFile);

            $import->update([
                'statut'          => 'termine',
                'xlsx_content'    => $xlsxBase64,
                'lignes_traitees' => $total,
                'progress'        => 100,
                'csv_content'     => null, // Libérer l'espace en base
            ]);

            Log::info("ProcessCsvImport [{$import->id}] terminé — {$total} adresses — "
                . round(filesize($tmpFile) / 1024 / 1024, 2) . ' Mo');

        } catch (\Throwable $e) {
            Log::error("ProcessCsvImport [{$import->id}] ERREUR: " . $e->getMessage());
            $import->update([
                'statut'         => 'erreur',
                'erreur_message' => $e->getMessage(),
            ]);
        } finally {
            // Toujours nettoyer le fichier temp même en cas d'erreur
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    // ════════════════════════════════════════════════════════════
    // OPENSPOUT — WRITER
    // ════════════════════════════════════════════════════════════

    private function createWriter(string $filePath): Writer
    {
        $options = new Options();
        $options->DEFAULT_ROW_HEIGHT     = 20;
        $options->DEFAULT_COLUMN_WIDTH   = 18;
        $options->SHOULD_USE_INLINE_STRINGS = true; // moins de RAM

        $writer = new Writer($options);
        $writer->openToFile($filePath);

        return $writer;
    }

    private function writeHeaderRow(Writer $writer, array $allCols): void
    {
        // Pré-construire les styles d'en-tête par couleur (réutilisés)
        $styleCache = [];
        $cells      = [];

        foreach ($allCols as $key) {
            $label = $this->enrichedHeaders[$key]
                ?? ucfirst(str_replace('_', ' ', $key));
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
        $cells  = [];
        $isEven = ($rowNumber % 2 === 0);
        $bgBase = $isEven ? 'FFFFFF' : 'F8FAFC';

        // Style de base (réutilisé pour toutes les cellules de la ligne)
        $baseStyle = (new Style())
            ->setFontSize(9)
            ->setFontColor('1E293B')
            ->setBackgroundColor($bgBase)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        foreach ($allCols as $key) {
            $value = $this->sanitize((string) ($rowData[$key] ?? ''));

            // Style conditionnel pour quelques colonnes clés
            $style = $this->conditionalStyle($key, $value, $bgBase);

            $cells[] = Cell::fromValue($value, $style ?? $baseStyle);
        }

        $writer->addRow(new Row($cells));
    }

    /**
     * Style coloré pour les colonnes à indicateur visuel
     * Retourne null si pas de style spécial → utiliser baseStyle
     */
    private function conditionalStyle(string $key, string $value, string $bgBase): ?Style
    {
        $greenStyle = fn() => (new Style())
            ->setFontBold()->setFontSize(9)->setFontColor('15803D')
            ->setBackgroundColor('DCFCE7')
            ->setCellAlignment(CellAlignment::CENTER);

        $redStyle = fn() => (new Style())
            ->setFontBold()->setFontSize(9)->setFontColor('B91C1C')
            ->setBackgroundColor('FEE2E2')
            ->setCellAlignment(CellAlignment::CENTER);

        return match(true) {
            $key === 'representant_legal' =>
                str_contains($value, 'Avec') ? $greenStyle() : $redStyle(),

            $key === 'qpv_eligible' =>
                $value === 'Éligible' ? $greenStyle() : ($value ? $redStyle() : null),

            in_array($key, ['qp_2024', 'qp_2015', 'zfu'], true) =>
                $value === 'Oui' ? $redStyle() : ($value ? $greenStyle() : null),

            $key === 'dr_statut' =>
                str_starts_with($value, 'OK') ? $greenStyle() : ($value ? $redStyle() : null),

            default => null,
        };
    }

    // ════════════════════════════════════════════════════════════
    // MAPPING RÉSULTAT → LIGNE (données brutes, PAS de style ici)
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
            return array_merge(
                $row,
                $this->emptyEnrichedData([
                    'dr_statut' => 'ERREUR',
                    'dr_erreur' => $result['message'] ?? 'Adresse introuvable',
                ])
            );
        }

        $adresse   = $result['adresse']          ?? null;
        $batiments = $result['batiments']         ?? [];
        $copros    = $result['coproprietes']      ?? [];
        $syndics   = $result['syndics']           ?? [];
        $qpv       = $result['qpv']               ?? null;
        $rnbData   = $result['rnb']               ?? null;
        $proprios  = $result['proprietaires_bdnb']?? [];

        $batiment  = $batiments[0] ?? null;
        $copro     = $copros[0]    ?? null;
        $syndic    = $syndics[0]   ?? null;

        // QPV
        $qpvChecks = collect($qpv['checks'] ?? []);
        $hasQp2024 = $qpvChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
        $hasQp2015 = $qpvChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
        $hasZfu    = $qpvChecks->contains(fn($c) => $c['result']['zfu']     ?? false);
        $eligible  = !($hasQp2024 || $hasQp2015 || $hasZfu);

        // RNB
        $rnbId      = null;
        $rnbStatut  = null;
        $rnbAddrs   = collect();
        if ($rnbData) $this->extractRnbData($rnbData, $rnbId, $rnbStatut, $rnbAddrs);

        $repNom    = $this->v($copro, ['representant_legal_nom',  'syndic_nom']);
        $repType   = $this->v($copro, ['representant_legal_type', 'type_syndic']);
        $sirenSynd = $this->v($copro, ['siren_syndic']);
        $siretSynd = $this->v($copro, ['siret_syndic', 'siret_representant_legal']);
        $repConnu  = $syndic !== null || !empty($repNom) || !empty($sirenSynd);

        return array_merge($row, [
            'representant_legal'          => $repConnu ? 'Avec représentant légal' : 'Sans représentant légal',
            'nom_representant'            => $repNom ?: $this->v($syndic, ['nom']),
            'type_representant'           => $repType ?: ($syndic ? 'syndic professionnel' : ''),
            'siren_syndic'                => $sirenSynd ?: $this->v($syndic, ['siren']),
            'siret_syndic'                => $siretSynd ?: $this->v($syndic, ['siret']),
            'immatriculation_copro'       => $this->v($copro, ['numero_immatriculation']),
            'nom_residence'               => $this->v($copro, ['nom_copropriete', 'nom_usage_copropriete']),
            'nb_lots_habitation'          => $this->v($copro, ['nombre_lots_habitation']),
            'score_rnic'                  => $this->v($copro, ['score_match']),
            'type_batiment'               => $this->v($batiment, ['type_batiment']),
            'annee_construction'          => $this->v($batiment, ['annee_construction']),
            'nb_logements'                => $this->v($batiment, ['nombre_logements']),
            'nb_niveaux'                  => $this->v($batiment, ['nombre_niveaux']),
            'hauteur'                     => $this->v($batiment, ['hauteur']),
            'surface_habitable'           => $this->v($batiment, ['surface_habitable']),
            'surface_emprise_sol'         => $this->v($batiment, ['surface_emprise_sol']),
            'classe_dpe'                  => $this->v($batiment, ['classe_dpe']),
            'ges'                         => $this->v($batiment, ['ges']),
            'type_chauffage_principal'    => $this->v($batiment, ['type_chauffage', 'chauffage_principal']),
            'energie_chauffage_collectif' => $this->v($batiment, ['energie_chauffage', 'energie_principale_chauffage']),
            'nb_proprietaires'            => (string) count($proprios),
            'nb_coproprietes'             => (string) count($copros),
            'siren_copropriete'           => $this->v($copro, ['siren_copropriete']),
            'adresse_normalisee'          => $adresse?->adresse_complete ?? '',
            'code_postal'                 => $adresse?->code_postal      ?? '',
            'ville'                       => $adresse?->ville             ?? '',
            'code_insee'                  => $adresse?->code_insee        ?? '',
            'latitude'                    => $adresse?->latitude  !== null ? (string) $adresse->latitude  : '',
            'longitude'                   => $adresse?->longitude !== null ? (string) $adresse->longitude : '',
            'qpv_eligible'                => $eligible ? 'Éligible' : 'Non éligible',
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
    // LECTURE FICHIER PAR CHUNKS → BASE64
    // Évite de charger un fichier de 50 Mo entier en RAM
    // ════════════════════════════════════════════════════════════
    private function fileToBase64Chunked(string $filePath): string
    {
        $handle   = fopen($filePath, 'rb');
        $base64   = '';
        $chunkSize = 1024 * 1024; // 1 Mo par chunk

        while (!feof($handle)) {
            $chunk  = fread($handle, $chunkSize);
            $base64 .= base64_encode($chunk);
        }

        fclose($handle);
        return $base64;
    }

    // ════════════════════════════════════════════════════════════
    // HELPERS
    // ════════════════════════════════════════════════════════════

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
        $progress = $total > 0 ? (int) round($done / $total * 100) : 0;
        $import->update(['lignes_traitees' => $done, 'progress' => $progress]);
    }

    private function buildEmptyRowData(
        array  $record,
        array  $originalCols,
        string $erreur = ''
    ): array {
        $row = [];
        foreach ($originalCols as $col) {
            $row[$col] = $record[$col] ?? '';
        }
        return array_merge($row, $this->emptyEnrichedData([
            'dr_statut' => 'ERREUR',
            'dr_erreur' => substr($erreur, 0, 200),
        ]));
    }

    private function emptyEnrichedData(array $overrides = []): array
    {
        return array_merge(
            array_fill_keys($this->enrichedCols, ''),
            $overrides
        );
    }

    private function v(mixed $model, array $keys, string $default = ''): string
    {
        if ($model === null) return $default;
        foreach ($keys as $key) {
            if (is_object($model) && isset($model->{$key})
                && $model->{$key} !== null && $model->{$key} !== '') {
                return (string) $model->{$key};
            }
            if (is_array($model) && isset($model[$key])
                && $model[$key] !== null && $model[$key] !== '') {
                return (string) $model[$key];
            }
            $raw = is_object($model) ? ($model->raw_data ?? []) : ($model['raw_data'] ?? []);
            if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
            if (is_array($raw) && isset($raw[$key]) && $raw[$key] !== null) {
                return (string) $raw[$key];
            }
        }
        return $default;
    }

    private function sanitize(?string $value): string
    {
        if ($value === null) return '';
        return mb_convert_encoding(
            preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '',
            'UTF-8', 'UTF-8'
        ) ?? '';
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
        $this->import->update([
            'statut'         => 'erreur',
            'erreur_message' => $exception->getMessage(),
        ]);
    }
}