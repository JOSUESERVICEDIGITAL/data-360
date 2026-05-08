<?php

namespace App\Console\Commands;

use App\Models\Back\QpvZone;
use Illuminate\Console\Command;

class ImportQpvZones extends Command
{
    protected $signature = 'qpv:import 
        {file : Chemin du fichier GeoJSON}
        {type : qp_2024|qp_2015|zfu}';

    protected $description = 'Importe les zones QPV/ZFU depuis un fichier GeoJSON';

    public function handle(): int
    {
        $file = $this->argument('file');
        $type = $this->argument('type');

        if (!in_array($type, ['qp_2024', 'qp_2015', 'zfu'], true)) {
            $this->error('Type invalide. Utilise : qp_2024, qp_2015 ou zfu');
            return self::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error("Fichier introuvable : {$file}");
            return self::FAILURE;
        }

        $json = json_decode(file_get_contents($file), true);

        if (!$json || empty($json['features'])) {
            $this->error('GeoJSON invalide ou vide.');
            return self::FAILURE;
        }

        $count = 0;

        foreach ($json['features'] as $feature) {
            $properties = $feature['properties'] ?? [];
            $geometry = $feature['geometry'] ?? null;

            if (!$geometry) {
                continue;
            }

            // Conversion automatique Lambert-93 / autres coordonnées projetées vers GPS WGS84
            $geometry = $this->convertGeometryIfNeeded($geometry);

            $code = $properties['code_qp']
                ?? $properties['CODE_QP']
                ?? $properties['code']
                ?? $properties['CODE']
                ?? $properties['id']
                ?? $properties['id_qp']
                ?? $properties['ID_QP']
                ?? null;

            $nom = $properties['nom_qp']
                ?? $properties['NOM_QP']
                ?? $properties['nom']
                ?? $properties['NOM']
                ?? $properties['libelle']
                ?? $properties['LIBELLE']
                ?? $properties['nom_qp_2024']
                ?? null;

            QpvZone::updateOrCreate(
                [
                    'type' => $type,
                    'code' => $code ?: md5(json_encode($geometry)),
                ],
                [
                    'nom' => $nom,
                    'geojson' => json_encode($geometry, JSON_UNESCAPED_UNICODE),
                ]
            );

            $count++;
        }

        $this->info("Import terminé : {$count} zones {$type}");

        return self::SUCCESS;
    }

    private function convertGeometryIfNeeded(array $geometry): array
    {
        if (empty($geometry['coordinates'])) {
            return $geometry;
        }

        $geometry['coordinates'] = $this->convertCoordinates($geometry['coordinates']);

        return $geometry;
    }

    private function convertCoordinates(array $coordinates): array
    {
        if (
            isset($coordinates[0], $coordinates[1])
            && is_numeric($coordinates[0])
            && is_numeric($coordinates[1])
        ) {
            $x = (float) $coordinates[0];
            $y = (float) $coordinates[1];

            // Déjà en GPS WGS84 : longitude / latitude
            if (abs($x) <= 180 && abs($y) <= 90) {
                return [$x, $y];
            }

            // Lambert-93 France hexagonale
            return $this->lambert93ToWgs84($x, $y);
        }

        return array_map(function ($item) {
            return is_array($item) ? $this->convertCoordinates($item) : $item;
        }, $coordinates);
    }

    private function lambert93ToWgs84(float $x, float $y): array
    {
        $e = 0.08181919106;
        $n = 0.7256077650532670;
        $c = 11754255.426096;
        $xs = 700000.0;
        $ys = 12655612.049876;
        $lon0 = deg2rad(3.0);

        $r = sqrt(pow($x - $xs, 2) + pow($y - $ys, 2));
        $gamma = atan(($x - $xs) / ($ys - $y));

        $lon = $lon0 + ($gamma / $n);
        $latIso = -log(abs($r / $c)) / $n;

        $lat = 2 * atan(exp($latIso)) - pi() / 2;

        for ($i = 0; $i < 6; $i++) {
            $lat = 2 * atan(
                pow(
                    (1 + $e * sin($lat)) / (1 - $e * sin($lat)),
                    $e / 2
                ) * exp($latIso)
            ) - pi() / 2;
        }

        return [
            round(rad2deg($lon), 7),
            round(rad2deg($lat), 7),
        ];
    }
}