<?php

namespace App\Services\Api;

use App\Models\Back\QpvZone;

class QpvEligibilityService
{
    public function check(?float $latitude, ?float $longitude): array
    {
        if (!$latitude || !$longitude) {
            return [
                'eligible' => null,
                'message' => 'Coordonnées GPS manquantes.',
                'qp_2024' => false,
                'qp_2015' => false,
                'zfu' => false,
                'matches' => [],
            ];
        }

        $matches = [];

        foreach (['qp_2024', 'qp_2015', 'zfu'] as $type) {
            $zone = $this->findContainingZone($type, $latitude, $longitude);

            $matches[$type] = $zone ? [
                'found' => true,
                'code' => $zone->code,
                'nom' => $zone->nom,
            ] : [
                'found' => false,
                'code' => null,
                'nom' => null,
            ];
        }

        $isInQp2024 = $matches['qp_2024']['found'];
        $isInQp2015 = $matches['qp_2015']['found'];
        $isInZfu = $matches['zfu']['found'];

        $eligible = !$isInQp2024 && !$isInQp2015 && !$isInZfu;

        return [
            'eligible' => $eligible,
            'message' => $eligible
                ? 'Adresse éligible : hors QP 2024, hors QP 2015 et hors ZFU.'
                : 'Adresse non éligible : située dans au moins une zone prioritaire.',
            'qp_2024' => $isInQp2024,
            'qp_2015' => $isInQp2015,
            'zfu' => $isInZfu,
            'matches' => $matches,
        ];
    }

    private function findContainingZone(string $type, float $lat, float $lng): ?QpvZone
    {
        $zones = QpvZone::where('type', $type)->get();

        foreach ($zones as $zone) {
            $geometry = json_decode($zone->geojson, true);

            if (!$geometry) {
                continue;
            }

            if ($this->pointInGeometry($lng, $lat, $geometry)) {
                return $zone;
            }
        }

        return null;
    }

    private function pointInGeometry(float $x, float $y, array $geometry): bool
    {
        $type = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? [];

        if ($type === 'Polygon') {
            return $this->pointInPolygon($x, $y, $coordinates);
        }

        if ($type === 'MultiPolygon') {
            foreach ($coordinates as $polygon) {
                if ($this->pointInPolygon($x, $y, $polygon)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pointInPolygon(float $x, float $y, array $polygon): bool
    {
        if (empty($polygon[0])) {
            return false;
        }

        $inside = $this->rayCasting($x, $y, $polygon[0]);

        if (!$inside) {
            return false;
        }

        // Exclure les trous intérieurs du polygone
        for ($i = 1; $i < count($polygon); $i++) {
            if ($this->rayCasting($x, $y, $polygon[$i])) {
                return false;
            }
        }

        return true;
    }

    private function rayCasting(float $x, float $y, array $points): bool
    {
        $inside = false;
        $count = count($points);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $points[$i][0];
            $yi = (float) $points[$i][1];
            $xj = (float) $points[$j][0];
            $yj = (float) $points[$j][1];

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 0.0000001) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}