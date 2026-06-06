<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
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

    // ────────────────────────────────────────────────────────────
    // Modèle CSV vide
    // ────────────────────────────────────────────────────────────
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

    // ────────────────────────────────────────────────────────────
    // Import CSV → stockage en base → job asynchrone
    // ────────────────────────────────────────────────────────────
    public function csvImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        // 1. Lire le contenu brut du CSV uploadé
        $csvContent = file_get_contents($request->file('csv_file')->getPathname());

        // Convertir en UTF-8 si nécessaire
        $encoding = mb_detect_encoding($csvContent, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ISO-8859-15'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
        }
        // Supprimer le BOM UTF-8 si présent
        $csvContent = preg_replace('/^\xEF\xBB\xBF/', '', $csvContent);

        // 2. Compter les lignes
        $reader = Reader::createFromString($csvContent);
        $reader->setHeaderOffset(0);
        $total = count(iterator_to_array($reader->getRecords()));

        if ($total === 0) {
            return back()->withErrors(['csv_file' => 'Le fichier CSV est vide.']);
        }

        // 3. Créer l'entrée en base avec le contenu CSV
        $import = \App\Models\Back\CsvImport::create([
            'user_id'           => Auth::id(),
            'filename_original' => $request->file('csv_file')->getClientOriginalName(),
            'csv_content'       => $csvContent,  // ← stocké en base, pas sur disque
            'total_lignes'      => $total,
            'statut'            => 'en_attente',
        ]);

        // 4. Dispatcher le job — plus de chemin de fichier
        \App\Jobs\ProcessCsvImport::dispatch($import);

        // 5. Rediriger vers la page de suivi
        return redirect()->route('front.csv.suivi', $import->id)
            ->with('success', "Import lancé — {$total} adresses en cours de traitement.");
    }

    // ────────────────────────────────────────────────────────────
    // Page de suivi
    // ────────────────────────────────────────────────────────────
    public function suivi(\App\Models\Back\CsvImport $import)
    {
        return view('front.csv.suivi', compact('import'));
    }

    // ────────────────────────────────────────────────────────────
    // Endpoint de progression (polling JSON)
    // ────────────────────────────────────────────────────────────
    public function progress(\App\Models\Back\CsvImport $import)
    {
        return response()->json([
            'statut'          => $import->statut,
            'progress'        => $import->progress,
            'lignes_traitees' => $import->lignes_traitees,
            'total_lignes'    => $import->total_lignes,
            // URL de téléchargement via route dédiée
            'download_url'    => $import->statut === 'termine'
                ? route('front.csv.download', $import->id)
                : null,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Téléchargement du XLSX depuis la base
    // ────────────────────────────────────────────────────────────
    public function download(\App\Models\Back\CsvImport $import)
    {
        if ($import->statut !== 'termine' || empty($import->xlsx_content)) {
            abort(404, 'Fichier non disponible.');
        }

        $xlsxContent = base64_decode($import->xlsx_content);
        $filename    = 'data360-enrichi-' . $import->created_at->format('Ymd-His') . '.xlsx';

        return response($xlsxContent, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length'      => strlen($xlsxContent),
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Construction XLSX — syntaxe PhpSpreadsheet v2+
    // ────────────────────────────────────────────────────────────
    private function buildXlsx(array $originalCols, array $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data360 Enrichi');

        $allCols     = array_merge($originalCols, $this->enrichedCols);
        $colIndexMap = [];
        foreach ($allCols as $i => $key) {
            $colIndexMap[$key] = $i + 1;
        }

        // En-têtes
        foreach ($allCols as $i => $key) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $cellRef   = $colLetter . '1';
            $label     = $this->enrichedHeaders[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $bg        = $this->groupColors[$key] ?? '0F172A';

            $sheet->getCell($cellRef)->setValue($label);
            $sheet->getStyle($cellRef)->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9, 'name' => 'Arial'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }
        $sheet->getRowDimension(1)->setRowHeight(38);

        // Données
        foreach ($rows as $rowIdx => $rowData) {
            $excelRow = $rowIdx + 2;
            $baseBg   = ($rowIdx % 2 === 0) ? 'FFFFFF' : 'F8FAFC';

            foreach ($allCols as $i => $key) {
                $colLetter = Coordinate::stringFromColumnIndex($i + 1);
                $cellRef   = $colLetter . $excelRow;
                $value     = $this->sanitize((string) ($rowData[$key] ?? ''));

                $sheet->getCell($cellRef)->setValue($value);
                $sheet->getStyle($cellRef)->applyFromArray([
                    'font'      => ['name' => 'Arial', 'size' => 9, 'color' => ['rgb' => '1E293B']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $baseBg]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
            }

            if ($colIdx = ($colIndexMap['representant_legal'] ?? null)) {
                $this->applyColorCell(
                    $sheet,
                    $colIdx,
                    $excelRow,
                    str_contains($rowData['representant_legal'] ?? '', 'Avec')
                );
            }
            if ($colIdx = ($colIndexMap['type_batiment'] ?? null)) {
                $this->applyColorCell(
                    $sheet,
                    $colIdx,
                    $excelRow,
                    !$this->isCollectif($rowData['type_batiment'] ?? ''),
                    true
                );
            }
            if ($colIdx = ($colIndexMap['type_chauffage_principal'] ?? null)) {
                $this->applyColorCell(
                    $sheet,
                    $colIdx,
                    $excelRow,
                    !$this->isCollectif($rowData['type_chauffage_principal'] ?? ''),
                    true
                );
            }
            if ($colIdx = ($colIndexMap['energie_chauffage_collectif'] ?? null)) {
                $this->applyColorCell(
                    $sheet,
                    $colIdx,
                    $excelRow,
                    !$this->isCollectif($rowData['energie_chauffage_collectif'] ?? ''),
                    true
                );
            }
            if ($colIdx = ($colIndexMap['qpv_eligible'] ?? null)) {
                $this->applyColorCell(
                    $sheet,
                    $colIdx,
                    $excelRow,
                    ($rowData['qpv_eligible'] ?? '') === 'Éligible'
                );
            }
            foreach (['qp_2024', 'qp_2015', 'zfu'] as $zoneKey) {
                if ($colIdx = ($colIndexMap[$zoneKey] ?? null)) {
                    $this->applyColorCell(
                        $sheet,
                        $colIdx,
                        $excelRow,
                        ($rowData[$zoneKey] ?? '') !== 'Oui'
                    );
                }
            }
            if ($colIdx = ($colIndexMap['dr_statut'] ?? null)) {
                $this->applyColorCell(
                    $sheet,
                    $colIdx,
                    $excelRow,
                    str_starts_with($rowData['dr_statut'] ?? '', 'OK')
                );
            }

            $sheet->getRowDimension($excelRow)->setRowHeight(22);
        }

        foreach ($allCols as $i => $key) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($colLetter)->setWidth($this->colWidths[$key] ?? 16);
        }

        $freezeCol = Coordinate::stringFromColumnIndex(count($originalCols) + 1);
        $sheet->freezePane($freezeCol . '2');

        $lastColLetter = Coordinate::stringFromColumnIndex(count($allCols));
        $sheet->setAutoFilter('A1:' . $lastColLetter . (count($rows) + 1));

        $spreadsheet->getProperties()
            ->setCreator('Data360')
            ->setLastModifiedBy('Data360')
            ->setTitle('Export Data360 — ' . now()->format('d/m/Y H:i'))
            ->setSubject('Enrichissement adresses françaises')
            ->setDescription('Export généré automatiquement par Data360');

        return $spreadsheet;
    }

    private function applyColorCell(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $colIdx,
        int $row,
        bool $isGood,
        bool $onlyIfNotEmpty = false
    ): void {
        $colLetter = Coordinate::stringFromColumnIndex($colIdx);
        $cellRef   = $colLetter . $row;

        if ($onlyIfNotEmpty) {
            if (empty(trim((string) $sheet->getCell($cellRef)->getValue()))) return;
        }

        $sheet->getStyle($cellRef)->applyFromArray([
            'font'      => [
                'bold' => true,
                'name' => 'Arial',
                'size' => 9,
                'color' => ['rgb' => $isGood ? '15803D' : 'B91C1C']
            ],
            'fill'      => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $isGood ? 'DCFCE7' : 'FEE2E2']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER
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
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        return $value ?? '';
    }

    private function val($model, array $keys, $default = ''): string
    {
        foreach ($keys as $key) {
            if (is_object($model) && isset($model->{$key}) && $model->{$key} !== null && $model->{$key} !== '') {
                return (string) $model->{$key};
            }
            if (is_array($model) && isset($model[$key]) && $model[$key] !== null && $model[$key] !== '') {
                return (string) $model[$key];
            }
            $raw = is_object($model) ? ($model->raw_data ?? []) : ($model['raw_data'] ?? []);
            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }
            if (is_array($raw) && isset($raw[$key]) && $raw[$key] !== null && $raw[$key] !== '') {
                return (string) $raw[$key];
            }
        }
        return $default;
    }

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
                        'cle_ban' => $addr['cle_interop_ban'] ?? $addr['cle_ban'] ?? $addr['id'] ?? null,
                        'id_ban'  => $addr['ban_id'] ?? $addr['id_ban'] ?? null,
                    ]);
                }
            }
        }

        foreach ($data as $value) {
            $this->extractRnb($value, $rnbId, $rnbStatut, $addresses);
        }
    }
}
