<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * PappersScraperService v3
 * ─────────────────────────────────────────────────────────────────
 * CORRECTIONS v3 :
 *   ✅ Merge intelligent : chaque source COMPLÈTE les champs manquants
 *   ✅ Ne s'arrête plus si capital_social est vide après INPI
 *   ✅ SIRET extrait depuis Pappers HTML
 *   ✅ Forme juridique extraite depuis Pappers HTML
 *   ✅ Dirigeant extrait depuis Pappers HTML
 *   ✅ INPI parsing robuste (essaie plusieurs chemins JSON)
 *   ✅ Capital formaté proprement (1500 → "1 500 €")
 * ─────────────────────────────────────────────────────────────────
 */
class PappersScraperService
{
    private const PAPPERS_WEB = 'https://www.pappers.fr/entreprise/';
    private const PAPPERS_API = 'https://api.pappers.fr/v2/entreprise';
    private const INPI_API    = 'https://registre-national-entreprises.inpi.fr/api/companies/';

    private const DELAY_MS    = 700;
    private const CACHE_TTL   = 86400; // 24h
    private const TIMEOUT     = 12;

    // ─────────────────────────────────────────
    // ENTRÉE PRINCIPALE
    // ─────────────────────────────────────────

    public function scrapeBySiren(string $siren): array
    {
        $siren = preg_replace('/\D/', '', $siren);
        if (strlen($siren) !== 9) {
            return $this->empty($siren, 'SIREN invalide');
        }

        $cacheKey = "pappers_v3_{$siren}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Partir d'un résultat vide
        $result = $this->empty($siren);

        // ── SOURCE 1 : INPI RNE ──────────────────
        $inpi = $this->fromInpiRne($siren);
        $result = $this->fill($result, $inpi);
        Log::debug("PappersScraper INPI [{$siren}] capital=" . ($result['capital_social'] ?? 'vide'));

        // ── SOURCE 2 : Pappers API (si token dispo) ──
        if ($this->needsCapital($result) && config('services.pappers.key')) {
            $pappers = $this->fromPappersApi($siren);
            $result  = $this->fill($result, $pappers);
            Log::debug("PappersScraper PappersAPI [{$siren}] capital=" . ($result['capital_social'] ?? 'vide'));
        }

        // ── SOURCE 3 : Pappers HTML (toujours tenté pour SIRET + compléments) ──
        // On tente même si on a déjà le capital, car le HTML peut apporter le SIRET
        if ($this->needsMoreData($result)) {
            $html   = $this->fromPappersHtml($siren);
            $result = $this->fill($result, $html);
            Log::debug("PappersScraper HTML [{$siren}] capital=" . ($result['capital_social'] ?? 'vide') . " siret=" . ($result['siret_siege'] ?? 'vide'));
        }

        // ── TOUJOURS mettre l'URL Pappers ──
        $result['url_pappers'] = self::PAPPERS_WEB . $siren;

        if (!$this->isEmpty($result)) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    /**
     * Batch scraping — retourne [ 'siren' => [...], ... ]
     */
    public function scrapeMultiple(array $sirens): array
    {
        $results = [];
        $unique  = array_unique(array_filter(array_map(
            fn($s) => preg_replace('/\D/', '', (string) $s),
            $sirens
        )));

        foreach ($unique as $siren) {
            if (strlen($siren) !== 9) continue;
            $results[$siren] = $this->scrapeBySiren($siren);
            usleep(self::DELAY_MS * 1000);
        }

        return $results;
    }

    // ─────────────────────────────────────────
    // SOURCE 1 — INPI RNE
    // Essaie plusieurs chemins JSON car la structure change
    // ─────────────────────────────────────────

    private function fromInpiRne(string $siren): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'Accept'     => 'application/json',
                    'User-Agent' => 'DataRocket/2.0',
                ])
                ->get(self::INPI_API . $siren);

            if ($response->failed()) {
                return $this->empty($siren, 'INPI ' . $response->status());
            }

            $json = $response->json();

            // Aplatir pour chercher n'importe où dans la structure
            $flat = $this->flatten($json);

            // Capital — essaie plusieurs chemins
            $montant = $this->pick($flat, [
                'montant',          // capital.montant
                'capitalMontant',
                'capital_montant',
                'montantCapital',
            ]);

            $devise = $this->pick($flat, ['devise', 'currency', 'deviseCapital']) ?? 'EUR';

            $capital = null;
            if ($montant !== null && $montant !== '') {
                $capital = number_format((float) $montant, 0, ',', ' ') . ' ' . $devise;
            }

            // SIRET siège
            $siret = $this->pick($flat, [
                'siretSiege',
                'siret_siege',
                'siret',
                'siretEtablissementPrincipal',
            ]);

            // Dirigeant
            $prenom  = $this->pick($flat, ['prenom', 'prenoms', 'firstName', 'prenom1UniteLegale']);
            $nomDir  = $this->pick($flat, ['nomDirigeant', 'nomRepresentant']);
            // fallback sur clé "nom" mais éviter de prendre la dénomination
            if (!$nomDir) {
                foreach ($flat as $k => $v) {
                    if (str_contains(strtolower($k), 'nom')
                        && !str_contains(strtolower($k), 'denomination')
                        && !str_contains(strtolower($k), 'commercial')
                        && is_string($v) && strlen($v) < 60
                        && strtoupper($v) !== $v // pas tout en majuscules (= raison sociale)
                    ) {
                        $nomDir = $v;
                        break;
                    }
                }
            }
            $dirigeant = trim(($prenom ?? '') . ' ' . ($nomDir ?? '')) ?: null;

            return [
                'siren'            => $siren,
                'nom'              => $this->pick($flat, [
                                        'denomination',
                                        'denominationSociale',
                                        'denominationUsuelle',
                                        'nom_entreprise',
                                     ]),
                'siret_siege'      => $siret ? preg_replace('/\D/', '', $siret) : null,
                'capital_social'   => $capital,
                'forme_juridique'  => $this->pick($flat, [
                                        'libelleFormeJuridique',
                                        'libelle',
                                        'formeJuridique',
                                        'forme_juridique',
                                        'categorieJuridique',
                                     ]),
                'activite'         => $this->pick($flat, [
                                        'libelleActivitePrincipale',
                                        'libelleSectionActivite',
                                        'activitePrincipale',
                                        'activitePrincipaleUniteLegale',
                                     ]),
                'date_creation'    => $this->pick($flat, [
                                        'dateImmatriculation',
                                        'dateCreation',
                                        'dateCreationUniteLegale',
                                     ]),
                'dirigeant'        => $dirigeant ?: null,
                'effectif'         => $this->pick($flat, [
                                        'effectif',
                                        'trancheEffectif',
                                        'trancheEffectifsUniteLegale',
                                     ]),
                'chiffre_affaires' => null,
                'source'           => 'inpi_rne',
            ];

        } catch (\Exception $e) {
            Log::debug("INPI RNE [{$siren}]: " . $e->getMessage());
            return $this->empty($siren, 'INPI exception');
        }
    }

    // ─────────────────────────────────────────
    // SOURCE 2 — PAPPERS API OFFICIELLE
    // ─────────────────────────────────────────

    private function fromPappersApi(string $siren): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get(self::PAPPERS_API, [
                    'siren'     => $siren,
                    'api_token' => config('services.pappers.key'),
                ]);

            if ($response->failed()) {
                return $this->empty($siren, 'Pappers API ' . $response->status());
            }

            $json  = $response->json();
            $siege = $json['siege'] ?? [];

            $capital = null;
            if (!empty($json['capital'])) {
                $capital = number_format((float) $json['capital'], 0, ',', ' ') . ' €';
            }

            $dirigeants = $json['representants'] ?? $json['dirigeants'] ?? [];
            $dirigeant  = null;
            if (!empty($dirigeants)) {
                $d         = $dirigeants[0];
                $dirigeant = trim(($d['prenoms'] ?? '') . ' ' . ($d['nom'] ?? '')) ?: null;
            }

            $ca = null;
            if (!empty($json['finances'])) {
                $fin = end($json['finances']);
                $caRaw = $fin['chiffre_affaires'] ?? null;
                if ($caRaw) $ca = number_format((float) $caRaw, 0, ',', ' ') . ' €';
            }

            return [
                'siren'            => $siren,
                'nom'              => $json['nom_entreprise'] ?? $json['denomination'] ?? null,
                'siret_siege'      => $siege['siret'] ?? null,
                'capital_social'   => $capital,
                'forme_juridique'  => $json['forme_juridique'] ?? null,
                'activite'         => $json['domaine_activite'] ?? $json['libelle_code_naf'] ?? null,
                'date_creation'    => $json['date_creation'] ?? null,
                'dirigeant'        => $dirigeant,
                'effectif'         => $json['effectif'] ?? $json['tranche_effectif'] ?? null,
                'chiffre_affaires' => $ca,
                'source'           => 'pappers_api',
            ];

        } catch (\Exception $e) {
            return $this->empty($siren, 'Pappers API exception');
        }
    }

    // ─────────────────────────────────────────
    // SOURCE 3 — PAPPERS HTML SCRAPING
    // Extrait capital, SIRET, forme juridique, dirigeant
    // ─────────────────────────────────────────

    private function fromPappersHtml(string $siren): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders($this->browserHeaders())
                ->get(self::PAPPERS_WEB . $siren);

            if ($response->failed()) {
                return $this->empty($siren, 'HTML ' . $response->status());
            }

            $html = $response->body();

            if (strlen($html) < 500
                || str_contains($html, 'cf-browser-verification')
                || str_contains($html, 'Checking your browser')
            ) {
                return $this->empty($siren, 'cloudflare_block');
            }

            // Tenter __NEXT_DATA__ d'abord
            if (preg_match(
                '/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/s',
                $html, $m
            )) {
                $jsonData = json_decode($m[1], true);
                if ($jsonData) {
                    $flat = $this->flatten($jsonData);
                    return [
                        'siren'           => $siren,
                        'nom'             => $this->pick($flat, ['denomination', 'nom_entreprise', 'denominationSociale']),
                        'siret_siege'     => $this->cleanSiret($this->pick($flat, ['siret', 'siret_siege', 'siretSiege'])),
                        'capital_social'  => $this->formatCapital($this->pick($flat, ['capital', 'capital_social', 'capitalSocial', 'montant'])),
                        'forme_juridique' => $this->pick($flat, ['forme_juridique', 'formeJuridique', 'libelle']),
                        'activite'        => $this->pick($flat, ['domaine_activite', 'libelle_code_naf', 'activite', 'libelleCodeNaf']),
                        'date_creation'   => $this->pick($flat, ['date_creation', 'dateCreation']),
                        'dirigeant'       => $this->pick($flat, ['nom_dirigeant', 'nomDirigeant', 'dirigeant']),
                        'effectif'        => $this->pick($flat, ['effectif', 'tranche_effectif']),
                        'chiffre_affaires'=> $this->formatCapital($this->pick($flat, ['chiffre_affaires', 'chiffreAffaires'])),
                        'source'          => 'pappers_html_next',
                    ];
                }
            }

            // Fallback regex sur HTML brut
            return $this->htmlRegexExtract($html, $siren);

        } catch (\Exception $e) {
            return $this->empty($siren, 'HTML exception');
        }
    }

    /**
     * Extraction par regex depuis le HTML brut de Pappers
     * Couvre capital, SIRET, forme juridique, dirigeant
     */
    private function htmlRegexExtract(string $html, string $siren): array
    {
        // ── Capital social ──
        $capital = $this->formatCapital($this->regex($html, [
            // Format "1 500 €" ou "1500 €" dans du texte
            '/[Cc]apital\s+social\s*:?\s*(?:<[^>]*>)*\s*([0-9][0-9\s\.,]*\s*(?:€|EUR|euros?))/i',
            '/[Cc]apital\s*:?\s*(?:<[^>]*>)*\s*([0-9][0-9\s\.,]*\s*(?:€|EUR|euros?))/i',
            // Format JSON embarqué
            '/"capital"\s*:\s*"?([0-9]+(?:\.[0-9]+)?)"?/i',
            '/capitalSocial["\s:]+([0-9]+(?:\.[0-9]+)?)/i',
            '/capital_social["\s:]+([0-9]+(?:\.[0-9]+)?)/i',
            '/montantCapital["\s:]+([0-9]+(?:\.[0-9]+)?)/i',
            '/montant["\s:]+([0-9]+)\s*,\s*["\s]*devise["\s:]+["\s]*EUR/i',
            '/data-capital="([^"]+)"/i',
        ]));

        // ── SIRET siège ── (14 chiffres)
        $siret = $this->cleanSiret($this->regex($html, [
            '/SIRET\s+si(?:è|e)ge\s*:?\s*(?:<[^>]*>)*\s*([0-9\s]{14,18})/i',
            '/SIRET\s*:?\s*(?:<[^>]*>)*\s*([0-9\s]{14,18})/i',
            '/siret["\s:]+["\s]*([0-9]{14})/i',
            '/siret_siege["\s:]+["\s]*([0-9]{14})/i',
            '/siretSiege["\s:]+["\s]*([0-9]{14})/i',
            '/data-siret="([0-9]{14})"/i',
            // Numéro 14 chiffres brut dans le texte
            '/\b(' . $siren . '[0-9]{5})\b/i',
        ]));

        // ── Forme juridique ──
        $forme = $this->regex($html, [
            '/[Ff]orme\s+juridique\s*:?\s*(?:<[^>]*>)+([^<]{3,50})</i',
            '/"forme_juridique"\s*:\s*"([^"]{3,80})"/i',
            '/formeJuridique["\s:]+["\s]*([^",\}\]{3,80})/i',
            '/libelleFormeJuridique["\s:]+["\s]*([^",\}]{3,80})/i',
        ]);

        // ── Dirigeant principal ──
        $dirigeant = $this->regex($html, [
            '/[Dd]irigeant\s*:?\s*(?:<[^>]*>)+([^<]{2,80})</i',
            '/[Pp]r(?:é|e)sident\s*:?\s*(?:<[^>]*>)+([^<]{2,80})</i',
            '/[Gg](?:é|e)rant\s*:?\s*(?:<[^>]*>)+([^<]{2,80})</i',
            '/"nom_dirigeant"\s*:\s*"([^"]{2,80})"/i',
        ]);

        // ── Activité / NAF ──
        $activite = $this->regex($html, [
            '/[Aa]ctivit(?:é|e)\s*:?\s*(?:<[^>]*>)+([^<]{3,100})</i',
            '/[Aa]ctivit(?:é|e)\s+principale\s*:?\s*(?:<[^>]*>)+([^<]{3,100})</i',
            '/"domaine_activite"\s*:\s*"([^"]{3,100})"/i',
            '/"libelle_code_naf"\s*:\s*"([^"]{3,100})"/i',
        ]);

        // ── Date de création ──
        $dateCreation = $this->regex($html, [
            '/[Cc]r(?:é|e)(?:a|é)(?:e|é)\s+le\s*:?\s*(?:<[^>]*>)*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i',
            '/"date_creation"\s*:\s*"([^"]{8,10})"/i',
            '/dateCreation["\s:]+["\s]*([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
        ]);

        return [
            'siren'            => $siren,
            'nom'              => $this->regex($html, [
                                    '/<h1[^>]*>([^<|]+)/i',
                                    '/property="og:title"\s+content="([^"|<]+)/i',
                                  ]),
            'siret_siege'      => $siret,
            'capital_social'   => $capital,
            'forme_juridique'  => $forme,
            'activite'         => $activite,
            'date_creation'    => $dateCreation,
            'dirigeant'        => $dirigeant,
            'effectif'         => $this->regex($html, [
                                    '/[Ee]ffectif\s*:?\s*(?:<[^>]*>)+([^<]{1,30})</i',
                                    '/"effectif"\s*:\s*"([^"]{1,30})"/i',
                                  ]),
            'chiffre_affaires' => null,
            'source'           => 'pappers_html',
        ];
    }

    // ─────────────────────────────────────────
    // MERGE INTELLIGENT
    // Remplit les champs vides sans écraser les champs déjà remplis
    // ─────────────────────────────────────────

    /**
     * Remplir les trous du résultat de base avec les données de la source extra
     */
    private function fill(array $base, array $extra): array
    {
        $fields = [
            'nom', 'siret_siege', 'capital_social', 'forme_juridique',
            'activite', 'date_creation', 'dirigeant', 'effectif', 'chiffre_affaires',
        ];

        foreach ($fields as $field) {
            if (
                ($base[$field] ?? null) === null
                || ($base[$field] ?? '') === ''
                || $base[$field] === '-'
            ) {
                if (!empty($extra[$field])) {
                    $base[$field] = $extra[$field];
                    // Mettre à jour la source si ce champ était vide
                    if ($field === 'capital_social') {
                        $base['source'] = $extra['source'] ?? $base['source'];
                    }
                }
            }
        }

        return $base;
    }

    // ─────────────────────────────────────────
    // CHECKS
    // ─────────────────────────────────────────

    private function needsCapital(array $data): bool
    {
        return empty($data['capital_social']);
    }

    private function needsMoreData(array $data): bool
    {
        return empty($data['capital_social'])
            || empty($data['siret_siege'])
            || empty($data['forme_juridique']);
    }

    private function isEmpty(array $data): bool
    {
        return empty($data['capital_social'])
            && empty($data['nom'])
            && empty($data['siret_siege']);
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $fullKey));
            } elseif ($value !== null && $value !== '') {
                $result[$fullKey]        = $value;
                $result[(string)$key] ??= $value;
            }
        }
        return $result;
    }

    private function pick(array $flat, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($flat[$key]) && $flat[$key] !== '') return $flat[$key];
            foreach ($flat as $fKey => $value) {
                if ($value !== '' && is_string($fKey)
                    && str_contains(strtolower($fKey), strtolower($key))
                ) {
                    return $value;
                }
            }
        }
        return null;
    }

    private function regex(string $html, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $v = trim(strip_tags(html_entity_decode($matches[1])));
                if ($v !== '') return $v;
            }
        }
        return null;
    }

    private function formatCapital(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') return null;
        $raw = (string) $raw;

        // Déjà formaté
        if (str_contains($raw, '€') || stripos($raw, 'eur') !== false) {
            return trim(preg_replace('/\s+/', ' ', $raw));
        }

        // Nombre avec virgule décimale française (1500,00)
        if (preg_match('/^([0-9]+)[,.]([0-9]{2})$/', $raw, $m)) {
            return number_format((float) $m[1], 0, ',', ' ') . ' €';
        }

        // Nombre brut
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits !== '' && strlen($digits) >= 1) {
            return number_format((int) $digits, 0, ',', ' ') . ' €';
        }

        return $raw;
    }

    private function cleanSiret(?string $raw): ?string
    {
        if (!$raw) return null;
        $cleaned = preg_replace('/\D/', '', $raw);
        return strlen($cleaned) === 14 ? $cleaned : null;
    }

    private function browserHeaders(): array
    {
        return [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'fr-FR,fr;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer'         => 'https://www.pappers.fr/',
            'Cache-Control'   => 'max-age=0',
        ];
    }

    private function empty(string $siren, string $error = ''): array
    {
        return [
            'siren'            => $siren,
            'nom'              => null,
            'siret_siege'      => null,
            'capital_social'   => null,
            'forme_juridique'  => null,
            'activite'         => null,
            'date_creation'    => null,
            'dirigeant'        => null,
            'effectif'         => null,
            'chiffre_affaires' => null,
            'url_pappers'      => self::PAPPERS_WEB . $siren,
            'source'           => 'scraper',
            'error'            => $error ?: null,
        ];
    }
}