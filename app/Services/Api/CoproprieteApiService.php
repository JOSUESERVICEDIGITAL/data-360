<?php

namespace App\Services\Api;

use App\Models\Back\RnicCopropriete;

class CoproprieteApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {}

    public function searchByAddress(string $adresse, ?string $codePostal = null, ?string $ville = null): array
    {
        $normalized = $this->normalizeText($adresse);

        $query = RnicCopropriete::query();

        if ($codePostal) {
            $query->where('code_postal', $codePostal);
        }

        if ($ville) {
            $query->where(function ($q) use ($ville) {
                $q->where('ville', 'like', '%' . $ville . '%');
            });
        }

        $results = $query
            ->limit(100)
            ->get()
            ->map(function (RnicCopropriete $copro) use ($normalized) {
                $score = $this->scoreAddress($normalized, $this->normalizeText($copro->adresse_complete ?? ''));

                return [
                    'score_match' => $score,
                    'copro' => $copro,
                ];
            })
            ->filter(fn ($item) => $item['score_match'] >= 35)
            ->sortByDesc('score_match')
            ->take(10)
            ->map(fn ($item) => $item['copro']->toArray())
            ->values()
            ->toArray();

        $this->logger->log(
            'RNIC_LOCAL',
            'rnic_coproprietes',
            $adresse,
            null,
            !empty($results),
            [
                'adresse' => $adresse,
                'code_postal' => $codePostal,
                'ville' => $ville,
            ],
            [
                'count' => count($results),
            ],
            empty($results) ? 'Aucune copropriété locale trouvée' : null
        );

        return $results;
    }

    public function normalize(array $item): array
    {
        $representantNom = $item['representant_legal_nom']
            ?? $item['representant']
            ?? $item['syndic_nom']
            ?? null;

        return [
            'numero_immatriculation' => $item['numero_immatriculation'] ?? null,
            'nom_copropriete' => $item['nom_copropriete'] ?? null,
            'siren_copropriete' => $item['siren_copropriete'] ?? null,

            'nombre_lots_total' => $item['nombre_lots_total'] ?? null,
            'nombre_lots_habitation' => $item['nombre_lots_habitation'] ?? null,
            'nombre_batiments' => $item['nombre_batiments'] ?? null,
            'nombre_adresses_associees' => $item['nombre_adresses_associees'] ?? null,

            'statut' => $item['statut'] ?? null,
            'date_immatriculation' => $item['date_immatriculation'] ?? null,

            'representant_legal_connu' => !empty($representantNom),
            'representant_legal_nom' => $representantNom,
            'representant_legal_type' => $item['representant_legal_type'] ?? 'syndic',
            'message_representant' => $representantNom ? null : 'Pas de représentant légal connu',

            'syndic_nom' => $item['syndic_nom'] ?? $representantNom,
            'siren_syndic' => $item['siren_syndic'] ?? null,
            'siret_syndic' => $item['siret_syndic'] ?? null,

            'raw_data' => $item,
        ];
    }

    private function normalizeText(?string $text): string
    {
        $text = mb_strtolower($text ?? '');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9 ]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function scoreAddress(string $searched, string $candidate): int
    {
        if (!$searched || !$candidate) {
            return 0;
        }

        similar_text($searched, $candidate, $percent);

        $score = (int) $percent;

        $searchedWords = array_filter(explode(' ', $searched));
        $candidateWords = array_filter(explode(' ', $candidate));

        $common = array_intersect($searchedWords, $candidateWords);

        $score += count($common) * 5;

        return min($score, 100);
    }
}