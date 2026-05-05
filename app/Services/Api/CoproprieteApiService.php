<?php

namespace App\Services\Api;

use App\Models\Back\RnicCopropriete;
use Illuminate\Support\Str;

class CoproprieteApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {}

    public function searchByAddress(string $adresse, ?string $codePostal = null, ?string $ville = null): array
    {
        $searched = $this->normalizeText($adresse);
        $streetNumber = $this->extractNumber($searched);
        $streetWords = $this->extractImportantWords($searched);

        $query = RnicCopropriete::query();

        if ($codePostal) {
            $query->where('code_postal', $codePostal);
        }

        if ($ville) {
            $query->where(function ($q) use ($ville) {
                $q->where('ville', 'like', '%' . $ville . '%');
            });
        }

        $candidates = $query
            ->whereNotNull('adresse_complete')
            ->limit(1000)
            ->get()
            ->map(function (RnicCopropriete $copro) use ($searched, $streetNumber, $streetWords) {
                $candidate = $this->normalizeText($copro->adresse_complete);

                return [
                    'score' => $this->scoreAddress($searched, $candidate, $streetNumber, $streetWords),
                    'copro' => $copro,
                ];
            })
            ->filter(fn ($item) => $item['score'] >= 45)
            ->sortByDesc('score')
            ->take(10)
            ->values();

        $results = [];

        foreach ($candidates as $candidate) {
            /** @var RnicCopropriete $copro */
            $copro = $candidate['copro'];

            $sameImmatriculation = collect();

            if ($copro->numero_immatriculation) {
                $sameImmatriculation = RnicCopropriete::where('numero_immatriculation', $copro->numero_immatriculation)
                    ->get(['adresse_complete', 'code_postal', 'ville']);
            }

            $arr = $copro->toArray();
            $arr['score_match'] = $candidate['score'];
            $arr['adresses_associees_liste'] = $sameImmatriculation
                ->map(fn ($a) => trim(($a->adresse_complete ?? '') . ' ' . ($a->code_postal ?? '') . ' ' . ($a->ville ?? '')))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (!$arr['nombre_adresses_associees']) {
                $arr['nombre_adresses_associees'] = count($arr['adresses_associees_liste']);
            }

            $results[] = $arr;
        }

        $this->logger->log(
            'RNIC_LOCAL',
            'rnic_coproprietes',
            $adresse,
            null,
            !empty($results),
            compact('adresse', 'codePostal', 'ville'),
            ['count' => count($results)],
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

            'adresses_associees_liste' => $item['adresses_associees_liste'] ?? [],

            'raw_data' => $item,
        ];
    }

    private function normalizeText(?string $text): string
    {
        $text = Str::ascii(mb_strtolower($text ?? ''));
        $text = preg_replace('/\b(rue|r|avenue|av|boulevard|bd|allee|all|impasse|chemin|ch|route|rte|place|pl)\b/u', ' ', $text);
        $text = preg_replace('/[^a-z0-9 ]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function extractNumber(string $text): ?string
    {
        preg_match('/\b\d+\b/', $text, $matches);

        return $matches[0] ?? null;
    }

    private function extractImportantWords(string $text): array
    {
        $words = array_filter(explode(' ', $text));

        return array_values(array_filter($words, fn ($word) => strlen($word) >= 4 && !is_numeric($word)));
    }

    private function scoreAddress(string $searched, string $candidate, ?string $streetNumber, array $streetWords): int
    {
        if (!$searched || !$candidate) {
            return 0;
        }

        similar_text($searched, $candidate, $percent);

        $score = (int) $percent;

        if ($streetNumber && preg_match('/\b' . preg_quote($streetNumber, '/') . '\b/', $candidate)) {
            $score += 25;
        }

        foreach ($streetWords as $word) {
            if (str_contains($candidate, $word)) {
                $score += 10;
            }
        }

        return min($score, 100);
    }
}
