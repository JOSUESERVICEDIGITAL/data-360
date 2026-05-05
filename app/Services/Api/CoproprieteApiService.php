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
        $searchedNumber = $this->extractNumber($searched);
        $searchedPostal = $codePostal ?: $this->extractPostalCode($adresse);
        $searchedWords = $this->extractImportantWords($searched);

        $query = RnicCopropriete::query()->whereNotNull('adresse_complete');

        if ($searchedPostal) {
            $query->where('code_postal', $searchedPostal);
        }

        $candidates = $query
            ->limit(3000)
            ->get()
            ->map(function (RnicCopropriete $copro) use ($adresse, $searched, $searchedNumber, $searchedPostal, $searchedWords) {
                $best = $this->bestAddressMatchForCopro($copro, $adresse, $searched, $searchedNumber, $searchedPostal, $searchedWords);

                return [
                    'score' => $best['score'],
                    'matched_address' => $best['matched_address'],
                    'is_exact_address' => $best['is_exact_address'],
                    'copro' => $copro,
                ];
            })
            ->filter(fn ($item) => $item['score'] >= 85 && $item['is_exact_address'])
            ->sortByDesc('score')
            ->take(5)
            ->values();

        $results = [];

        foreach ($candidates as $candidate) {
            /** @var RnicCopropriete $copro */
            $copro = $candidate['copro'];

            $sameImmatriculation = $copro->numero_immatriculation
                ? RnicCopropriete::where('numero_immatriculation', $copro->numero_immatriculation)
                    ->get(['adresse_complete', 'code_postal', 'ville', 'raw_data'])
                : collect();

            $arr = $copro->toArray();
            $arr['score_match'] = $candidate['score'];
            $arr['adresse_rnic_match'] = $candidate['matched_address'];
            $arr['adresse_match_exact'] = $candidate['is_exact_address'];

            $arr['adresses_associees_liste'] = $this->buildAssociatedAddresses($sameImmatriculation, $copro);

            if (empty($arr['nombre_adresses_associees'])) {
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
            [
                'adresse' => $adresse,
                'code_postal' => $codePostal,
                'ville' => $ville,
                'searched_number' => $searchedNumber,
                'searched_postal' => $searchedPostal,
            ],
            [
                'count' => count($results),
            ],
            empty($results) ? 'Adresse non enregistrée dans le RNIC local' : null
        );

        return $results;
    }

    public function normalize(array $item): array
    {
        $representantNom = $this->cleanRepresentativeName(
            $item['representant_legal_nom']
                ?? $item['syndic_nom']
                ?? $item['representant']
                ?? null
        );

        $isSharedHiddenIdentity = $this->isHiddenOpenDataIdentity($representantNom);

        $representantConnu = !empty($representantNom) && !$isSharedHiddenIdentity;

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

            'representant_legal_connu' => $representantConnu,
            'representant_legal_nom' => $representantConnu ? $representantNom : null,
            'representant_legal_type' => $representantConnu ? ($item['representant_legal_type'] ?? 'syndic') : null,
            'message_representant' => $representantConnu ? null : 'Pas de représentant légal connu',

            'syndic_nom' => $representantConnu ? ($item['syndic_nom'] ?? $representantNom) : null,
            'siren_syndic' => $representantConnu ? ($item['siren_syndic'] ?? null) : null,
            'siret_syndic' => $representantConnu ? ($item['siret_syndic'] ?? null) : null,

            'score_match' => $item['score_match'] ?? null,
            'adresse_rnic_match' => $item['adresse_rnic_match'] ?? null,
            'adresse_match_exact' => $item['adresse_match_exact'] ?? false,
            'adresses_associees_liste' => $item['adresses_associees_liste'] ?? [],

            'raw_data' => $item,
        ];
    }

    private function bestAddressMatchForCopro(
        RnicCopropriete $copro,
        string $originalSearched,
        string $searched,
        ?string $searchedNumber,
        ?string $searchedPostal,
        array $searchedWords
    ): array {
        $addresses = $this->candidateAddressesForCopro($copro);

        $best = [
            'score' => 0,
            'matched_address' => null,
            'is_exact_address' => false,
        ];

        foreach ($addresses as $candidateAddress) {
            $candidate = $this->normalizeText($candidateAddress);
            $candidateNumber = $this->extractNumber($candidate);
            $candidatePostal = $this->extractPostalCode($candidateAddress) ?: $copro->code_postal;

            if ($searchedPostal && $candidatePostal && $searchedPostal !== $candidatePostal) {
                continue;
            }

            if ($searchedNumber && $candidateNumber && $searchedNumber !== $candidateNumber) {
                continue;
            }

            $score = $this->scoreAddress($searched, $candidate, $searchedNumber, $searchedWords);

            $exact = $this->isExactEnough($score, $searched, $candidate, $searchedNumber, $candidateNumber);

            if ($score > $best['score']) {
                $best = [
                    'score' => $score,
                    'matched_address' => $candidateAddress,
                    'is_exact_address' => $exact,
                ];
            }
        }

        return $best;
    }

    private function candidateAddressesForCopro(RnicCopropriete $copro): array
    {
        $raw = $copro->raw_data ?? [];

        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }

        $addresses = [
            $copro->adresse_complete,
            $raw['adresse_reference'] ?? null,
            $raw['numero_voie_adresse'] ?? null,
            $raw['adresse_complementaire_1'] ?? null,
            $raw['adresse_complementaire_2'] ?? null,
            $raw['adresse_complementaire_3'] ?? null,
        ];

        return collect($addresses)
            ->filter()
            ->map(function ($address) use ($copro) {
                $address = trim((string) $address);

                if (!preg_match('/\b\d{5}\b/', $address) && $copro->code_postal) {
                    $address .= ' ' . $copro->code_postal;
                }

                if ($copro->ville && !str_contains(Str::ascii(mb_strtolower($address)), Str::ascii(mb_strtolower($copro->ville)))) {
                    $address .= ' ' . $copro->ville;
                }

                return $address;
            })
            ->unique()
            ->values()
            ->toArray();
    }

    private function buildAssociatedAddresses($sameImmatriculation, RnicCopropriete $copro): array
    {
        $items = collect();

        foreach ($sameImmatriculation as $row) {
            foreach ($this->candidateAddressesForCopro($row) as $address) {
                $items->push($address);
            }
        }

        if ($items->isEmpty()) {
            foreach ($this->candidateAddressesForCopro($copro) as $address) {
                $items->push($address);
            }
        }

        return $items
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function isExactEnough(int $score, string $searched, string $candidate, ?string $searchedNumber, ?string $candidateNumber): bool
    {
        if ($searchedNumber && $candidateNumber && $searchedNumber !== $candidateNumber) {
            return false;
        }

        if ($score >= 95) {
            return true;
        }

        $searchedWords = $this->extractImportantWords($searched);
        $candidateWords = $this->extractImportantWords($candidate);

        $common = array_intersect($searchedWords, $candidateWords);

        return $score >= 85 && count($common) >= 2;
    }

    private function normalizeText(?string $text): string
    {
        $text = Str::ascii(mb_strtolower($text ?? ''));

        $text = preg_replace('/\b(rue|r|avenue|av|avenu|boulevard|bd|boul|allee|all|impasse|chemin|ch|route|rte|place|pl|square|sq|cours|crs|quai|q)\b/u', ' ', $text);

        $text = str_replace(['bis', 'ter'], [' bis ', ' ter '], $text);
        $text = preg_replace('/[^a-z0-9 ]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function extractNumber(string $text): ?string
    {
        preg_match('/\b\d+\b/', $text, $matches);

        return $matches[0] ?? null;
    }

    private function extractPostalCode(?string $text): ?string
    {
        preg_match('/\b\d{5}\b/', (string) $text, $matches);

        return $matches[0] ?? null;
    }

    private function extractImportantWords(string $text): array
    {
        $words = array_filter(explode(' ', $text));

        $stopWords = [
            'rue', 'avenue', 'boulevard', 'allee', 'impasse', 'chemin', 'route',
            'place', 'bis', 'ter', 'saint', 'sainte',
        ];

        return array_values(array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) >= 4
                && !is_numeric($word)
                && !in_array($word, $stopWords, true)
                && !preg_match('/^\d{5}$/', $word);
        }));
    }

    private function scoreAddress(string $searched, string $candidate, ?string $streetNumber, array $streetWords): int
    {
        if (!$searched || !$candidate) {
            return 0;
        }

        similar_text($searched, $candidate, $percent);

        $score = (int) $percent;

        if ($streetNumber && preg_match('/\b' . preg_quote($streetNumber, '/') . '\b/', $candidate)) {
            $score += 30;
        }

        foreach ($streetWords as $word) {
            if (str_contains($candidate, $word)) {
                $score += 12;
            }
        }

        return min($score, 100);
    }

    private function cleanRepresentativeName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $name = preg_replace('/\b\d{14}\b/', '', $name);
        $name = preg_replace('/\b\d{9}\b/', '', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return $name ?: null;
    }

    private function isHiddenOpenDataIdentity(?string $name): bool
    {
        if (!$name) {
            return false;
        }

        return str_contains(
            Str::ascii(mb_strtolower($name)),
            'identite non partagee en open data'
        );
    }
}