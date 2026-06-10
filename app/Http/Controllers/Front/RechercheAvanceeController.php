<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Back\CsvImport;
use App\Jobs\ProcessCsvImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RechercheAvanceeController extends Controller
{
    // ─── Mots-clés bâtiments collectifs ────────────────────────
    private array $collectifKeywords = [
        'collectif',
        'collective',
        'immeuble',
        'copropriete',
        'copropriété',
        'résidence',
        'residence',
        'appartement',
        'appartements',
    ];

    // ─── Colonnes enrichies à ajouter au CSV ──────────────────
    private array $enrichedCols = [
        'representant_legal',
        'nom_representant',
        'type_representant',
        'siren_syndic',
        'siret_syndic',
        'immatriculation_copro',
        'nom_residence',
        'nb_lots_habitation',
        'score_rnic',
        'type_batiment',
        'annee_construction',
        'nb_logements',
        'nb_niveaux',
        'hauteur',
        'surface_habitable',
        'surface_emprise_sol',
        'classe_dpe',
        'ges',
        'type_chauffage_principal',
        'energie_chauffage_collectif',
        'nb_proprietaires',
        'nb_coproprietes',
        'siren_copropriete',
        'adresse_normalisee',
        'code_postal',
        'ville',
        'code_insee',
        'latitude',
        'longitude',
        'qpv_eligible',
        'qp_2024',
        'qp_2015',
        'zfu',
        'rnb_id',
        'rnb_statut',
        'rnb_nb_adresses',
        'syndic_forme_juridique',
        'syndic_capital_social',
        'syndic_chiffre_affaires',
        'syndic_resultat',
        'syndic_effectif',
        'syndic_dirigeant',
        'dr_statut',
        'dr_erreur',
    ];

    // ─── Labels des colonnes enrichies ────────────────────────
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

    // ─── Couleurs par groupe de colonnes ──────────────────────
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

    // ─── Largeurs de colonnes ─────────────────────────────────
    private array $colWidths = [
        'adresse'                     => 40,
        'representant_legal'          => 22,
        'nom_representant'            => 30,
        'type_representant'           => 22,
        'siren_syndic'                => 14,
        'siret_syndic'                => 18,
        'immatriculation_copro'       => 20,
        'nom_residence'               => 28,
        'nb_lots_habitation'          => 14,
        'score_rnic'                  => 12,
        'type_batiment'               => 20,
        'annee_construction'          => 14,
        'nb_logements'                => 13,
        'nb_niveaux'                  => 11,
        'hauteur'                     => 11,
        'surface_habitable'           => 18,
        'surface_emprise_sol'         => 16,
        'classe_dpe'                  => 11,
        'ges'                         => 10,
        'type_chauffage_principal'    => 24,
        'energie_chauffage_collectif' => 26,
        'nb_proprietaires'            => 16,
        'nb_coproprietes'             => 14,
        'siren_copropriete'           => 16,
        'adresse_normalisee'          => 38,
        'code_postal'                 => 13,
        'ville'                       => 18,
        'code_insee'                  => 13,
        'latitude'                    => 13,
        'longitude'                   => 13,
        'qpv_eligible'                => 16,
        'qp_2024'                     => 11,
        'qp_2015'                     => 11,
        'zfu'                         => 10,
        'rnb_id'                      => 20,
        'rnb_statut'                  => 14,
        'rnb_nb_adresses'             => 16,
        'syndic_forme_juridique'      => 22,
        'syndic_capital_social'       => 16,
        'syndic_chiffre_affaires'     => 20,
        'syndic_resultat'             => 14,
        'syndic_effectif'             => 12,
        'syndic_dirigeant'            => 26,
        'dr_statut'                   => 16,
        'dr_erreur'                   => 30,
    ];

    // ════════════════════════════════════════════════════════════
    // MODÈLE CSV VIDE
    // ════════════════════════════════════════════════════════════
    public function csvTemplate()
    {
        $csv = "adresse\n"
            . "\"10 rue de la Paix, 75001 Paris\"\n"
            . "\"5 avenue Victor Hugo, 69002 Lyon\"\n"
            . "\"3 place Bellecour, 69002 Lyon\"";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele-data360.csv"',
        ]);
    }

    // ════════════════════════════════════════════════════════════
    // IMPORT CSV → STOCKAGE BASE → JOB ASYNCHRONE
    // ════════════════════════════════════════════════════════════
    public function csvImport(Request $request)
    {
        // ── Guard plan premium ──────────────────────────────────
        if (!Auth::check() || !in_array(Auth::user()->plan, ['premium', 'enterprise'])) {
            abort(403, 'Accès réservé aux comptes Premium et Enterprise.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        // ── Lecture + normalisation UTF-8 ──────────────────────
        $csvContent = file_get_contents($request->file('csv_file')->getPathname());

        $encoding = mb_detect_encoding(
            $csvContent,
            ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-15'],
            true
        );
        if ($encoding && $encoding !== 'UTF-8') {
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
        }
        // Supprimer BOM UTF-8
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);

        // ── Compter les lignes ─────────────────────────────────
        $reader = Reader::createFromString($csvContent);
        $reader->setHeaderOffset(0);
        $records = iterator_to_array($reader->getRecords());
        $total   = count($records);

        if ($total === 0) {
            return back()->withErrors(['csv_file' => 'Le fichier CSV est vide ou mal formaté.']);
        }

        if ($total > 500) {
            return back()->withErrors(['csv_file' => 'Maximum 500 adresses par import.']);
        }

        // ── Vérifier que la colonne "adresse" existe ───────────
        $firstRecord = reset($records);
        if (!isset($firstRecord['adresse']) && !isset($firstRecord['Adresse'])) {
            return back()->withErrors([
                'csv_file' => 'Le fichier doit contenir une colonne "adresse". Téléchargez le modèle CSV.'
            ]);
        }

        // ── Vérifier les crédits ───────────────────────────────
        $user = Auth::user();
        if ($user->credits < $total) {
            return back()->withErrors([
                'csv_file' => "Crédits insuffisants. Il vous faut {$total} crédits, vous en avez {$user->credits}."
            ]);
        }

        // ── Créer l'entrée CsvImport ───────────────────────────
        $import = CsvImport::create([
            'user_id'           => Auth::id(),
            'filename_original' => $request->file('csv_file')->getClientOriginalName(),
            'csv_content'       => $csvContent,
            'total_lignes'      => $total,
            'statut'            => 'en_attente',
            'lignes_traitees'   => 0,
            'progress'          => 0,
        ]);

        // ── Dispatcher le job asynchrone ───────────────────────
        ProcessCsvImport::dispatch($import);

        return redirect()
            ->route('front.csv.suivi', $import->id)
            ->with('success', "Import lancé — {$total} adresses en cours de traitement.");
    }

    // ════════════════════════════════════════════════════════════
    // PAGE DE SUIVI
    // ════════════════════════════════════════════════════════════
    public function suivi(CsvImport $import)
    {
        // Sécurité : seul le propriétaire peut voir le suivi
        if ($import->user_id !== Auth::id()) {
            abort(403);
        }

        return view('front.csv.suivi', compact('import'));
    }

    // ════════════════════════════════════════════════════════════
    // PROGRESSION (polling AJAX)
    // ════════════════════════════════════════════════════════════
    public function progress(CsvImport $import)
    {
        if ($import->user_id !== Auth::id()) {
            abort(403);
        }

        $import->refresh(); // Récupérer les dernières valeurs

        return response()->json([
            'statut'          => $import->statut,
            'progress'        => (int) $import->progress,
            'lignes_traitees' => (int) $import->lignes_traitees,
            'total_lignes'    => (int) $import->total_lignes,
            'message'         => $this->progressMessage($import),
            'download_url'    => $import->statut === 'termine'
                ? route('front.csv.download', $import->id)
                : null,
        ]);
    }

    private function progressMessage(CsvImport $import): string
    {
        return match ($import->statut) {
            'en_attente' => 'En attente de traitement…',
            'en_cours'   => "Traitement en cours — {$import->lignes_traitees}/{$import->total_lignes} adresses",
            'termine'    => 'Traitement terminé ! Votre fichier XLSX est prêt.',
            'erreur'     => 'Une erreur est survenue. Veuillez réessayer.',
            default      => 'Statut inconnu.',
        };
    }

    // ════════════════════════════════════════════════════════════
    // TÉLÉCHARGEMENT XLSX
    // ════════════════════════════════════════════════════════════
    public function download(CsvImport $import)
    {
        if ($import->user_id !== Auth::id()) {
            abort(403);
        }

        if ($import->statut !== 'termine' || empty($import->xlsx_content)) {
            abort(404, 'Fichier non disponible. Le traitement est peut-être encore en cours.');
        }

        $filename = 'data360-enrichi-' . $import->created_at->format('Ymd-His') . '.xlsx';

        // ── Décoder le base64 et écrire dans un fichier temporaire ──
        $xlsxBinary = base64_decode($import->xlsx_content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'data360_xlsx_');
        file_put_contents($tmpFile, $xlsxBinary);
        unset($xlsxBinary); // libérer RAM immédiatement

        // ── Streamer depuis le fichier temp ──────────────────────────
        // deleteFileAfterSend(true) → Laravel supprime le fichier temp après envoi
        return response()->download($tmpFile, $filename, [
            'Content-Type'              => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control'             => 'no-cache, must-revalidate',
            'Pragma'                    => 'no-cache',
            'Expires'                   => '0',
        ])->deleteFileAfterSend(true);
    }
    // ════════════════════════════════════════════════════════════
    // CONSTRUCTION XLSX (appelé par ProcessCsvImport)
    // ════════════════════════════════════════════════════════════
    public function buildXlsx(array $originalCols, array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data360 Enrichi');

        $allCols     = array_merge($originalCols, $this->enrichedCols);
        $colIndexMap = [];
        foreach ($allCols as $i => $key) {
            $colIndexMap[$key] = $i + 1;
        }

        // ── En-têtes ──────────────────────────────────────────
        foreach ($allCols as $i => $key) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $cellRef   = $colLetter . '1';
            $label     = $this->enrichedHeaders[$key]
                ?? ucfirst(str_replace('_', ' ', $key));
            $bg        = $this->groupColors[$key] ?? '0F172A';

            $sheet->getCell($cellRef)->setValue($label);
            $sheet->getStyle($cellRef)->applyFromArray([
                'font'      => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 9,
                    'name'  => 'Arial',
                ],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bg],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(38);

        // ── Données ───────────────────────────────────────────
        foreach ($rows as $rowIdx => $rowData) {
            $excelRow = $rowIdx + 2;
            $baseBg   = ($rowIdx % 2 === 0) ? 'FFFFFF' : 'F8FAFC';

            foreach ($allCols as $i => $key) {
                $colLetter = Coordinate::stringFromColumnIndex($i + 1);
                $cellRef   = $colLetter . $excelRow;
                $value     = $this->sanitize((string) ($rowData[$key] ?? ''));

                $sheet->getCell($cellRef)->setValue($value);
                $sheet->getStyle($cellRef)->applyFromArray([
                    'font'      => [
                        'name'  => 'Arial',
                        'size'  => 9,
                        'color' => ['rgb' => '1E293B'],
                    ],
                    'fill'      => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $baseBg],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'E2E8F0'],
                        ],
                    ],
                ]);
            }

            // ── Coloration conditionnelle ──────────────────────
            $this->applyConditionalColors($sheet, $colIndexMap, $rowData, $excelRow);
            $sheet->getRowDimension($excelRow)->setRowHeight(22);
        }

        // ── Largeurs de colonnes ───────────────────────────────
        foreach ($allCols as $i => $key) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($colLetter)
                ->setWidth($this->colWidths[$key] ?? 16);
        }

        // ── Freeze + AutoFilter ───────────────────────────────
        $freezeCol = Coordinate::stringFromColumnIndex(count($originalCols) + 1);
        $sheet->freezePane($freezeCol . '2');

        $lastColLetter = Coordinate::stringFromColumnIndex(count($allCols));
        $sheet->setAutoFilter('A1:' . $lastColLetter . (count($rows) + 1));

        // ── Métadonnées ────────────────────────────────────────
        $spreadsheet->getProperties()
            ->setCreator('Data360')
            ->setLastModifiedBy('Data360')
            ->setTitle('Export Data360 — ' . now()->format('d/m/Y H:i'))
            ->setSubject('Enrichissement adresses françaises')
            ->setDescription('Export généré automatiquement par Data360 — sans scraping externe');

        return $spreadsheet;
    }

    // ─── Coloration conditionnelle des cellules clés ──────────
    private function applyConditionalColors(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        array $colIndexMap,
        array $rowData,
        int   $excelRow
    ): void {
        // Représentant légal
        if ($idx = ($colIndexMap['representant_legal'] ?? null)) {
            $this->colorCell(
                $sheet,
                $idx,
                $excelRow,
                str_contains($rowData['representant_legal'] ?? '', 'Avec')
            );
        }

        // Type bâtiment
        if ($idx = ($colIndexMap['type_batiment'] ?? null)) {
            $this->colorCell(
                $sheet,
                $idx,
                $excelRow,
                !$this->isCollectif($rowData['type_batiment'] ?? ''),
                true
            );
        }

        // Type chauffage
        if ($idx = ($colIndexMap['type_chauffage_principal'] ?? null)) {
            $this->colorCell(
                $sheet,
                $idx,
                $excelRow,
                !$this->isCollectif($rowData['type_chauffage_principal'] ?? ''),
                true
            );
        }

        // Énergie chauffage
        if ($idx = ($colIndexMap['energie_chauffage_collectif'] ?? null)) {
            $this->colorCell(
                $sheet,
                $idx,
                $excelRow,
                !$this->isCollectif($rowData['energie_chauffage_collectif'] ?? ''),
                true
            );
        }

        // Éligibilité QPV
        if ($idx = ($colIndexMap['qpv_eligible'] ?? null)) {
            $this->colorCell(
                $sheet,
                $idx,
                $excelRow,
                ($rowData['qpv_eligible'] ?? '') === 'Éligible'
            );
        }

        // QP 2024 / QP 2015 / ZFU
        foreach (['qp_2024', 'qp_2015', 'zfu'] as $zoneKey) {
            if ($idx = ($colIndexMap[$zoneKey] ?? null)) {
                $this->colorCell(
                    $sheet,
                    $idx,
                    $excelRow,
                    ($rowData[$zoneKey] ?? '') !== 'Oui'
                );
            }
        }

        // Statut traitement
        if ($idx = ($colIndexMap['dr_statut'] ?? null)) {
            $this->colorCell(
                $sheet,
                $idx,
                $excelRow,
                str_starts_with($rowData['dr_statut'] ?? '', 'OK')
            );
        }
    }

    private function colorCell(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int  $colIdx,
        int  $row,
        bool $isGood,
        bool $onlyIfNotEmpty = false
    ): void {
        $colLetter = Coordinate::stringFromColumnIndex($colIdx);
        $cellRef   = $colLetter . $row;

        if ($onlyIfNotEmpty && empty(trim((string) $sheet->getCell($cellRef)->getValue()))) {
            return;
        }

        $sheet->getStyle($cellRef)->applyFromArray([
            'font'      => [
                'bold'  => true,
                'name'  => 'Arial',
                'size'  => 9,
                'color' => ['rgb' => $isGood ? '15803D' : 'B91C1C'],
            ],
            'fill'      => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $isGood ? 'DCFCE7' : 'FEE2E2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function isCollectif(string $value): bool
    {
        if (empty($value)) return false;
        $lower = mb_strtolower(trim($value));
        foreach ($this->collectifKeywords as $kw) {
            if (str_contains($lower, $kw)) return true;
        }
        return false;
    }

    private function sanitize(?string $value): string
    {
        if ($value === null) return '';
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8') ?? '';
    }

    // ─── Helper lecture valeur depuis objet ou tableau ─────────
    private function val(mixed $model, array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            if (
                is_object($model) && isset($model->{$key})
                && $model->{$key} !== null && $model->{$key} !== ''
            ) {
                return (string) $model->{$key};
            }
            if (
                is_array($model) && isset($model[$key])
                && $model[$key] !== null && $model[$key] !== ''
            ) {
                return (string) $model[$key];
            }
            $raw = is_object($model) ? ($model->raw_data ?? []) : ($model['raw_data'] ?? []);
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }
            if (
                is_array($raw) && isset($raw[$key])
                && $raw[$key] !== null && $raw[$key] !== ''
            ) {
                return (string) $raw[$key];
            }
        }
        return $default;
    }

    // ─── Extraction données RNB récursive ─────────────────────
    private function extractRnb($data, &$rnbId, &$rnbStatut, &$addresses): void
    {
        if (!is_array($data)) return;

        if (isset($data['rnb_id']) && !$rnbId)     $rnbId     = $data['rnb_id'];
        if (isset($data['status']) && !$rnbStatut) $rnbStatut = $data['status'];

        if (isset($data['addresses']) && is_array($data['addresses'])) {
            foreach ($data['addresses'] as $addr) {
                $label = $addr['label']
                    ?? $addr['adresse']
                    ?? trim(collect([
                        $addr['street_number'] ?? null,
                        $addr['street_rep']    ?? null,
                        $addr['street']        ?? null,
                        $addr['city_zipcode']  ?? null,
                        $addr['city_name']     ?? null,
                    ])->filter()->implode(' '));

                if ($label) {
                    $addresses->push([
                        'adresse' => $label,
                        'cle_ban' => $addr['cle_interop_ban'] ?? $addr['cle_ban'] ?? null,
                        'id_ban'  => $addr['ban_id']          ?? $addr['id_ban']  ?? null,
                    ]);
                }
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $this->extractRnb($value, $rnbId, $rnbStatut, $addresses);
            }
        }
    }

    // ─── Getter public pour le job ─────────────────────────────
    public function getEnrichedCols(): array
    {
        return $this->enrichedCols;
    }
    public function getEnrichedHeaders(): array
    {
        return $this->enrichedHeaders;
    }
    public function getGroupColors(): array
    {
        return $this->groupColors;
    }
    public function getColWidths(): array
    {
        return $this->colWidths;
    }
}
