<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportRnic extends Command
{
    protected $signature = 'rnic:import {--fresh : Vider la table avant import}';
    protected $description = 'Importer les copropriétés RNIC depuis storage/app/rnic.csv';

    public function handle(): int
    {
        DB::disableQueryLog();

        DB::statement("SET SESSION wait_timeout=28800");
        DB::statement("SET SESSION net_read_timeout=600");
        DB::statement("SET SESSION net_write_timeout=600");

        $path = storage_path('app/rnic.csv');

        if (!file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('rnic_coproprietes')->truncate();
            $this->info('Table rnic_coproprietes vidée.');
        }

        $delimiter = $this->detectDelimiter($path);

        $handle = fopen($path, 'r');

        $rawHeader = fgetcsv($handle, 0, $delimiter);

        if (!$rawHeader) {
            $this->error('Header CSV introuvable.');
            return self::FAILURE;
        }

        $header = array_map(fn($h) => $this->normalizeKey($h), $rawHeader);

        $this->info('Colonnes détectées :');
        $this->line(implode(' | ', $header));

        $count = 0;
        $ignored = 0;

        $batch = [];
        $batchSize = 200;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {

            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            }

            if (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }

            $data = array_combine($header, $row);

            $numeroImmatriculation = $this->get($data, [
                'numero_immatriculation',
                'numero_d_immatriculation',          // ← format vu dans le nouveau CSV
                'n_immatriculation',
                'num_immatriculation',
                'n_d_immatriculation',
                'immatriculation',
            ]);

            $adresse = $this->get($data, [
                'adresse_reference',
                'adresse_de_reference',               // ← nouveau CSV
                'adresse_complete',
                'adresse',
                'adresse_de_la_copropriete',
                'adresse_copropriete',
                'adresse_du_syndicat',
                'localisation',
                'numero_et_voie_adresse_de_reference', // ← nouveau CSV (numéro + voie)
            ]);

            $codePostal = $this->onlyDigits($this->get($data, [
                'code_postal_adresse',
                'code_postal_adresse_de_reference',   // ← nouveau CSV
                'code_postal',
                'cp',
                'codepostal',
                'code_postal_copropriete',
                'code_officiel_commune',
            ]));

            if (!$codePostal && $adresse) {
                preg_match('/\b\d{5}\b/', $adresse, $matches);
                $codePostal = $matches[0] ?? null;
            }

            $ville = $this->get($data, [
                'commune_adresse',
                'commune_adresse_de_reference',       // ← nouveau CSV
                'nom_officiel_commune',
                'ville',
                'commune',
                'libelle_commune',
                'commune_copropriete',
            ]);

            $codeInsee = $this->onlyDigits($this->get($data, [
                'code_insee',
                'insee',
                'code_commune',
                'code_officiel_commune',
                'code_officiel_arrondissement_commune',
                'commune',
            ]));

            if (!$numeroImmatriculation && !$adresse) {
                $ignored++;
                continue;
            }

            $nomCopro = $this->get($data, [
                'nom_usage_copropriete',
                'nom_d_usage_de_la_copropriete',      // ← nouveau CSV
                'nom_copropriete',
                'nom_de_la_copropriete',
                'nom_d_usage',
                'nom_usage',
                'residence',
                'nom_residence',
            ]);

            $representant = $this->get($data, [
                'raison_sociale_representant_legal',
                'raison_sociale_du_representant_legal',          // ← nouveau CSV
                'identification_representant_legal',
                'identification_du_representant_legal_raison_sociale_et_le_numer', // ← nouveau CSV (tronqué)
                'representant_legal_nom',
                'representant_legal',
                'nom_representant_legal',
                'representant',
                'nom_syndic',
                'syndic',
                'nom_du_syndic',
                'mandataire',
            ]);

            if ($representant && $representant !== 'Identité non partagée en Open Data') {
                $representant = preg_replace('/\b\d{9}\b/', '', $representant);
                $representant = preg_replace('/\b\d{14}\b/', '', $representant);
                $representant = trim($representant);
            }

            $siretSyndic = $this->onlyDigits($this->get($data, [
                'siret_representant_legal',
                'siret_du_representant_legal',         // ← nouveau CSV
                'siret_syndic',
                'siret_representant',
                'siret_du_syndic',
                'siret',
            ]));

            $sirenSyndic = $this->onlyDigits($this->get($data, [
                'siren_syndic',
                'siren_representant',
                'siren_representant_legal',
                'siren_du_representant_legal',         // ← cohérence nommage nouveau CSV
                'siren_du_syndic',
                'siren',
            ]));

            if (!$sirenSyndic && strlen((string) $siretSyndic) === 14) {
                $sirenSyndic = substr($siretSyndic, 0, 9);
            }

            if ($representant && $siretSyndic) {
                $representant = trim(str_replace($siretSyndic, '', $representant));
            }

            if ($representant && $sirenSyndic) {
                $representant = trim(str_replace($sirenSyndic, '', $representant));
            }

            // ── Mandat en cours / statut du mandat ──────────────
            // Le nouveau CSV utilise "mandat_en_cours_dans_la_copropriete"
            // au lieu de l'ancien "mandat_en_cours"
            $mandatEnCours = $this->get($data, [
                'mandat_en_cours',
                'mandat_en_cours_dans_la_copropriete', // ← nouveau CSV
                'statut',
                'etat',
                'statut_immatriculation',
            ]);

            // ── Date de fin du dernier mandat ───────────────────
            $dateFinMandat = $this->get($data, [
                'date_fin_dernier_mandat',
                'date_de_fin_du_dernier_mandat',       // ← nouveau CSV
            ]);

            // ── Type de syndic (bénévole / professionnel / non connu) ──
            $typeSyndic = $this->get($data, [
                'type_syndic',
                'type_de_syndic_benevole_professionnel_non_connu', // ← nouveau CSV
            ]);

            // ── Dernière mise à jour de la fiche ────────────────
            $derniereMaj = $this->get($data, [
                'date_derniere_maj',
                'date_de_la_derniere_maj',             // ← nouveau CSV
            ]);

            $adressesAssociees = $this->adressesAssociees($data);

            $batch[] = [
                'numero_immatriculation' =>
                $numeroImmatriculation
                    ?: md5(($adresse ?? '') . ($codePostal ?? '') . ($ville ?? '')),

                'nom_copropriete' => $nomCopro,

                'adresse_complete' => $adresse,
                'code_postal' => $codePostal,
                'ville' => $ville,
                'code_insee' => $codeInsee,

                'siren_copropriete' => $this->onlyDigits($this->get($data, [
                    'siren_copropriete',
                    'siren_du_syndicat',
                    'siren_syndicat',
                ])),

                'nombre_lots_total' => $this->toInt($this->get($data, [
                    'nombre_total_lots',
                    'nombre_lots_total',
                    'nb_lots_total',
                    'nombre_total_de_lots',            // ← nouveau CSV
                    'total_lots',
                ])),

                'nombre_lots_habitation' => $this->toInt($this->get($data, [
                    'nombre_lots_habitation',
                    'nb_lots_habitation',
                    'nombre_de_lots_a_usage_d_habitation', // ← nouveau CSV
                    'lots_habitation',
                    'nombre_lots_usage_habitation',
                ])),

                'nombre_batiments' => $this->toInt($this->get($data, [
                    'nombre_batiments',
                    'nb_batiments',
                    'nombre_de_batiments',
                ])),

                'nombre_adresses_associees' => count($adressesAssociees),

                'representant_legal_connu' =>
                !empty($representant)
                    && $representant !== 'Identité non partagée en Open Data',

                'representant_legal_nom' => $representant,

                'representant_legal_type' =>
                $representant
                    ? ($typeSyndic ?: 'syndic')
                    : 'inconnu',

                'message_representant' =>
                $representant
                    ? null
                    : 'Pas de représentant légal connu',

                'syndic_nom' => $representant,
                'siren_syndic' => $sirenSyndic,
                'siret_syndic' => $siretSyndic,

                'statut' => $mandatEnCours,

                'date_immatriculation' => $this->normalizeDate(
                    $this->get($data, [
                        'date_immatriculation',
                        'date_d_immatriculation',
                    ])
                ),

                'raw_data' => json_encode(array_merge($data, [
                    'adresses_associees_liste' => $adressesAssociees,
                    // Champs normalisés ajoutés pour fiabiliser les lectures
                    // ultérieures côté Blade/Service, peu importe le nom de
                    // colonne d'origine dans le CSV source.
                    'mandat_en_cours'                   => $mandatEnCours,
                    'date_fin_dernier_mandat'           => $dateFinMandat,
                    'type_syndic'                        => $typeSyndic,
                    'date_derniere_maj'                 => $derniereMaj,
                    'raison_sociale_representant_legal' => $representant,
                    'siret_representant_legal'          => $siretSyndic,
                    'siren_representant_legal'          => $sirenSyndic,
                ])),

                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {

                DB::table('rnic_coproprietes')->insert($batch);

                $count += count($batch);

                $this->info("Import : {$count} lignes | ignorées : {$ignored}");

                $batch = [];
                gc_collect_cycles();
            }
        }

        if (!empty($batch)) {

            DB::table('rnic_coproprietes')->insert($batch);

            $count += count($batch);
        }

        fclose($handle);

        $this->info("✅ Import terminé : {$count} lignes importées, {$ignored} ignorées.");

        return self::SUCCESS;
    }

    private function adressesAssociees(array $data): array
    {
        $adresses = [];

        foreach (
            [
                'adresse_reference',
                'adresse_de_reference',               // ← nouveau CSV
                'adresse_complete',
                'adresse',
                'adresse_complementaire_1',
                'adresse_complementaire_2',
                'adresse_complementaire_3',
            ] as $key
        ) {
            $value = $this->get($data, [$key]);

            if ($value) {
                $adresses[] = trim($value);
            }
        }

        return array_values(array_unique(array_filter($adresses)));
    }

    private function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');

        $line = fgets($handle);

        fclose($handle);

        $delimiters = [
            ';' => substr_count($line, ';'),
            ',' => substr_count($line, ','),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($delimiters);

        return array_key_first($delimiters);
    }

    private function normalizeKey(?string $key): string
    {
        $key = Str::ascii(mb_strtolower(trim((string) $key)));

        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim($key, '_');
    }

    private function get(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {

            $key = $this->normalizeKey($key);

            if (isset($data[$key]) && trim((string) $data[$key]) !== '') {
                return trim((string) $data[$key]);
            }
        }

        return null;
    }

    private function onlyDigits(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits ?: null;
    }

    private function toInt(?string $value): ?int
    {
        if (!$value) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits !== '' ? (int) $digits : null;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
