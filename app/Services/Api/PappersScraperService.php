<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * PappersScraperService v5 — SCRAPING EN PREMIER, DOUBLE INTELLIGENCE
 * ─────────────────────────────────────────────────────────────────────
 * NOUVEAUTÉS v5 :
 *   🥇 Pappers HTML scraping EN PREMIER (plus besoin d'API)
 *   🧠 Double intelligence : __NEXT_DATA__ + regex HTML + meta + JSON-LD
 *   🔁 Retry automatique sur Pappers si Cloudflare (2ème tentative)
 *   🔍 Extraction SIRET via pattern SIREN+5chiffres (ultra-fiable)
 *   📦 deepSearch optimisé : cherche dans props.pageProps et toutes structures
 *   ⚡ INPI en fallback seulement si Pappers ne donne rien
 *   🔑 Pappers API en dernier recours si clé dispo
 * ─────────────────────────────────────────────────────────────────────
 */
class PappersScraperService
{
    // ── URLs ──
    private const PAPPERS_WEB  = 'https://www.pappers.fr/entreprise/';
    private const PAPPERS_API  = 'https://api.pappers.fr/v2/entreprise';
    private const INPI_API     = 'https://registre-national-entreprises.inpi.fr/api/companies/';

    // ── Config ──
    private const DELAY_MS     = 600;
    private const CACHE_TTL    = 86400;   // 24h pour résultats positifs
    private const CACHE_NEG_TTL = 3600;  // 1h pour résultats vides (évite de re-scraper trop vite)
    private const TIMEOUT      = 15;
    private const RETRY_DELAY  = 2;      // secondes entre tentatives

    // ─────────────────────────────────────────────────────────────
    // 🔑 ENTRÉE PRINCIPALE — Pappers en PREMIER
    // ─────────────────────────────────────────────────────────────

    public function scrapeBySiren(string $siren): array
    {
        $siren = preg_replace('/\D/', '', $siren);
        if (strlen($siren) !== 9) {
            return $this->empty($siren, 'siren_invalide');
        }

        $cacheKey = "psc_v5_{$siren}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = $this->empty($siren);

        // ══════════════════════════════════════════════════
        // 🥇 SOURCE 1 : PAPPERS HTML (PRIORITÉ ABSOLUE)
        // ══════════════════════════════════════════════════
        $pappers = $this->scrapePappersPage($siren);
        $result  = $this->merge($result, $pappers, 'pappers_html');

        Log::debug("Pappers HTML [{$siren}] → capital={$result['capital_social']} siret={$result['siret_siege']}");

        // ══════════════════════════════════════════════════
        // 🥈 SOURCE 2 : INPI RNE (si données manquantes)
        // ══════════════════════════════════════════════════
        if ($this->needsData($result)) {
            $inpi   = $this->callInpi($siren);
            $result = $this->merge($result, $inpi, 'inpi_rne');
            Log::debug("INPI [{$siren}] → capital={$result['capital_social']} siret={$result['siret_siege']}");
        }

        // ══════════════════════════════════════════════════
        // 🥉 SOURCE 3 : PAPPERS API (dernier recours si clé)
        // ══════════════════════════════════════════════════
        if ($this->needsData($result) && config('services.pappers.key')) {
            $api    = $this->callPappersApi($siren);
            $result = $this->merge($result, $api, 'pappers_api');
        }

        // ── URL Pappers toujours présente ──
        $result['url_pappers'] = self::PAPPERS_WEB . $siren;

        Log::info("PappersScraper v5 [{$siren}] FINAL → capital={$result['capital_social']} siret={$result['siret_siege']} src={$result['source']}");

        // ── Cacher ──
        if ($this->hasUsefulData($result)) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        } else {
            // Cache négatif court pour éviter de re-scraper en boucle
            Cache::put($cacheKey . '_neg', true, self::CACHE_NEG_TTL);
        }

        return $result;
    }

    /**
     * Batch — retourne [ 'siren' => [...], ... ]
     */
    public function scrapeMultiple(array $sirens): array
    {
        $results = [];

        $sirens = array_values(array_unique(array_filter(
            array_map(fn($s) => preg_replace('/\D/', '', (string) $s), $sirens),
            fn($s) => strlen($s) === 9
        )));

        foreach ($sirens as $siren) {
            $results[$siren] = $this->scrapeBySiren($siren);
            usleep(self::DELAY_MS * 1000);
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────
    // 🥇 PAPPERS HTML — SCRAPING DOUBLE INTELLIGENCE
    // ─────────────────────────────────────────────────────────────

    private function scrapePappersPage(string $siren): array
    {
        $html = $this->fetchPappersHtml($siren);

        if (!$html) return [];

        // ── Intelligence 1 : __NEXT_DATA__ JSON (Next.js) ──
        if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/s', $html, $m)) {
            $nd = json_decode($m[1], true);
            if ($nd) {
                $fromNext = $this->parseNextDataDeep($nd, $siren, $html);
                if ($this->hasUsefulData($fromNext)) {
                    Log::debug("Pappers [{$siren}] → données depuis __NEXT_DATA__");
                    return $fromNext;
                }
            }
        }

        // ── Intelligence 2 : JSON-LD (Schema.org) ──
        $fromLd = $this->parseJsonLd($html, $siren);
        if ($this->hasUsefulData($fromLd)) {
            Log::debug("Pappers [{$siren}] → données depuis JSON-LD");
            return $fromLd;
        }

        // ── Intelligence 3 : Regex HTML brutes ──
        Log::debug("Pappers [{$siren}] → fallback regex HTML");
        return $this->parseHtmlRegex($html, $siren);
    }

    /**
     * Fetch HTML Pappers avec retry sur blocage
     */
    private function fetchPappersHtml(string $siren): ?string
    {
        $url = self::PAPPERS_WEB . $siren;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $r = Http::timeout(self::TIMEOUT)
                    ->withHeaders($this->browserHeaders($attempt))
                    ->get($url);

                if ($r->successful()) {
                    $html = $r->body();
                    if ($this->isCloudflareBlock($html)) {
                        Log::debug("Pappers [{$siren}] tentative {$attempt} bloquée Cloudflare");
                        if ($attempt < 2) {
                            sleep(self::RETRY_DELAY);
                            continue;
                        }
                        return null;
                    }
                    Log::debug("Pappers [{$siren}] HTML reçu " . strlen($html) . " octets (tentative {$attempt})");
                    return $html;
                }

                // 404 = entreprise pas sur Pappers, inutile de retry
                if ($r->status() === 404) return null;

                Log::debug("Pappers [{$siren}] HTTP " . $r->status() . " tentative {$attempt}");
                if ($attempt < 2) sleep(self::RETRY_DELAY);

            } catch (\Exception $e) {
                Log::debug("Pappers [{$siren}] exception tentative {$attempt}: " . $e->getMessage());
                if ($attempt < 2) sleep(self::RETRY_DELAY);
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    // INTELLIGENCE 1 : __NEXT_DATA__ (Next.js)
    // Deep search avec chemins connus + fallback récursif
    // ─────────────────────────────────────────────────────────────

    private function parseNextDataDeep(array $nd, string $siren, string $html): array
    {
        // ── Chercher le bloc entreprise dans les chemins connus ──
        $entreprise = null;
        $paths = [
            'props.pageProps.entreprise',
            'props.pageProps.company',
            'props.pageProps.data.entreprise',
            'props.pageProps.data.company',
            'props.pageProps.initialData.entreprise',
            'props.pageProps.initialData',
            'props.pageProps',
        ];

        foreach ($paths as $path) {
            $candidate = data_get($nd, $path);
            if (is_array($candidate) && !empty($candidate)) {
                // Vérifier que c'est bien un bloc entreprise (contient siren ou denomination)
                if (
                    isset($candidate['siren'])
                    || isset($candidate['denomination'])
                    || isset($candidate['nom_entreprise'])
                    || isset($candidate['capital'])
                ) {
                    $entreprise = $candidate;
                    break;
                }
            }
        }

        // ── Fallback : deepSearch sur tout le JSON ──
        $all = $this->deepSearchJson($nd);

        // ── Extraire capital ──
        $capital = null;

        // Depuis le bloc entreprise si trouvé
        if ($entreprise) {
            $rawCapital = $entreprise['capital'] ?? $entreprise['capital_social'] ?? null;
            if (is_numeric($rawCapital)) {
                $capital = number_format((float) $rawCapital, 0, ',', ' ') . ' €';
            } elseif (is_string($rawCapital) && $rawCapital !== '') {
                $capital = $this->formatMontant($rawCapital);
            } elseif (is_array($rawCapital)) {
                $montant = $rawCapital['montant'] ?? $rawCapital['amount'] ?? null;
                $devise  = $rawCapital['devise']  ?? $rawCapital['currency'] ?? '€';
                if ($montant !== null) {
                    $capital = number_format((float) $montant, 0, ',', ' ') . ' ' . $devise;
                }
            }
        }

        // Depuis deepSearch si pas encore trouvé
        if (!$capital) {
            foreach (['capital', 'capital_social', 'capitalSocial'] as $k) {
                if (isset($all[$k])) {
                    if (is_numeric($all[$k])) {
                        $capital = number_format((float) $all[$k], 0, ',', ' ') . ' €';
                        break;
                    } elseif (is_string($all[$k]) && $all[$k] !== '') {
                        $capital = $this->formatMontant($all[$k]);
                        if ($capital) break;
                    }
                }
            }
        }

        // Si toujours pas de capital → tenter HTML regex
        if (!$capital) {
            $capital = $this->extractCapital($html);
        }

        // ── SIRET ──
        $siret = null;
        if ($entreprise) {
            $siret = $this->toSiret(
                $entreprise['siret_siege']
                ?? $entreprise['siretSiege']
                ?? data_get($entreprise, 'siege.siret')
                ?? null
            );
        }
        if (!$siret) {
            foreach (['siret', 'siretSiege', 'siret_siege'] as $k) {
                if (isset($all[$k]) && $this->toSiret($all[$k])) {
                    $siret = $this->toSiret($all[$k]);
                    break;
                }
            }
        }
        // Ultra-fiable : pattern SIREN + 5 chiffres dans tout le HTML
        if (!$siret) {
            $siret = $this->extractSiret($html, $siren);
        }

        // ── Nom ──
        $nom = $entreprise['denomination']
            ?? $entreprise['nom_entreprise']
            ?? $entreprise['denominationSociale']
            ?? $all['denomination']
            ?? $all['nom_entreprise']
            ?? null;

        // ── Forme juridique ──
        $forme = null;
        if ($entreprise) {
            $formeRaw = $entreprise['forme_juridique'] ?? $entreprise['formeJuridique'] ?? null;
            $forme = is_string($formeRaw) ? $formeRaw : data_get($formeRaw, 'libelle');
        }
        if (!$forme) $forme = $all['forme_juridique'] ?? $all['formeJuridique'] ?? null;

        // ── Activité ──
        $activite = $entreprise['domaine_activite']
                 ?? $entreprise['libelle_code_naf']
                 ?? $all['domaine_activite']
                 ?? $all['libelle_code_naf']
                 ?? null;

        // ── Dirigeant ──
        $dirigeant = null;
        $dirigeants = $entreprise['representants'] ?? $entreprise['dirigeants'] ?? [];
        if (!empty($dirigeants[0])) {
            $d = $dirigeants[0];
            $dirigeant = trim(($d['prenoms'] ?? $d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?: null;
        }
        if (!$dirigeant) $dirigeant = $all['nom_dirigeant'] ?? null;

        return array_filter([
            'nom'          => $nom,
            'siret'        => $siret,
            'capital'      => $capital,
            'forme'        => $forme,
            'activite'     => is_string($activite) ? $activite : null,
            'dateCreation' => $entreprise['date_creation'] ?? $all['date_creation'] ?? null,
            'dirigeant'    => $dirigeant,
            'effectif'     => $entreprise['effectif'] ?? $entreprise['tranche_effectif'] ?? $all['effectif'] ?? null,
            'ca'           => null,
        ], fn($v) => $v !== null && $v !== '');
    }

    // ─────────────────────────────────────────────────────────────
    // INTELLIGENCE 2 : JSON-LD Schema.org
    // ─────────────────────────────────────────────────────────────

    private function parseJsonLd(string $html, string $siren): array
    {
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/s', $html, $ms)) {
            return [];
        }

        foreach ($ms[1] as $raw) {
            $ld = json_decode($raw, true);
            if (!$ld) continue;

            $nom     = $ld['name'] ?? $ld['legalName'] ?? null;
            $capital = $this->formatMontant($ld['capitalStock'] ?? $ld['capital'] ?? null);
            $forme   = $ld['legalForm'] ?? null;
            $siret   = $this->toSiret($ld['taxID'] ?? $ld['identifier'] ?? null);

            if ($nom || $capital || $siret) {
                return array_filter([
                    'nom'     => $nom,
                    'siret'   => $siret,
                    'capital' => $capital,
                    'forme'   => $forme,
                ], fn($v) => $v !== null && $v !== '');
            }
        }

        return [];
    }

    // ─────────────────────────────────────────────────────────────
    // INTELLIGENCE 3 : Regex HTML brutes (30+ patterns)
    // ─────────────────────────────────────────────────────────────

    private function parseHtmlRegex(string $html, string $siren): array
    {
        return array_filter([
            'nom'          => $this->extractNom($html),
            'siret'        => $this->extractSiret($html, $siren),
            'capital'      => $this->extractCapital($html),
            'forme'        => $this->extractForme($html),
            'activite'     => $this->extractActivite($html),
            'dateCreation' => $this->extractDateCreation($html),
            'dirigeant'    => $this->extractDirigeant($html),
            'effectif'     => $this->extractEffectif($html),
        ], fn($v) => $v !== null && $v !== '');
    }

    // ─────────────────────────────────────────────────────────────
    // EXTRACTEURS INDIVIDUELS — patterns multiples pour chaque champ
    // ─────────────────────────────────────────────────────────────

    private function extractCapital(string $html): ?string
    {
        $raw = $this->rx($html, [
            // Texte naturel avec €
            '/[Cc]apital\s+social\s*[:\-]\s*(?:<[^>]*>)*\s*([0-9][0-9\s\.,]*\s*(?:€|EUR|euros?))/i',
            '/[Cc]apital\s*[:\-]\s*(?:<[^>]*>)*\s*([0-9][0-9\s\.,]*\s*(?:€|EUR|euros?))/i',
            // JSON inline — valeur numérique explicite
            '/"capital"\s*:\s*([0-9]+(?:\.[0-9]{1,2})?)\s*[,\}]/i',
            '/"capital_social"\s*:\s*([0-9]+(?:\.[0-9]{1,2})?)\s*[,\}]/i',
            '/capitalSocial["\s:]+([0-9]+(?:\.[0-9]{1,2})?)\s*[,\}]/i',
            '/montantCapital["\s:]+([0-9]+(?:\.[0-9]{1,2})?)\s*[,\}]/i',
            // JSON avec devise séparée : {"montant":1500,"devise":"EUR"}
            '/"montant"\s*:\s*([0-9]+(?:\.[0-9]{1,2})?)\s*,\s*"devise"/i',
            // Attributs data-*
            '/data-capital["\s=]+["\s]*([0-9][0-9\s\.,]*)/i',
            // Balise meta
            '/<meta[^>]*(?:capital|capital-social)[^>]*content="([0-9][^"]+)"/i',
        ]);

        return $this->formatMontant($raw);
    }

    private function extractSiret(string $html, string $siren): ?string
    {
        // 🎯 Ultra-fiable : SIREN connu + 5 chiffres = SIRET siège
        if (preg_match('/' . preg_quote($siren, '/') . '(\d{5})/i', $html, $m)) {
            return $siren . $m[1];
        }

        $raw = $this->rx($html, [
            '/SIRET\s+(?:du\s+)?(?:si[eè]ge\s*)?[:\-–]?\s*(?:<[^>]*>)*\s*([0-9][0-9\s]{12,16}[0-9])/i',
            '/"siret_siege"\s*:\s*"([0-9]{14})"/i',
            '/"siret"\s*:\s*"([0-9]{14})"/i',
            '/siretSiege["\s:=]+["\s]*([0-9]{14})/i',
            '/siret_siege["\s:=]+["\s]*([0-9]{14})/i',
            '/data-siret="([0-9]{14})"/i',
            '/id="siret[^"]*">([0-9][0-9 ]{12,16}[0-9])/i',
        ]);

        return $this->toSiret($raw);
    }

    private function extractNom(string $html): ?string
    {
        return $this->rx($html, [
            '/<h1[^>]*class="[^"]*(?:title|name|denomination)[^"]*"[^>]*>([^<]+)/i',
            '/<h1[^>]*>([^<|–\-]+)/i',
            '/property="og:title"\s+content="([^"|<–\-]+)/i',
            '/<title>([^|\-<]{3,80})/i',
            '/"denomination"\s*:\s*"([^"]{2,120})"/i',
            '/"nom_entreprise"\s*:\s*"([^"]{2,120})"/i',
        ]);
    }

    private function extractForme(string $html): ?string
    {
        return $this->rx($html, [
            '/[Ff]orme\s+juridique\s*[:\-]\s*(?:<[^>]*>)+([^<]{3,80})/i',
            '/"forme_juridique"\s*:\s*"([^"]{3,80})"/i',
            '/formeJuridique["\s:]+["\s]*([A-Z][^",\}\n]{2,60})/i',
            '/libelleFormeJuridique["\s:]+["\s]*([^",\}\n]{3,80})/i',
            '/[Ss]oci[eé]t[eé]\s+([A-Z][^<,\n]{3,50})(?:\s*<|\s*,)/i',
        ]);
    }

    private function extractActivite(string $html): ?string
    {
        return $this->rx($html, [
            '/[Aa]ctivit[eé]\s+principale\s*[:\-]\s*(?:<[^>]*>)+([^<]{5,120})/i',
            '/[Aa]ctivit[eé]\s*[:\-]\s*(?:<[^>]*>)+([^<]{5,120})/i',
            '/"domaine_activite"\s*:\s*"([^"]{5,120})"/i',
            '/"libelle_code_naf"\s*:\s*"([^"]{5,120})"/i',
            '/code_naf["\s:=]+["\s]*[0-9]{4}[A-Z]?\s*[-–]\s*([^"<,\n]{5,100})/i',
        ]);
    }

    private function extractDateCreation(string $html): ?string
    {
        return $this->rx($html, [
            '/"date_creation"\s*:\s*"([0-9]{4}-[0-9]{2}-[0-9]{2})"/i',
            '/dateCreation["\s:]+["\s]*([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
            '/dateImmatriculation["\s:]+["\s]*([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
            '/[Cc]r[eé][eé]e?\s+le\s*[:\-]?\s*(?:<[^>]*>)*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i',
            '/[Ii]mmatricul[eé]e?\s+le\s*[:\-]?\s*(?:<[^>]*>)*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i',
        ]);
    }

    private function extractDirigeant(string $html): ?string
    {
        return $this->rx($html, [
            '/"nom_dirigeant"\s*:\s*"([^"]{2,80})"/i',
            '/[Pp]r[eé]sident\s*[:\-]\s*(?:<[^>]*>)+([^<]{3,80})/i',
            '/[Gg][eé]rant\s*[:\-]\s*(?:<[^>]*>)+([^<]{3,80})/i',
            '/[Dd]irigeant\s*[:\-]\s*(?:<[^>]*>)+([^<]{3,80})/i',
            '/[Rr]epresentant\s+l[eé]gal\s*[:\-]\s*(?:<[^>]*>)+([^<]{3,80})/i',
            '/"prenom"\s*:\s*"([^"]+)"\s*,\s*"nom"\s*:\s*"([^"]+)"/i',
        ]);
    }

    private function extractEffectif(string $html): ?string
    {
        return $this->rx($html, [
            '/"effectif"\s*:\s*"([^"]{1,40})"/i',
            '/"tranche_effectif"\s*:\s*"([^"]{1,40})"/i',
            '/[Ee]ffectif\s*[:\-]\s*(?:<[^>]*>)+([^<]{1,40})/i',
            '/[Ss]alari[eé]s\s*[:\-]\s*(?:<[^>]*>)+([^<]{1,40})/i',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 🥈 INPI RNE — Fallback avec chemins explicites
    // ─────────────────────────────────────────────────────────────

    private function callInpi(string $siren): array
    {
        try {
            $r = Http::timeout(self::TIMEOUT)
                ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'DataRocket/5.0'])
                ->get(self::INPI_API . $siren);

            if ($r->failed()) return [];

            $json = $r->json();
            if (!$json) return [];

            Log::debug("INPI [{$siren}] clés racine: " . implode(',', array_keys($json)));

            // Trouver le bloc de données
            $content = data_get($json, 'formality.content')
                    ?? data_get($json, 'company.formality.content')
                    ?? data_get($json, 'identite')
                    ?? $json;

            $entreprise = $content['personneMorale']
                       ?? $content['personnePhysique']
                       ?? $content;

            // Capital
            $capital = null;
            foreach ([
                fn() => data_get($entreprise, 'capital.montant'),
                fn() => data_get($content, 'capital.montant'),
                fn() => data_get($json, 'capital.montant'),
                fn() => data_get($json, 'capitalSocial.montant'),
            ] as $getter) {
                $montant = $getter();
                if ($montant !== null && $montant !== '') {
                    $devise = data_get($entreprise, 'capital.devise') ?? 'EUR';
                    $capital = number_format((float) $montant, 0, ',', ' ') . ' ' . $devise;
                    break;
                }
            }

            // SIRET
            $siret = $this->toSiret(
                data_get($content, 'etablissementPrincipal.siret')
                ?? data_get($content, 'siretSiege')
                ?? data_get($json, 'siretSiege')
                ?? data_get($json, 'siege.siret')
            );

            // Forme juridique
            $forme = data_get($entreprise, 'formeJuridique.libelleFormeJuridique')
                  ?? data_get($entreprise, 'formeJuridique.libelle')
                  ?? null;
            if (is_array($forme)) $forme = null;

            // Dénomination
            $nom = data_get($entreprise, 'denomination')
                ?? data_get($entreprise, 'denominationSociale')
                ?? data_get($content, 'denomination')
                ?? null;

            // Activité
            $activite = data_get($content, 'activitePrincipale.libelleActivitePrincipale')
                     ?? data_get($json, 'libelleCodeNaf')
                     ?? null;
            if (is_array($activite)) $activite = null;

            // Dirigeant
            $dirigeant = null;
            $dirs = data_get($content, 'dirigeants', []);
            if (!empty($dirs[0])) {
                $d0 = $dirs[0];
                $dirigeant = trim((data_get($d0, 'prenoms') ?? '') . ' ' . (data_get($d0, 'nom') ?? '')) ?: null;
            }

            return array_filter([
                'nom'          => $nom,
                'siret'        => $siret,
                'capital'      => $capital,
                'forme'        => $forme,
                'activite'     => $activite,
                'dateCreation' => data_get($json, 'dateImmatriculation') ?? data_get($json, 'dateCreation'),
                'dirigeant'    => $dirigeant,
                'effectif'     => data_get($json, 'effectif') ?? data_get($json, 'trancheEffectif'),
            ], fn($v) => $v !== null && $v !== '');

        } catch (\Exception $e) {
            Log::debug("INPI [{$siren}] exception: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 🥉 PAPPERS API — Dernier recours
    // ─────────────────────────────────────────────────────────────

    private function callPappersApi(string $siren): array
    {
        try {
            $r = Http::timeout(self::TIMEOUT)
                ->get(self::PAPPERS_API, [
                    'siren'     => $siren,
                    'api_token' => config('services.pappers.key'),
                ]);

            if ($r->failed()) return [];

            $json = $r->json();
            if (!$json) return [];

            $capital = null;
            if (!empty($json['capital'])) {
                $capital = number_format((float) $json['capital'], 0, ',', ' ') . ' €';
            }

            $dirigeant = null;
            $dirs = $json['representants'] ?? $json['dirigeants'] ?? [];
            if (!empty($dirs[0])) {
                $d = $dirs[0];
                $dirigeant = trim(($d['prenoms'] ?? '') . ' ' . ($d['nom'] ?? '')) ?: null;
            }

            $ca = null;
            if (!empty($json['finances'])) {
                $fin = end($json['finances']);
                if ($fin['chiffre_affaires'] ?? null) {
                    $ca = number_format((float) $fin['chiffre_affaires'], 0, ',', ' ') . ' €';
                }
            }

            return array_filter([
                'nom'          => $json['nom_entreprise'] ?? $json['denomination'] ?? null,
                'siret'        => $this->toSiret(data_get($json, 'siege.siret')),
                'capital'      => $capital,
                'forme'        => $json['forme_juridique'] ?? null,
                'activite'     => $json['domaine_activite'] ?? $json['libelle_code_naf'] ?? null,
                'dateCreation' => $json['date_creation'] ?? null,
                'dirigeant'    => $dirigeant,
                'effectif'     => $json['effectif'] ?? $json['tranche_effectif'] ?? null,
                'ca'           => $ca,
            ], fn($v) => $v !== null && $v !== '');

        } catch (\Exception $e) {
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // MERGE — remplit les trous sans écraser, avec mapping explicite
    // ─────────────────────────────────────────────────────────────

    private function merge(array $result, array $extra, string $sourceName): array
    {
        if (empty($extra)) return $result;

        $mapping = [
            'nom'          => 'nom',
            'siret'        => 'siret_siege',
            'capital'      => 'capital_social',
            'forme'        => 'forme_juridique',
            'activite'     => 'activite',
            'dateCreation' => 'date_creation',
            'dirigeant'    => 'dirigeant',
            'effectif'     => 'effectif',
            'ca'           => 'chiffre_affaires',
        ];

        $capitalSet = false;

        foreach ($mapping as $fromKey => $toKey) {
            $val = $extra[$fromKey] ?? null;
            if ($val === null || $val === '' || $val === '-') continue;

            if (empty($result[$toKey])) {
                $result[$toKey] = is_string($val) ? trim($val) : $val;
                if ($toKey === 'capital_social') {
                    $capitalSet = true;
                }
            }
        }

        // Mettre à jour la source uniquement si c'est la première qui apporte le capital
        if ($capitalSet || ($result['source'] === 'scraper' && !empty($extra))) {
            $result['source'] = $sourceName;
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function needsData(array $r): bool
    {
        return empty($r['capital_social'])
            || empty($r['siret_siege'])
            || empty($r['forme_juridique']);
    }

    private function hasUsefulData(array $r): bool
    {
        return !empty($r['capital_social'])
            || !empty($r['siret_siege'])
            || !empty($r['nom']);
    }

    private function isCloudflareBlock(string $html): bool
    {
        return strlen($html) < 1000
            || str_contains($html, 'cf-browser-verification')
            || str_contains($html, 'Just a moment')
            || str_contains($html, 'Checking your browser')
            || str_contains($html, 'cloudflare')
            || str_contains($html, 'Ray ID:');
    }

    /**
     * Deep search récursif — uniquement les clés connues
     */
    private function deepSearchJson(array $data): array
    {
        static $wanted = [
            'capital', 'capital_social', 'capitalSocial', 'montantCapital',
            'siret', 'siretSiege', 'siret_siege',
            'denomination', 'denominationSociale', 'nom_entreprise',
            'forme_juridique', 'formeJuridique',
            'domaine_activite', 'libelle_code_naf', 'activite',
            'date_creation', 'dateCreation',
            'nom_dirigeant', 'nomDirigeant',
            'effectif', 'tranche_effectif',
        ];

        $result = [];
        array_walk_recursive($data, function ($value, $key) use (&$result, $wanted) {
            if (
                in_array($key, $wanted, true)
                && !isset($result[$key])
                && $value !== null
                && $value !== ''
            ) {
                $result[$key] = $value;
            }
        });

        return $result;
    }

    private function formatMontant(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') return null;
        $raw = trim((string) $raw);

        // Déjà avec symbole
        if (str_contains($raw, '€') || stripos($raw, 'eur') !== false) {
            return preg_replace('/\s+/', ' ', $raw);
        }

        // Retirer tout sauf chiffres (enlever ,00 ou .00 finals)
        $clean  = preg_replace('/[,.]00$/', '', $raw);
        $digits = preg_replace('/[^0-9]/', '', $clean);

        if ($digits !== '' && strlen($digits) >= 1 && (int) $digits > 0) {
            return number_format((int) $digits, 0, ',', ' ') . ' €';
        }

        return null;
    }

    private function toSiret(mixed $raw): ?string
    {
        if (!$raw) return null;
        $clean = preg_replace('/\D/', '', (string) $raw);
        return strlen($clean) === 14 ? $clean : null;
    }

    private function rx(string $html, array $patterns): ?string
    {
        foreach ($patterns as $p) {
            if (preg_match($p, $html, $m)) {
                $v = trim(strip_tags(html_entity_decode(
                    isset($m[2]) ? $m[1] . ' ' . $m[2] : $m[1]
                )));
                if ($v !== '') return $v;
            }
        }
        return null;
    }

    /**
     * Headers navigateur — 2 profils différents pour le retry
     */
    private function browserHeaders(int $attempt = 1): array
    {
        if ($attempt === 2) {
            return [
                'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.8,en;q=0.6',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer'         => 'https://www.google.fr/search?q=pappers+entreprise',
                'Cache-Control'   => 'no-cache',
            ];
        }

        return [
            'User-Agent'               => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept'                   => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language'          => 'fr-FR,fr;q=0.9',
            'Accept-Encoding'          => 'gzip, deflate, br',
            'Referer'                  => 'https://www.pappers.fr/',
            'Sec-Fetch-Dest'           => 'document',
            'Sec-Fetch-Mode'           => 'navigate',
            'Sec-Fetch-Site'           => 'same-origin',
            'Sec-Fetch-User'           => '?1',
            'Upgrade-Insecure-Requests'=> '1',
            'Cache-Control'            => 'max-age=0',
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