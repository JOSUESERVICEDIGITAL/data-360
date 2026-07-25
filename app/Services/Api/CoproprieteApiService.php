<?php

namespace App\Services\Api;

use App\Models\Back\RnicCopropriete;
use App\Services\CoproprieteService;
use Illuminate\Support\Str;

class CoproprieteApiService
{
    /**
     * Valeurs considérées comme "vides" / placeholder dans le CSV RNIC.
     * Le CSV utilise parfois des libellés textuels au lieu de NULL pour
     * indiquer l'absence de donnée — il faut les traiter comme vides,
     * sinon ils sont interprétés à tort comme un vrai nom de représentant
     * ou un vrai identifiant.
     */
    private const PLACEHOLDER_VALUES = [
        'non connu',
        'non connue',
        'non renseigne',
        'non renseignee',
        'non communique',
        'non communiquee',
        'non disponible',
        'inconnu',
        'inconnue',
        'n a',
        'na',
        'nc',
        '-',
        '--',
        '',
    ];

    public function __construct(
        protected ApiLoggerService   $logger,
        protected CoproprieteService $coproprieteService  // ← nouveau
    ) {}

    public function searchByAddress(string $adresse, ?string $codePostal = null, ?string $ville = null): array
    {
        // Extraire le CP depuis l'adresse brute si BAN a renvoyé un mauvais CP
        $cpDepuisAdresse = $this->extractPostalCode($adresse);

        // Priorité : CP extrait de l'adresse > CP du géocodage
        $codePostalFinal = $cpDepuisAdresse ?? $codePostal;

        // ── 1. Recherche locale
        $results = $this->searchLocal($adresse, $codePostalFinal, $ville);

        // ── 2. Fallback API RNIC publique si rien trouvé
        if (empty($results)) {
            $results = $this->searchRnicApi($adresse, $codePostalFinal, $ville);
        }

        return $results;
    }

    // ═══════════════════════════════════════════════════════════════
    // RECHERCHE LOCALE — inchangée
    // ═══════════════════════════════════════════════════════════════
    private function searchLocal(string $adresse, ?string $codePostal, ?string $ville): array
    {
        $searched       = $this->normalizeText($adresse);
        $searchedNumber = $this->extractNumber($searched);
        $searchedPostal = $codePostal ?: $this->extractPostalCode($adresse);
        $searchedWords  = $this->extractImportantWords($searched);

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
                    'score'            => $best['score'],
                    'matched_address'  => $best['matched_address'],
                    'is_exact_address' => $best['is_exact_address'],
                    'copro'            => $copro,
                ];
            })
            ->filter(fn($item) => $item['score'] >= 70 && !empty($item['matched_address']))
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
            $arr['score_match']              = $candidate['score'];
            $arr['adresse_rnic_match']       = $candidate['matched_address'];
            $arr['adresse_match_exact']      = $candidate['is_exact_address'];
            $arr['adresses_associees_liste'] = $this->buildAssociatedAddresses($sameImmatriculation, $copro);

            if (empty($arr['nombre_adresses_associees'])) {
                $arr['nombre_adresses_associees'] = count($arr['adresses_associees_liste']);
            }

            $arr['_source'] = 'local';
            $results[] = $arr;
        }

        $this->logger->log(
            'RNIC_LOCAL',
            'rnic_coproprietes',
            $adresse,
            null,
            !empty($results),
            ['adresse' => $adresse, 'code_postal' => $codePostal, 'ville' => $ville],
            ['count' => count($results)],
            empty($results) ? 'Adresse non enregistrée dans le RNIC local' : null
        );

        return $results;
    }

    // ═══════════════════════════════════════════════════════════════
    // FALLBACK API RNIC PUBLIQUE — inchangé
    // ═══════════════════════════════════════════════════════════════
    private function searchRnicApi(string $adresse, ?string $codePostal, ?string $ville): array
    {
        $queryAdresse = trim($adresse . ($codePostal ? " $codePostal" : '') . ($ville ? " $ville" : ''));

        $apiResult = $this->coproprieteService->rechercherParAdresse($queryAdresse);

        if (!$apiResult['success'] || empty($apiResult['data'])) {
            $this->logger->log(
                'RNIC_API',
                'rnic_api_public',
                $adresse,
                null,
                false,
                ['adresse' => $queryAdresse],
                [],
                'Aucun résultat depuis l\'API RNIC publique'
            );
            return [];
        }

        $results = collect($apiResult['data'])
            ->map(fn($item) => $this->mapRnicApiToLocalFormat($item, $adresse))
            ->values()
            ->all();

        $this->logger->log(
            'RNIC_API',
            'rnic_api_public',
            $adresse,
            null,
            true,
            ['adresse' => $queryAdresse],
            ['count' => count($results)]
        );

        return $results;
    }

    /**
     * Convertit la réponse normalisée du CoproprieteService
     * vers le format attendu par normalize() et le moteur.
     */
    private function mapRnicApiToLocalFormat(array $item, string $adresseOrigine): array
    {
        $representant = $item['representant_legal'] ?? [];

        return [
            // Identifiants
            'numero_immatriculation'    => $item['id'] ?? null,
            'nom_copropriete'           => $item['nom'] ?? null,
            'siren_copropriete'         => null,

            // Adresse
            'adresse_complete'          => $item['adresse'] ?? null,
            'code_postal'               => $item['code_postal'] ?? null,
            'ville'                     => $item['ville'] ?? null,

            // Lots
            'nombre_lots_total'         => $item['nb_lots_total'] ?? null,
            'nombre_lots_habitation'    => $item['nb_lots_habitation'] ?? null,
            'nombre_batiments'          => null,
            'nombre_adresses_associees' => null,

            // Statut
            'statut'                    => null,
            'date_immatriculation'      => $item['date_immatriculation'] ?? null,

            // Représentant légal
            'representant_legal_nom'    => ($representant['present'] ?? false)
                ? ($representant['nom'] ?? null)
                : null,
            'representant_legal_type'   => $representant['type'] ?? null,
            'syndic_nom'                => ($representant['present'] ?? false)
                ? ($representant['nom'] ?? null)
                : null,
            'siren_syndic'              => null,
            'siret_syndic'              => $representant['siret'] ?? null,

            // Procédures
            'procedures_en_cours'       => $item['procedures_en_cours'] ?? [],

            // Meta
            'score_match'               => 80, // score par défaut API publique
            'adresse_rnic_match'        => $item['adresse'] ?? null,
            'adresse_match_exact'       => false,
            'adresses_associees_liste'  => [],
            '_source'                   => 'rnic_api_public',
            '_lien_officiel'            => $item['lien_officiel'] ?? null,
            'raw_data'                  => $item,
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // NORMALIZE — FIX PRINCIPAL : filtre anti-placeholder
    // ═══════════════════════════════════════════════════════════════
   public function normalize(array $item): array
{
    // Extraire et aplatir raw_data pour accéder aux vrais champs RNIC 2024
    $rawData = $item['raw_data'] ?? [];
    if (is_string($rawData)) {
        $rawData = json_decode($rawData, true) ?: [];
    }
    if (!is_array($rawData)) {
        $rawData = [];
    }

    // raw_data imbriqué
    $rawNested = $rawData['raw_data'] ?? [];
    if (is_string($rawNested)) {
        $rawNested = json_decode($rawNested, true) ?: [];
    }

    // Fusion complète : item + raw_data imbriqué + raw_data niveau 1
    // raw_data niveau 1 est prioritaire car contient les vrais champs RNIC
    $all = array_merge($item, is_array($rawNested) ? $rawNested : [], $rawData);

    // ── REPRÉSENTANT NOM — cherche dans tous les champs possibles ──
    $representantNom = null;
    $nomKeys = [
        'raison_sociale_representant_legal',  // vrai champ RNIC 2024
        'identification_representant_legal',   // vrai champ RNIC 2024
        'representant_legal_nom',              // colonne DB normalisée
        'syndic_nom',                          // ancien format
        'nom_syndic',                          // variante
    ];
    foreach ($nomKeys as $key) {
        $val = $all[$key] ?? null;
        if ($val !== null && trim((string)$val) !== '') {
            $representantNom = (string)$val;
            break;
        }
    }

    // ── SIREN — cherche dans tous les champs possibles ────────────
    $sirenSyndic = null;
    $sirenKeys = [
        'siren_representant_legal',   // vrai champ RNIC 2024
        'siren_syndic',               // ancien format
    ];
    foreach ($sirenKeys as $key) {
        $val = preg_replace('/\D/', '', (string)($all[$key] ?? ''));
        if (strlen($val) === 9) {
            $sirenSyndic = $val;
            break;
        }
    }

    // ── SIRET — cherche dans tous les champs possibles ────────────
    $siretSyndic = null;
    $siretKeys = [
        'siret_representant_legal',   // vrai champ RNIC 2024
        'siret_syndic',               // ancien format
    ];
    foreach ($siretKeys as $key) {
        $val = preg_replace('/\D/', '', (string)($all[$key] ?? ''));
        if (strlen($val) === 14) {
            $siretSyndic = $val;
            break;
        }
    }

    // ── NETTOYAGE NOM ─────────────────────────────────────────────
    if ($representantNom) {
        $check = strtolower(trim($representantNom));
        $placeholders = ['non connu', 'non renseigne', 'inconnu', '-', '', 'n a', 'na', 'nc'];
        if (in_array($check, $placeholders, true)
            || str_contains($check, 'identite non partagee')) {
            $representantNom = null;
        }
    }

    // ── TYPE SYNDIC ───────────────────────────────────────────────
    $typeSyndic = $all['type_syndic'] ?? $all['representant_legal_type'] ?? null;

    // ── MANDAT ───────────────────────────────────────────────────
    $mandatEnCours = $all['mandat_en_cours'] ?? $all['statut'] ?? null;
    $dateFinMandat = $all['date_fin_dernier_mandat'] ?? null;

    // ── REPRÉSENTANT CONNU ? ──────────────────────────────────────
    $representantConnu = !empty($representantNom) || !empty($sirenSyndic) || !empty($siretSyndic);

    // ── NOM COPROPRIÉTÉ ───────────────────────────────────────────
    $nomCopro = $all['nom_usage_copropriete'] ?? $all['nom_copropriete'] ?? null;

    // ── ADRESSE ───────────────────────────────────────────────────
    $adresseComplete = $all['adresse_reference']
                    ?? $all['adresse_de_reference']
                    ?? $all['adresse_complete']
                    ?? $all['adresse_rnic_match']
                    ?? null;

    $codePostal = $all['code_postal_adresse'] ?? $all['code_postal'] ?? null;
    $ville      = $all['commune_adresse'] ?? $all['nom_officiel_commune'] ?? $all['ville'] ?? null;

    // ── LOTS ──────────────────────────────────────────────────────
    $nbLotsTotal      = $all['nombre_total_lots']      ?? $all['nombre_lots_total']      ?? null;
    $nbLotsHabitation = $all['nombre_lots_habitation'] ?? null;

    // ── MESSAGE ───────────────────────────────────────────────────
    if ($representantConnu) {
        $messageRep = null;
    } elseif (!empty($mandatEnCours)) {
        $messageRep = $mandatEnCours;
    } else {
        $messageRep = 'Pas de représentant légal connu';
    }

    return [
        'numero_immatriculation'            => $all['numero_immatriculation']    ?? null,
        'nom_copropriete'                   => $nomCopro,
        'siren_copropriete'                 => $all['siren_copropriete']         ?? null,
        'nombre_lots_total'                 => $nbLotsTotal,
        'nombre_lots_habitation'            => $nbLotsHabitation,
        'nombre_batiments'                  => $all['nombre_batiments']          ?? null,
        'nombre_adresses_associees'         => $all['nombre_adresses_associees'] ?? null,
        'statut'                            => $mandatEnCours,
        'mandat_en_cours'                   => $mandatEnCours,
        'date_fin_dernier_mandat'           => $dateFinMandat,
        'date_immatriculation'              => $all['date_immatriculation']      ?? null,
        'representant_legal_connu'          => $representantConnu,
        'representant_legal_nom'            => $representantConnu ? $representantNom : null,
        'representant_legal_type'           => $representantConnu ? $typeSyndic   : null,
        'syndic_nom'                        => $representantConnu ? $representantNom : null,
        'type_syndic'                       => $representantConnu ? $typeSyndic   : null,
        'siren_syndic'                      => $representantConnu ? $sirenSyndic  : null,
        'siret_syndic'                      => $representantConnu ? $siretSyndic  : null,
        'siren_representant_legal'          => $representantConnu ? $sirenSyndic  : null,
        'siret_representant_legal'          => $representantConnu ? $siretSyndic  : null,
        'raison_sociale_representant_legal' => $representantConnu ? $representantNom : null,
        'identification_representant_legal' => $representantConnu ? $representantNom : null,
        'message_representant'              => $messageRep,
        'adresse_complete'                  => $adresseComplete,
        'adresse_reference'                 => $adresseComplete,
        'code_postal'                       => $codePostal,
        'code_postal_adresse'               => $codePostal,
        'ville'                             => $ville,
        'commune_adresse'                   => $ville,
        'nom_officiel_commune'              => $ville,
        'score_match'                       => $all['score_match']               ?? null,
        'adresse_rnic_match'                => $all['adresse_rnic_match']        ?? null,
        'adresse_match_exact'               => $all['adresse_match_exact']       ?? false,
        'adresses_associees_liste'          => $all['adresses_associees_liste']  ?? [],
        '_source'                           => $all['_source']                   ?? 'local',
        '_lien_officiel'                    => $all['_lien_officiel']            ?? null,
        'raw_data'                          => $rawData ?: $item,
    ];
}

    // ─────────────────────────────────────────────────────────────
    // DÉTECTION DE VALEUR PLACEHOLDER (FIX PRINCIPAL)
    // ─────────────────────────────────────────────────────────────

    /**
     * Détecte si une valeur texte est en réalité un "vide déguisé"
     * (ex: "non connu", "inconnu", "-", etc.) plutôt qu'une vraie donnée.
     * Normalise accents/casse/ponctuation avant comparaison.
     */
    private function isPlaceholderValue(?string $value): bool
    {
        if ($value === null) {
            return true;
        }

        $normalized = Str::ascii(mb_strtolower(trim($value)));
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);
        $normalized = trim($normalized);

        if ($normalized === '') {
            return true;
        }

        return in_array($normalized, self::PLACEHOLDER_VALUES, true);
    }

    /**
     * Nettoie un identifiant (SIREN/SIRET) : enlève les valeurs
     * placeholder et ne garde que les chiffres si la valeur est valide.
     */
    private function cleanIdentifier(?string $value): ?string
    {
        if ($this->isPlaceholderValue($value)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    // ═══════════════════════════════════════════════════════════════
    // MÉTHODES PRIVÉES — matching d'adresse
    // ═══════════════════════════════════════════════════════════════

    private function extractStreetType(?string $text): ?string
    {
        $text = Str::ascii(mb_strtolower($text ?? ''));
        $map  = [
            'rue'       => ['rue', 'r'],
            'avenue'    => ['avenue', 'av', 'avenu'],
            'boulevard' => ['boulevard', 'bd', 'boul'],
            'allee'     => ['allee', 'all'],
            'chemin'    => ['chemin', 'ch'],
            'route'     => ['route', 'rte'],
            'impasse'   => ['impasse'],
            'place'     => ['place', 'pl'],
            'square'    => ['square', 'sq'],
            'cours'     => ['cours', 'crs'],
            'quai'      => ['quai'],
        ];
        foreach ($map as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (preg_match('/\b' . preg_quote($alias, '/') . '\b/u', $text)) {
                    return $canonical;
                }
            }
        }
        return null;
    }

    private function bestAddressMatchForCopro(RnicCopropriete $copro, string $originalSearched, string $searched, ?string $searchedNumber, ?string $searchedPostal, array $searchedWords): array
    {
        $addresses = $this->candidateAddressesForCopro($copro);
        $best      = ['score' => 0, 'matched_address' => null, 'is_exact_address' => false];

        foreach ($addresses as $candidateAddress) {
            $candidate       = $this->normalizeText($candidateAddress);
            $candidateNumber = $this->extractNumber($candidate);
            $candidatePostal = $this->extractPostalCode($candidateAddress) ?: $copro->code_postal;

            $searchedStreetType  = $this->extractStreetType($originalSearched);
            $candidateStreetType = $this->extractStreetType($candidateAddress);

            if ($searchedStreetType && $candidateStreetType && $searchedStreetType !== $candidateStreetType) continue;
            if ($searchedPostal && $candidatePostal && $searchedPostal !== $candidatePostal) continue;
            if ($searchedNumber && $candidateNumber && $searchedNumber !== $candidateNumber) continue;
            if ($searchedNumber && !$candidateNumber) continue;

            $searchedStreetWords  = $this->extractStreetWordsOnly($searched);
            $candidateStreetWords = $this->extractStreetWordsOnly($candidate);

            if (!empty($searchedStreetWords)) {
                foreach ($searchedStreetWords as $word) {
                    if (!in_array($word, $candidateStreetWords, true)) continue 2;
                }
            }

            $score = $this->scoreAddress($searched, $candidate, $searchedNumber, $searchedWords);
            $exact = $this->isExactEnough($score, $searched, $candidate, $searchedNumber, $candidateNumber);

            if ($score > $best['score']) {
                $best = ['score' => $score, 'matched_address' => $candidateAddress, 'is_exact_address' => $exact];
            }
        }

        return $best;
    }

    private function extractStreetWordsOnly(string $text): array
    {
        $words     = array_filter(explode(' ', $text));
        $stopWords = ['rue', 'avenue', 'boulevard', 'allee', 'impasse', 'chemin', 'route', 'place', 'bis', 'ter', 'saint', 'sainte', 'marseille', 'montpellier', 'paris', 'lyon', 'toulouse', 'nice', 'nantes', 'bordeaux', 'lille', 'rennes', 'ciotat'];
        return array_values(array_filter($words, fn($w) => strlen($w) >= 4 && !is_numeric($w) && !in_array($w, $stopWords, true) && !preg_match('/^\d{5}$/', $w)));
    }

    /**
     * FIX : on filtre maintenant les adresses placeholder (ex: "non connu"
     * dans adresse_complementaire_3) qui ne devraient pas être utilisées
     * comme candidates pour le matching d'adresse.
     */
    private function candidateAddressesForCopro(RnicCopropriete $copro): array
    {
        $raw = $copro->raw_data ?? [];
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];

        $addresses = [
            $copro->adresse_complete,
            $raw['adresse_reference']          ?? null,
            $raw['adresse_de_reference']       ?? null, // nouveau nom CSV
            $raw['numero_voie_adresse']        ?? null,
            $raw['adresse_complementaire_1']   ?? null,
            $raw['adresse_complementaire_2']   ?? null,
            $raw['adresse_complementaire_3']   ?? null,
        ];

        return collect($addresses)
            ->filter(fn($a) => $a && !$this->isPlaceholderValue($a))
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
        return $items->filter()->unique()->values()->toArray();
    }

    private function isExactEnough(int $score, string $searched, string $candidate, ?string $searchedNumber, ?string $candidateNumber): bool
    {
        if ($searchedNumber && $candidateNumber && $searchedNumber !== $candidateNumber) return false;
        if ($score >= 95) return true;
        $common = array_intersect($this->extractImportantWords($searched), $this->extractImportantWords($candidate));
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
        if (preg_match('/\b\d+\s*\/\s*\d+\b/', $text, $matches)) return str_replace(' ', '', $matches[0]);
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
        $words     = array_filter(explode(' ', $text));
        $stopWords = ['rue', 'avenue', 'boulevard', 'allee', 'impasse', 'chemin', 'route', 'place', 'bis', 'ter', 'saint', 'sainte'];
        return array_values(array_filter($words, fn($w) => strlen($w) >= 4 && !is_numeric($w) && !in_array($w, $stopWords, true) && !preg_match('/^\d{5}$/', $w)));
    }

    private function scoreAddress(string $searched, string $candidate, ?string $streetNumber, array $streetWords): int
    {
        if (!$searched || !$candidate) return 0;
        similar_text($searched, $candidate, $percent);
        $score = (int) $percent;
        if ($streetNumber && preg_match('/\b' . preg_quote($streetNumber, '/') . '\b/', $candidate)) $score += 30;
        foreach ($streetWords as $word) {
            if (str_contains($candidate, $word)) $score += 12;
        }
        return min($score, 100);
    }

    /**
     * FIX : nettoie un nom de représentant en enlevant SIREN/SIRET
     * embarqués, ET neutralise les valeurs placeholder (ex: "non connu")
     * qui ne sont pas de vrais noms de syndic.
     */
    private function cleanRepresentativeName(?string $name): ?string
    {
        if ($this->isPlaceholderValue($name)) {
            return null;
        }

        $name = preg_replace('/\b\d{14}\b/', '', $name);
        $name = preg_replace('/\b\d{9}\b/', '', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        // Après nettoyage des chiffres, vérifier à nouveau si le
        // résultat n'est pas devenu vide ou un placeholder.
        return $this->isPlaceholderValue($name) ? null : ($name ?: null);
    }

    private function isHiddenOpenDataIdentity(?string $name): bool
    {
        if (!$name) return false;
        return str_contains(Str::ascii(mb_strtolower($name)), 'identite non partagee en open data');
    }
}
