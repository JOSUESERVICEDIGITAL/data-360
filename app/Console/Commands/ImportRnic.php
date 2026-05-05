<?php

namespace App\Console\Commands;

use App\Models\Back\RnicCopropriete;
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
                'n_immatriculation',
                'num_immatriculation',
                'numero_d_immatriculation',
                'n_d_immatriculation',
                'immatriculation',
            ]);

            $adresse = $this->get($data, [
                'adresse_reference',
                'adresse_complete',
                'adresse',
                'adresse_de_reference',
                'adresse_de_la_copropriete',
                'adresse_copropriete',
                'adresse_du_syndicat',
                'localisation',
            ]);

            $codePostal = $this->onlyDigits($this->get($data, [
                'code_postal_adresse',
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
                'nom_copropriete',
                'nom_de_la_copropriete',
                'nom_d_usage',
                'nom_usage',
                'residence',
                'nom_residence',
            ]);

            $representant = $this->get($data, [
                'raison_sociale_representant_legal',
                'identification_representant_legal',
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
                'siret_syndic',
                'siret_representant',
                'siret_du_syndic',
                'siret',
            ]));

            $sirenSyndic = $this->onlyDigits($this->get($data, [
                'siren_syndic',
                'siren_representant',
                'siren_representant_legal',
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

            $adressesAssociees = $this->adressesAssociees($data);

            RnicCopropriete::updateOrCreate(
                [
                    'numero_immatriculation' => $numeroImmatriculation ?: md5(($adresse ?? '') . ($codePostal ?? '') . ($ville ?? '')),
                ],
                [
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
                        'nombre_total_de_lots',
                        'total_lots',
                    ])),

                    'nombre_lots_habitation' => $this->toInt($this->get($data, [
                        'nombre_lots_habitation',
                        'nb_lots_habitation',
                        'nombre_de_lots_a_usage_d_habitation',
                        'lots_habitation',
                        'nombre_lots_usage_habitation',
                    ])),

                    'nombre_batiments' => $this->toInt($this->get($data, [
                        'nombre_batiments',
                        'nb_batiments',
                        'nombre_de_batiments',
                    ])),

                    'nombre_adresses_associees' => count($adressesAssociees),

                    'representant_legal_connu' => !empty($representant) && $representant !== 'Identité non partagée en Open Data',
                    'representant_legal_nom' => $representant,
                    'representant_legal_type' => $representant ? ($this->get($data, ['type_syndic']) ?: 'syndic') : 'inconnu',
                    'message_representant' => $representant ? null : 'Pas de représentant légal connu',

                    'syndic_nom' => $representant,
                    'siren_syndic' => $sirenSyndic,
                    'siret_syndic' => $siretSyndic,

                    'statut' => $this->get($data, ['mandat_en_cours', 'statut', 'etat', 'statut_immatriculation']),
                    'date_immatriculation' => $this->normalizeDate($this->get($data, [
                        'date_immatriculation',
                        'date_d_immatriculation',
                    ])),

                    'raw_data' => array_merge($data, [
                        'adresses_associees_liste' => $adressesAssociees,
                    ]),
                ]
            );

            $count++;

            if ($count % 1000 === 0) {
                $this->info("Import : {$count} lignes | ignorées : {$ignored}");
            }
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
                'adresse_complete',
                'adresse',
                'adresse_complementaire_1',
                'adresse_complementaire_2',
                'adresse_complementaire_3',
            ] as $key
        ) {
            $value = $this->get($data, [$key]);

            if ($value) {
                $adresses[] = $value;
            }
        }

        return collect($adresses)
            ->map(fn($adresse) => trim($adresse))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
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
