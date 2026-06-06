<?php

namespace App\Jobs;

use App\Models\Back\CsvImport;
use App\Models\Back\Copropriete;
use App\Models\Back\Adresse as AdresseModel;
use App\Services\Api\DataRocketEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries   = 1;

    // Cache mémoire pour éviter les doublons d'appels API
    private array $resultatCache = [];

    private array $collectifKeywords = [
        'collectif', 'collective', 'immeuble', 'copropriete', 'copropriété',
        'résidence', 'residence', 'appartement', 'appartements',
    ];

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
    // Constructeur — plus de csvPath, tout est en base
    // ────────────────────────────────────────────────────────────
    public function __construct(
        public CsvImport $import,
    ) {}

    // ────────────────────────────────────────────────────────────
    // TRAITEMENT PRINCIPAL
    // ────────────────────────────────────────────────────────────
    public function handle(DataRocketEngineService $engine): void
    {
        $this->import->update(['statut' => 'en_cours']);

        try {
            // 1. Lire le CSV depuis la base (pas depuis le disque)
            $csv = Reader::createFromString($this->import->csv_content);
            $csv->setHeaderOffset(0);
            $rows = iterator_to_array($csv->getRecords());

            if (empty($rows)) {
                $this->import->update(['statut' => 'erreur', 'erreur_message' => 'CSV vide.']);
                return;
            }

            $firstRow   = $rows[array_key_first($rows)];
            $adresseCol = collect(array_keys($firstRow))
                ->first(fn($k) => in_array(strtolower(trim($k)), ['adresse', 'address', 'adresses']));

            if (!$adresseCol) {
                $this->import->update(['statut' => 'erreur', 'erreur_message' => 'Colonne "adresse" introuvable.']);
                return;
            }

            $originalCols = array_keys($firstRow);
            $total        = count($rows);
            $output       = [];

            $this->import->update(['total_lignes' => $total]);

            // 2. Traitement par chunks de 10
            $chunks = array_chunk($rows, 10, true);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $idx => $row) {
                    $adresse = trim($row[$adresseCol] ?? '');

                    if (empty($adresse)) {
                        $output[] = array_merge($row, array_fill_keys($this->enrichedCols, ''));
                    } else {
                        try {
                            $cacheKey = mb_strtolower(trim($adresse));

                            if (isset($this->resultatCache[$cacheKey])) {
                                $resultat = $this->resultatCache[$cacheKey];
                            } else {
                                $resultat = $this->getFromDbCache($adresse, $engine);
                                $this->resultatCache[$cacheKey] = $resultat;
                            }

                            $output[] = array_merge($row, $this->buildEnriched($resultat));

                        } catch (\Throwable $e) {
                            $enriched              = array_fill_keys($this->enrichedCols, '');
                            $enriched['dr_statut'] = 'ERREUR';
                            $enriched['dr_erreur'] = $e->getMessage();
                            $output[]              = array_merge($row, $enriched);
                        }
                    }
                }

                // Mise à jour progression après chaque chunk
                $this->import->update(['lignes_traitees' => min(count($output), $total)]);
                gc_collect_cycles();
            }

            // 3. Générer le XLSX en mémoire (pas sur disque)
            $spreadsheet = $this->buildXlsx($originalCols, $output);

            ob_start();
            (new Xlsx($spreadsheet))->save('php://output');
            $xlsxBinary = ob_get_clean();

            // 4. Stocker le XLSX en base64 dans la base de données
            $this->import->update([
                'statut'          => 'termine',
                'xlsx_content'    => base64_encode($xlsxBinary),  // ← en base, pas sur disque
                'lignes_traitees' => $total,
            ]);

            // Libérer la mémoire
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $xlsxBinary);

        } catch (\Throwable $e) {
            $this->import->update([
                'statut'         => 'erreur',
                'erreur_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ────────────────────────────────────────────────────────────
    // Cache DB : réutiliser les données déjà en base
    // ────────────────────────────────────────────────────────────
    private function getFromDbCache(string $adresse, DataRocketEngineService $engine): array
    {
        $adresseEnBase = AdresseModel::where('adresse_complete', 'like', '%' . trim($adresse) . '%')
            ->with(['batiments', 'coproprietes.syndics'])
            ->first();

        if ($adresseEnBase && $adresseEnBase->batiments->count() > 0) {
            return [
                'success'            => true,
                'adresse'            => $adresseEnBase,
                'batiments'          => $adresseEnBase->batiments->all(),
                'coproprietes'       => $adresseEnBase->coproprietes->all(),
                'syndics'            => $adresseEnBase->coproprietes
                    ->flatMap(fn($c) => $c->syndics)
                    ->unique('id')->values()->all(),
                'proprietaires_bdnb' => [],
                'cadastre'           => [],
                'rnb'                => null,
                'qpv'                => ['checks' => [], 'eligible' => null],
                '_from_cache'        => true,
            ];
        }

        return $engine->searchByAddress($adresse);
    }

    // ────────────────────────────────────────────────────────────
    // Assemblage des colonnes enrichies
    // ────────────────────────────────────────────────────────────
    private function buildEnriched(array $resultat): array
    {
        $copros = collect($resultat['coproprietes'] ?? []);
        $copro  = $copros->first();

        if ($copro instanceof Copropriete && !$copro->relationLoaded('syndics')) {
            $copro->load('syndics');
        }

        $syndicsDuService = collect($resultat['syndics'] ?? []);
        $syndic = $syndicsDuService->filter()->first()
            ?? ($copro ? $copro->syndics->first() : null);

        $representantNom   = $syndic ? ($syndic->nom   ?? '') : '';
        $sirenSyndic       = $syndic ? ($syndic->siren ?? '') : '';
        $siretSyndic       = $syndic ? ($syndic->siret ?? '') : '';
        $representantConnu = $syndic !== null && (!empty($representantNom) || !empty($sirenSyndic) || !empty($siretSyndic));

        $qpv        = $resultat['qpv'] ?? null;
        $qpvChecks  = collect($qpv['checks'] ?? []);
        $hasQpv2024 = $qpvChecks->contains(fn($c) => $c['result']['qp_2024'] ?? false);
        $hasQpv2015 = $qpvChecks->contains(fn($c) => $c['result']['qp_2015'] ?? false);
        $hasZfu     = $qpvChecks->contains(fn($c) => $c['result']['zfu']     ?? false);
        $hasAnyZone = $hasQpv2024 || $hasQpv2015 || $hasZfu;

        $batiment   = collect($resultat['batiments'] ?? [])->first();
        $adresseObj = $resultat['adresse'] ?? null;

        $rnbId = null; $rnbStatut = null; $rnbAddresses = collect();
        if ($rnbData = ($resultat['rnb'] ?? null)) {
            $this->extractRnb($rnbData, $rnbId, $rnbStatut, $rnbAddresses);
            $rnbAddresses = $rnbAddresses
                ->filter(fn($a) => !empty($a['adresse']) && $a['adresse'] !== '-')
                ->unique('adresse')->values();
        }

        return [
            'representant_legal'          => $representantConnu ? 'Avec représentant' : 'Sans représentant',
            'nom_representant'             => $representantNom,
            'type_representant'            => $syndic ? ($syndic->forme_juridique ?? '') : '',
            'siren_syndic'                 => $sirenSyndic,
            'siret_syndic'                 => $siretSyndic,
            'immatriculation_copro'        => $copro ? ($copro->numero_immatriculation ?? '') : '',
            'nom_residence'                => $copro ? ($copro->nom_copropriete       ?? '') : '',
            'nb_lots_habitation'           => $copro ? ($copro->nombre_lots_habitation ?? '') : '',
            'score_rnic'                   => '',
            'type_batiment'                => $batiment ? $this->val($batiment, ['type_batiment'])    : '',
            'annee_construction'           => $batiment ? $this->val($batiment, ['annee_construction']) : '',
            'nb_logements'                 => $batiment ? $this->val($batiment, ['nombre_logements'])  : '',
            'nb_niveaux'                   => $batiment ? $this->val($batiment, ['nombre_niveaux'])    : '',
            'hauteur'                      => $batiment ? $this->val($batiment, ['hauteur'])           : '',
            'surface_habitable'            => $batiment ? $this->val($batiment, ['surface_habitable']) : '',
            'surface_emprise_sol'          => $batiment ? $this->val($batiment, ['surface_emprise_sol']) : '',
            'classe_dpe'                   => $batiment ? $this->val($batiment, ['classe_dpe'])        : '',
            'ges'                          => $batiment ? $this->val($batiment, ['ges'])               : '',
            'type_chauffage_principal'     => $batiment ? $this->val($batiment, ['type_chauffage', 'chauffage_principal', 'type_installation_chauffage']) : '',
            'energie_chauffage_collectif'  => $batiment ? $this->val($batiment, ['energie_chauffage', 'energie_principale_chauffage', 'l_ch_princ_energie']) : '',
            'nb_proprietaires'             => (string) count($resultat['proprietaires_bdnb'] ?? []),
            'nb_coproprietes'              => (string) $copros->count(),
            'siren_copropriete'            => $copro ? ($copro->siren_copropriete ?? '') : '',
            'adresse_normalisee'           => is_object($adresseObj) ? ($adresseObj->adresse_complete ?? '') : '',
            'code_postal'                  => is_object($adresseObj) ? ($adresseObj->code_postal     ?? '') : '',
            'ville'                        => is_object($adresseObj) ? ($adresseObj->ville           ?? '') : '',
            'code_insee'                   => is_object($adresseObj) ? ($adresseObj->code_insee      ?? '') : '',
            'latitude'                     => is_object($adresseObj) ? ($adresseObj->latitude        ?? '') : '',
            'longitude'                    => is_object($adresseObj) ? ($adresseObj->longitude       ?? '') : '',
            'qpv_eligible'                 => $hasAnyZone ? 'Non éligible' : 'Éligible',
            'qp_2024'                      => $hasQpv2024 ? 'Oui' : 'Non',
            'qp_2015'                      => $hasQpv2015 ? 'Oui' : 'Non',
            'zfu'                          => $hasZfu     ? 'Oui' : 'Non',
            'rnb_id'                       => $rnbId    ?? '',
            'rnb_statut'                   => $rnbStatut ?? '',
            'rnb_nb_adresses'              => (string) $rnbAddresses->count(),
            'syndic_forme_juridique'       => $syndic ? ($syndic->forme_juridique    ?? '') : '',
            'syndic_capital_social'        => $syndic ? ($syndic->capital_social     ?? '') : '',
            'syndic_chiffre_affaires'      => $syndic ? ($syndic->chiffre_affaires   ?? '') : '',
            'syndic_resultat'              => $syndic ? ($syndic->resultat            ?? '') : '',
            'syndic_effectif'              => $syndic ? ($syndic->effectif            ?? '') : '',
            'syndic_dirigeant'             => $syndic ? ($syndic->dirigeant_principal ?? '') : '',
            'dr_statut'                    => isset($resultat['_from_cache']) ? 'OK (cache)' : 'OK',
            'dr_erreur'                    => '',
        ];
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
                $value     = $this->sanitize($rowData[$key] ?? null);

                $sheet->getCell($cellRef)->setValue($value);
                $sheet->getStyle($cellRef)->applyFromArray([
                    'font'      => ['name' => 'Arial', 'size' => 9, 'color' => ['rgb' => '1E293B']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $baseBg]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                ]);
            }

            if ($colIdx = ($colIndexMap['representant_legal'] ?? null)) {
                $this->applyColorCell($sheet, $colIdx, $excelRow,
                    str_contains($rowData['representant_legal'] ?? '', 'Avec'));
            }
            if ($colIdx = ($colIndexMap['type_batiment'] ?? null)) {
                $this->applyColorCell($sheet, $colIdx, $excelRow,
                    !$this->isCollectif($rowData['type_batiment'] ?? ''), true);
            }
            if ($colIdx = ($colIndexMap['type_chauffage_principal'] ?? null)) {
                $this->applyColorCell($sheet, $colIdx, $excelRow,
                    !$this->isCollectif($rowData['type_chauffage_principal'] ?? ''), true);
            }
            if ($colIdx = ($colIndexMap['energie_chauffage_collectif'] ?? null)) {
                $this->applyColorCell($sheet, $colIdx, $excelRow,
                    !$this->isCollectif($rowData['energie_chauffage_collectif'] ?? ''), true);
            }
            if ($colIdx = ($colIndexMap['qpv_eligible'] ?? null)) {
                $this->applyColorCell($sheet, $colIdx, $excelRow,
                    ($rowData['qpv_eligible'] ?? '') === 'Éligible');
            }
            foreach (['qp_2024', 'qp_2015', 'zfu'] as $zoneKey) {
                if ($colIdx = ($colIndexMap[$zoneKey] ?? null)) {
                    $this->applyColorCell($sheet, $colIdx, $excelRow,
                        ($rowData[$zoneKey] ?? '') !== 'Oui');
                }
            }
            if ($colIdx = ($colIndexMap['dr_statut'] ?? null)) {
                $this->applyColorCell($sheet, $colIdx, $excelRow,
                    str_starts_with($rowData['dr_statut'] ?? '', 'OK'));
            }

            $sheet->getRowDimension($excelRow)->setRowHeight(22);
        }

        foreach ($allCols as $i => $key) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($colLetter)->setWidth($this->colWidths[$key] ?? 16);
        }

        $freezeCol = Coordinate::stringFromColumnIndex(count($originalCols) + 1);
        $sheet->freezePane($freezeCol . '2');

        $lastCol = Coordinate::stringFromColumnIndex(count($allCols));
        $sheet->setAutoFilter('A1:' . $lastCol . (count($rows) + 1));

        $spreadsheet->getProperties()
            ->setCreator('Data360')
            ->setTitle('Export Data360 — ' . now()->format('d/m/Y H:i'));

        return $spreadsheet;
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────
    private function applyColorCell(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $colIdx, int $row, bool $isGood, bool $onlyIfNotEmpty = false
    ): void {
        $colLetter = Coordinate::stringFromColumnIndex($colIdx);
        $cellRef   = $colLetter . $row;

        if ($onlyIfNotEmpty) {
            if (empty(trim((string) $sheet->getCell($cellRef)->getValue()))) return;
        }

        $sheet->getStyle($cellRef)->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9,
                            'color' => ['rgb' => $isGood ? '15803D' : 'B91C1C']],
            'fill'      => ['fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $isGood ? 'DCFCE7' : 'FEE2E2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
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
            if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
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
                $label = $addr['label'] ?? $addr['adresse']
                    ?? trim(collect([
                        $addr['street_number'] ?? null, $addr['street_rep'] ?? null,
                        $addr['street'] ?? null, $addr['city_zipcode'] ?? null,
                        $addr['city_name'] ?? null,
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
