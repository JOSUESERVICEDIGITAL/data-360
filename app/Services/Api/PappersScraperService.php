<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * PappersScraperService
 * ─────────────────────────────────────────────────────────────────────────
 * Scrape https://www.pappers.fr/entreprise/{siren}
 * Boucle sur une liste de SIRENs et extrait capital social + infos clés.
 * Utilise le JSON Next.js (__NEXT_DATA__) en priorité, fallback HTML regex.
 * Cache 24h pour éviter de re-scraper.
 */
class PappersScraperService
{
    private const BASE_URL  = 'https://www.pappers.fr/entreprise/';
    private const DELAY_MS  = 900;    // délai entre requêtes (ms)
    private const CACHE_TTL = 86400;  // 24h
    private const TIMEOUT   = 20;

    // ─────────────────────────────────────────────────────
    // API PUBLIQUE
    // ─────────────────────────────────────────────────────

    /**
     * Scrape un seul SIREN
     */
    public function scrapeBySiren(string $siren): array
    {
        $siren = preg_replace('/\D/', '', $siren);

        if (strlen($siren) !== 9) {
            return $this->empty($siren, 'SIREN invalide (doit faire 9 chiffres)');
        }

        // Cache hit ?
        $cacheKey = "pappers_scrape_{$siren}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders($this->headers())
                ->get(self::BASE_URL . $siren);

            if ($response->failed()) {
                return $this->empty($siren, 'Erreur HTTP ' . $response->status());
            }

            $data = $this->parse($response->body(), $siren);

            // Ne cache que si on a trouvé quelque chose
            if (!empty($data['capital_social']) || !empty($data['nom'])) {
                Cache::put($cacheKey, $data, self::CACHE_TTL);
            }

            return $data;

        } catch (\Exception $e) {
            Log::warning("PappersScraper SIREN={$siren} : " . $e->getMessage());
            return $this->empty($siren, $e->getMessage());
        }
    }

    /**
     * Scrape une liste de SIRENs
     * Retourne un tableau indexé par SIREN : [ '411484926' => [...], ... ]
     *
     * @param  string[] $sirens
     * @return array<string, array>
     */
    public function scrapeMultiple(array $sirens): array
    {
        $results = [];
        $unique  = array_unique(array_filter(array_map(
            fn($s) => preg_replace('/\D/', '', (string) $s),
            $sirens
        )));

        foreach ($unique as $siren) {
            if (strlen($siren) !== 9) {
                continue;
            }

            $results[$siren] = $this->scrapeBySiren($siren);

            // Anti-rate-limit : pause entre chaque requête
            usleep(self::DELAY_MS * 1000);
        }

        return $results;
    }

    /**
     * Enrichir un tableau de propriétaires avec les données scrapées
     *
     * @param  array[] $proprietaires  [ ['siren' => '...', 'nom' => '...'], ... ]
     * @return array[]
     */
    public function enrichProprietaires(array $proprietaires): array
    {
        // 1. Collecter tous les SIRENs valides
        $sirens = collect($proprietaires)
            ->pluck('siren')
            ->filter(fn($s) => $s && strlen(preg_replace('/\D/', '', $s)) === 9)
            ->unique()
            ->values()
            ->all();

        // 2. Scraper en batch
        $scraped = $this->scrapeMultiple($sirens);

        // 3. Merger dans chaque propriétaire
        return collect($proprietaires)
            ->map(function (array $prop) use ($scraped) {
                $siren = preg_replace('/\D/', '', $prop['siren'] ?? '');
                $data  = $scraped[$siren] ?? null;

                if (!$data || !empty($data['error'])) {
                    return $prop;
                }

                return array_merge($prop, [
                    'nom'              => $data['nom']            ?? $prop['nom']             ?? null,
                    'capital_social'   => $data['capital_social'] ?? $prop['capital_social']  ?? null,
                    'forme_juridique'  => $data['forme_juridique']?? $prop['forme_juridique'] ?? null,
                    'activite'         => $data['activite']       ?? $prop['activite']        ?? null,
                    'date_creation'    => $data['date_creation']  ?? $prop['date_creation']   ?? null,
                    'dirigeant_principal' => $data['dirigeant']   ?? $prop['dirigeant_principal'] ?? null,
                    'siret'            => $data['siret_siege']    ?? $prop['siret']           ?? null,
                    'effectif'         => $data['effectif']       ?? $prop['effectif']        ?? null,
                    'chiffre_affaires' => $data['chiffre_affaires'] ?? $prop['chiffre_affaires'] ?? null,
                    'url_pappers'      => self::BASE_URL . $siren,
                    'source_enrichissement' => 'pappers_scrape',
                ]);
            })
            ->values()
            ->all();
    }

    // ─────────────────────────────────────────────────────
    // PARSING
    // ─────────────────────────────────────────────────────

    private function parse(string $html, string $siren): array
    {
        // Priorité 1 : JSON Next.js embarqué (__NEXT_DATA__)
        if (preg_match(
            '/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/s',
            $html,
            $m
        )) {
            $json = json_decode($m[1], true);
            if ($json) {
                $result = $this->fromNextJson($json, $siren);
                if (!empty($result['nom']) || !empty($result['capital_social'])) {
                    return $result;
                }
            }
        }

        // Priorité 2 : JSON-LD Schema.org
        if (preg_match_all(
            '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/s',
            $html,
            $ms
        )) {
            foreach ($ms[1] as $jsonRaw) {
                $ld = json_decode($jsonRaw, true);
                if ($ld) {
                    $result = $this->fromJsonLd($ld, $siren);
                    if (!empty($result['nom'])) {
                        return $result;
                    }
                }
            }
        }

        // Fallback : HTML brut par regex
        return $this->fromHtml($html, $siren);
    }

    /**
     * Extraire depuis __NEXT_DATA__ (structure Next.js)
     */
    private function fromNextJson(array $json, string $siren): array
    {
        $flat = $this->flatten($json);

        return [
            'siren'            => $siren,
            'nom'              => $this->pick($flat, [
                'denomination', 'denominationSociale', 'nom_entreprise',
                'nomEntreprise', 'name', 'company_name',
            ]),
            'capital_social'   => $this->formatCapital($this->pick($flat, [
                'capital', 'capital_social', 'capitalSocial',
                'montant_capital', 'montantCapital',
            ])),
            'forme_juridique'  => $this->pick($flat, [
                'forme_juridique', 'formeJuridique', 'legal_form', 'legalForm',
            ]),
            'activite'         => $this->pick($flat, [
                'libelle_code_naf', 'libelleCodeNaf', 'activite',
                'activity', 'domaine_activite',
            ]),
            'date_creation'    => $this->pick($flat, [
                'date_creation', 'dateCreation', 'date_immatriculation',
                'dateImmatriculation',
            ]),
            'dirigeant'        => $this->pick($flat, [
                'nom_dirigeant', 'nomDirigeant', 'representant',
                'dirigeant', 'gerant',
            ]),
            'siret_siege'      => $this->pick($flat, [
                'siret', 'siret_siege', 'siretSiege',
            ]),
            'effectif'         => $this->pick($flat, [
                'effectif', 'tranche_effectif', 'trancheEffectif', 'nb_employes',
            ]),
            'chiffre_affaires' => $this->formatMontant($this->pick($flat, [
                'chiffre_affaires', 'chiffreAffaires', 'ca', 'revenue',
            ])),
            'source'           => 'pappers_next_data',
        ];
    }

    /**
     * Extraire depuis JSON-LD Schema.org
     */
    private function fromJsonLd(array $ld, string $siren): array
    {
        return [
            'siren'          => $siren,
            'nom'            => $ld['name'] ?? $ld['legalName'] ?? null,
            'capital_social' => $this->formatCapital($ld['capitalStock'] ?? null),
            'forme_juridique'=> $ld['legalForm'] ?? null,
            'activite'       => $ld['description'] ?? null,
            'source'         => 'pappers_json_ld',
        ];
    }

    /**
     * Fallback : regex sur le HTML brut
     */
    private function fromHtml(string $html, string $siren): array
    {
        return [
            'siren'           => $siren,
            'nom'             => $this->regex($html, [
                '/<h1[^>]*>([^<]+)<\/h1>/i',
                '/property="og:title"\s+content="([^"|]+)/i',
                '/<title>([^|\-<]+)/i',
            ]),
            'capital_social'  => $this->formatCapital($this->regex($html, [
                '/[Cc]apital\s+social\s*:?\s*<[^>]*>([^<]+)/i',
                '/[Cc]apital\s*:?\s*<[^>]*>\s*([0-9][0-9\s\.,]*\s*(?:€|EUR)?)/i',
                '/"capital"\s*:\s*"?([0-9]+)"?/i',
                '/capitalSocial["\s:]+([0-9]+)/i',
                '/capital_social["\s:]+([0-9]+)/i',
                '/data-capital="([^"]+)"/i',
            ])),
            'forme_juridique' => $this->regex($html, [
                '/[Ff]orme\s+juridique\s*:?\s*<[^>]*>([^<]+)/i',
                '/"forme_juridique"\s*:\s*"([^"]+)"/i',
                '/formeJuridique["\s:]+([^",\}]+)/i',
            ]),
            'activite'        => $this->regex($html, [
                '/[Aa]ctivité\s*:?\s*<[^>]*>([^<]+)/i',
                '/[Aa]ctivité\s*principale\s*:?\s*<[^>]*>([^<]+)/i',
            ]),
            'source'          => 'pappers_html_fallback',
        ];
    }

    // ─────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────

    /**
     * Aplatir un tableau JSON en dot-notation + clés simples
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flatten($value, $fullKey));
            } elseif ($value !== null && $value !== '') {
                $result[$fullKey]        = $value;
                // Index aussi par clé simple (dernière partie)
                $result[(string)$key] ??= $value;
            }
        }

        return $result;
    }

    /**
     * Trouver la première valeur non-vide parmi plusieurs clés
     */
    private function pick(array $flat, array $keys): mixed
    {
        foreach ($keys as $key) {
            // Correspondance exacte
            if (isset($flat[$key]) && $flat[$key] !== '') {
                return $flat[$key];
            }
            // Correspondance partielle (le champ contient la clé)
            foreach ($flat as $flatKey => $value) {
                if (
                    $value !== ''
                    && is_string($flatKey)
                    && str_contains(strtolower($flatKey), strtolower($key))
                ) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Appliquer une liste de patterns regex sur une chaîne HTML
     */
    private function regex(string $html, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $value = trim(strip_tags(html_entity_decode($matches[1])));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Formater un montant de capital social
     */
    private function formatCapital(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $raw = (string) $raw;

        // Déjà formaté avec €
        if (str_contains($raw, '€') || stripos($raw, 'eur') !== false) {
            return trim($raw);
        }

        // Nombre brut
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits !== '' && strlen($digits) >= 1) {
            return number_format((int) $digits, 0, ',', ' ') . ' €';
        }

        return $raw;
    }

    /**
     * Formater un chiffre d'affaires ou montant générique
     */
    private function formatMontant(mixed $raw): ?string
    {
        return $this->formatCapital($raw);
    }

    /**
     * Retourner un résultat vide avec message d'erreur
     */
    private function empty(string $siren, string $error = ''): array
    {
        return [
            'siren'            => $siren,
            'nom'              => null,
            'capital_social'   => null,
            'forme_juridique'  => null,
            'activite'         => null,
            'date_creation'    => null,
            'dirigeant'        => null,
            'siret_siege'      => null,
            'effectif'         => null,
            'chiffre_affaires' => null,
            'url_pappers'      => self::BASE_URL . $siren,
            'source'           => 'pappers_scrape',
            'error'            => $error ?: null,
        ];
    }

    /**
     * Headers HTTP pour imiter un navigateur réel
     */
    private function headers(): array
    {
        return [
            'User-Agent'               => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'                   => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language'          => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding'          => 'gzip, deflate, br',
            'Connection'               => 'keep-alive',
            'Upgrade-Insecure-Requests'=> '1',
            'Sec-Fetch-Dest'           => 'document',
            'Sec-Fetch-Mode'           => 'navigate',
            'Sec-Fetch-Site'           => 'none',
            'Sec-Fetch-User'           => '?1',
            'Cache-Control'            => 'max-age=0',
            'Referer'                  => 'https://www.pappers.fr/',
        ];
    }
}