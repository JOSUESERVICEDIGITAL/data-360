<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * PappersScraperService v4 — VERSION FINALE CORRIGÉE
 * ─────────────────────────────────────────────────────────────────
 * CAUSES DU BUG v3 :
 *   ❌ flatten() + pick() partiel = matchait n'importe quel champ
 *      contenant 'montant' (ex: adresse, commentaires, etc.)
 *   ❌ Collisions de clés simples dans flatten() causaient des valeurs
 *      incorrectes selon l'ordre d'itération JSON
 *
 * CORRECTIONS v4 :
 *   ✅ INPI : chemins EXPLICITES (data_get) pas de recherche partielle
 *   ✅ Pappers HTML : regex directes robustes
 *   ✅ Logique simple : essaie chaque source, prend ce qu'elle donne
 *   ✅ Merge par champ (fill) conservé et simplifié
 *   ✅ Debug log à chaque étape
 * ─────────────────────────────────────────────────────────────────
 */
class PappersScraperService
{
    private const PAPPERS_WEB = 'https://www.pappers.fr/entreprise/';
    private const PAPPERS_API = 'https://api.pappers.fr/v2/entreprise';
    private const INPI_API    = 'https://registre-national-entreprises.inpi.fr/api/companies/';

    private const DELAY_MS    = 700;
    private const CACHE_TTL   = 86400;
    private const TIMEOUT     = 12;

    // ─────────────────────────────────────────────────────────────
    // API PUBLIQUE
    // ─────────────────────────────────────────────────────────────

    public function scrapeBySiren(string $siren): array
    {
        $siren = preg_replace('/\D/', '', $siren);
        if (strlen($siren) !== 9) {
            return $this->emptyResult($siren, 'siren_invalide');
        }

        $cacheKey = "psc_v4_{$siren}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = $this->emptyResult($siren);

        // ── 1. INPI RNE (gratuit, officiel) ──
        $inpi = $this->callInpi($siren);
        $result = $this->merge($result, $inpi, 'inpi_rne');

        // ── 2. Pappers API officielle (si clé dispo) ──
        if ($this->missingCapital($result) && config('services.pappers.key')) {
            $papi = $this->callPappersApi($siren);
            $result = $this->merge($result, $papi, 'pappers_api');
        }

        // ── 3. Pappers HTML (toujours tenté si SIRET ou capital manquants) ──
        if ($this->missingCapital($result) || $this->missingSiret($result)) {
            $html = $this->callPappersHtml($siren);
            $result = $this->merge($result, $html, 'pappers_html');
        }

        $result['url_pappers'] = self::PAPPERS_WEB . $siren;

        Log::debug("PappersScraper [{$siren}] → capital={$result['capital_social']} siret={$result['siret_siege']} src={$result['source']}");

        // Cacher uniquement si on a trouvé au moins quelque chose
        if ($result['capital_social'] || $result['siret_siege'] || $result['nom']) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    public function scrapeMultiple(array $sirens): array
    {
        $results = [];

        $sirens = array_unique(array_filter(
            array_map(fn($s) => preg_replace('/\D/', '', (string)$s), $sirens),
            fn($s) => strlen($s) === 9
        ));

        foreach ($sirens as $siren) {
            $results[$siren] = $this->scrapeBySiren($siren);
            usleep(self::DELAY_MS * 1000);
        }

        return $results;
    }

    // ─────────────────────────────────────────────────────────────
    // SOURCE 1 — INPI RNE
    // Chemins explicites selon la vraie structure de l'API INPI
    // ─────────────────────────────────────────────────────────────

    private function callInpi(string $siren): array
    {
        try {
            $r = Http::timeout(self::TIMEOUT)
                ->withHeaders(['Accept' => 'application/json', 'User-Agent' => 'DataRocket/4.0'])
                ->get(self::INPI_API . $siren);

            if ($r->failed()) {
                Log::debug("INPI [{$siren}] HTTP " . $r->status());
                return [];
            }

            $json = $r->json();
            if (!$json) return [];

            Log::debug("INPI [{$siren}] réponse reçue, clés racine: " . implode(',', array_keys($json)));

            // ── Essayer plusieurs structures connues de l'API INPI ──

            // Structure A : {formality: {content: {personneMorale: {...}}}}
            $contentA = data_get($json, 'formality.content');

            // Structure B : {company: {formality: {content: {...}}}}
            $contentB = data_get($json, 'company.formality.content');

            // Structure C : données directement à la racine
            $contentC = $json;

            // Structure D : {identite: {...}}
            $contentD = data_get($json, 'identite');

            // Trouver le bon bloc de données
            $data = null;
            foreach ([$contentA, $contentB, $contentD, $contentC] as $candidate) {
                if (is_array($candidate) && !empty($candidate)) {
                    $data = $candidate;
                    break;
                }
            }

            if (!$data) return [];

            // ── Bloc entreprise (personne morale ou physique) ──
            $entreprise = $data['personneMorale']
                       ?? $data['personnePhysique']
                       ?? $data;

            // ── Capital social ──
            // Chemins explicites uniquement — pas de recherche partielle
            $capital = null;
            $montant = data_get($entreprise, 'capital.montant')
                    ?? data_get($data, 'capital.montant')
                    ?? data_get($json, 'capital.montant')
                    ?? data_get($json, 'capitalSocial.montant')
                    ?? data_get($entreprise, 'capitalSocial')
                    ?? data_get($data, 'capitalSocial');

            $devise  = data_get($entreprise, 'capital.devise')
                    ?? data_get($data, 'capital.devise')
                    ?? 'EUR';

            if ($montant !== null && $montant !== '') {
                $capital = number_format((float) $montant, 0, ',', ' ') . ' ' . $devise;
            }

            // ── SIRET siège ──
            $siret = $this->toSiret(
                data_get($data, 'etablissementPrincipal.siret')
                ?? data_get($data, 'siretSiege')
                ?? data_get($json, 'siretSiege')
                ?? data_get($json, 'siege.siret')
            );

            // ── Forme juridique ──
            $forme = data_get($entreprise, 'formeJuridique.libelleFormeJuridique')
                  ?? data_get($entreprise, 'formeJuridique.libelle')
                  ?? data_get($entreprise, 'formeJuridique')
                  ?? data_get($data, 'formeJuridique.libelleFormeJuridique')
                  ?? null;
            if (is_array($forme)) $forme = null;

            // ── Dénomination ──
            $nom = data_get($entreprise, 'denomination')
                ?? data_get($entreprise, 'denominationSociale')
                ?? data_get($data, 'denomination')
                ?? data_get($json, 'denomination')
                ?? null;

            // ── Activité ──
            $activite = data_get($data, 'activitePrincipale.libelleActivitePrincipale')
                     ?? data_get($data, 'activitePrincipale')
                     ?? data_get($json, 'libelleCodeNaf')
                     ?? null;
            if (is_array($activite)) $activite = null;

            // ── Date de création ──
            $dateCreation = data_get($json, 'dateImmatriculation')
                         ?? data_get($json, 'dateCreation')
                         ?? data_get($data, 'dateCreation')
                         ?? null;

            // ── Dirigeant ──
            $dirigeants  = data_get($data, 'dirigeants', []);
            $dirigeant   = null;
            if (!empty($dirigeants) && is_array($dirigeants)) {
                $d0        = $dirigeants[0];
                $prenom    = data_get($d0, 'prenoms') ?? data_get($d0, 'prenom') ?? '';
                $nomDir    = data_get($d0, 'nom') ?? '';
                $dirigeant = trim("$prenom $nomDir") ?: null;
            }

            // ── Effectif ──
            $effectif = data_get($json, 'effectif')
                     ?? data_get($json, 'trancheEffectif')
                     ?? null;

            Log::debug("INPI [{$siren}] capital={$capital} siret={$siret} nom={$nom}");

            return compact(
                'nom', 'siret', 'capital', 'forme',
                'activite', 'dateCreation', 'dirigeant', 'effectif'
            );

        } catch (\Exception $e) {
            Log::debug("INPI [{$siren}] exception: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SOURCE 2 — PAPPERS API OFFICIELLE
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

            $dirigeants = $json['representants'] ?? $json['dirigeants'] ?? [];
            $dirigeant  = null;
            if (!empty($dirigeants[0])) {
                $d = $dirigeants[0];
                $dirigeant = trim(($d['prenoms'] ?? '') . ' ' . ($d['nom'] ?? '')) ?: null;
            }

            $ca = null;
            if (!empty($json['finances'])) {
                $fin = end($json['finances']);
                if ($fin['chiffre_affaires'] ?? null) {
                    $ca = number_format((float) $fin['chiffre_affaires'], 0, ',', ' ') . ' €';
                }
            }

            return [
                'nom'          => $json['nom_entreprise'] ?? $json['denomination'] ?? null,
                'siret'        => $this->toSiret($json['siege']['siret'] ?? null),
                'capital'      => $capital,
                'forme'        => $json['forme_juridique'] ?? null,
                'activite'     => $json['domaine_activite'] ?? $json['libelle_code_naf'] ?? null,
                'dateCreation' => $json['date_creation'] ?? null,
                'dirigeant'    => $dirigeant,
                'effectif'     => $json['effectif'] ?? $json['tranche_effectif'] ?? null,
                'ca'           => $ca,
            ];

        } catch (\Exception $e) {
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SOURCE 3 — PAPPERS HTML
    // ─────────────────────────────────────────────────────────────

    private function callPappersHtml(string $siren): array
    {
        try {
            $r = Http::timeout(self::TIMEOUT)
                ->withHeaders($this->headers())
                ->get(self::PAPPERS_WEB . $siren);

            if ($r->failed()) {
                Log::debug("Pappers HTML [{$siren}] HTTP " . $r->status());
                return [];
            }

            $html = $r->body();

            if (strlen($html) < 800
                || str_contains($html, 'cf-browser-verification')
                || str_contains($html, 'Just a moment')
                || str_contains($html, 'Checking your browser')
            ) {
                Log::debug("Pappers HTML [{$siren}] bloqué Cloudflare");
                return [];
            }

            Log::debug("Pappers HTML [{$siren}] reçu " . strlen($html) . " octets");

            // ── Tenter __NEXT_DATA__ en priorité ──
            if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/s', $html, $m)) {
                $nd = json_decode($m[1], true);
                if ($nd) {
                    return $this->parseNextData($nd, $siren, $html);
                }
            }

            // ── Fallback : regex HTML brutes ──
            return $this->parseHtmlDirect($html, $siren);

        } catch (\Exception $e) {
            Log::debug("Pappers HTML [{$siren}] exception: " . $e->getMessage());
            return [];
        }
    }

    private function parseNextData(array $nd, string $siren, string $html): array
    {
        // Chercher récursivement capital, siret, etc. dans le JSON
        $all = $this->deepSearch($nd);

        // Capital — SEULEMENT depuis les clés qui ont du sens
        $capital = null;
        foreach (['capital', 'capital_social', 'capitalSocial', 'montantCapital'] as $k) {
            if (isset($all[$k]) && is_numeric($all[$k])) {
                $capital = number_format((float) $all[$k], 0, ',', ' ') . ' €';
                break;
            }
            if (isset($all[$k]) && is_string($all[$k]) && $all[$k] !== '') {
                $capital = $this->formatMontant($all[$k]);
                if ($capital) break;
            }
        }

        // SIRET — chercher explicitement
        $siret = null;
        foreach (['siret', 'siretSiege', 'siret_siege', 'siretEtablissementPrincipal'] as $k) {
            if (isset($all[$k]) && strlen(preg_replace('/\D/', '', $all[$k])) === 14) {
                $siret = preg_replace('/\D/', '', $all[$k]);
                break;
            }
        }

        // Si toujours pas de SIRET, chercher dans l'HTML
        if (!$siret) {
            $siret = $this->extractSiretFromHtml($html, $siren);
        }

        // Si toujours pas de capital, chercher dans l'HTML
        if (!$capital) {
            $capital = $this->extractCapitalFromHtml($html);
        }

        return [
            'nom'          => $all['denomination'] ?? $all['nom_entreprise'] ?? $all['denominationSociale'] ?? null,
            'siret'        => $siret,
            'capital'      => $capital,
            'forme'        => $all['forme_juridique'] ?? $all['formeJuridique'] ?? null,
            'activite'     => $all['domaine_activite'] ?? $all['libelle_code_naf'] ?? $all['activite'] ?? null,
            'dateCreation' => $all['date_creation'] ?? $all['dateCreation'] ?? null,
            'dirigeant'    => $all['nom_dirigeant'] ?? $all['nomDirigeant'] ?? null,
            'effectif'     => $all['effectif'] ?? $all['tranche_effectif'] ?? null,
        ];
    }

    private function parseHtmlDirect(string $html, string $siren): array
    {
        return [
            'nom'          => $this->rx($html, [
                                '/<h1[^>]*>([^<|]+)/i',
                                '/property="og:title"\s+content="([^"|<]+)/i',
                             ]),
            'siret'        => $this->extractSiretFromHtml($html, $siren),
            'capital'      => $this->extractCapitalFromHtml($html),
            'forme'        => $this->rx($html, [
                                '/[Ff]orme\s+juridique\s*:?\s*(?:<[^>]*>)+([^<]{3,60})/i',
                                '/"forme_juridique"\s*:\s*"([^"]{3,80})"/i',
                                '/formeJuridique["\s:]+["\s]*([^",\}]{3,80})/i',
                             ]),
            'activite'     => $this->rx($html, [
                                '/[Aa]ctivit[eé]\s*:?\s*(?:<[^>]*>)+([^<]{5,120})/i',
                                '/"domaine_activite"\s*:\s*"([^"]{5,120})"/i',
                             ]),
            'dateCreation' => $this->rx($html, [
                                '/"date_creation"\s*:\s*"([0-9]{4}-[0-9]{2}-[0-9]{2})"/i',
                                '/dateCreation["\s:]+["\s]*([0-9]{4}-[0-9]{2}-[0-9]{2})/i',
                             ]),
            'dirigeant'    => $this->rx($html, [
                                '/"nom_dirigeant"\s*:\s*"([^"]{2,80})"/i',
                                '/[Dd]irigeant\s*:?\s*(?:<[^>]*>)+([^<]{2,80})/i',
                             ]),
            'effectif'     => $this->rx($html, [
                                '/"effectif"\s*:\s*"([^"]{1,30})"/i',
                             ]),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // EXTRACTEURS SPÉCIALISÉS
    // ─────────────────────────────────────────────────────────────

    /**
     * Extraire le capital social depuis HTML — patterns robustes
     */
    private function extractCapitalFromHtml(string $html): ?string
    {
        // Format texte : "Capital social : 1 500 €"
        $raw = $this->rx($html, [
            '/[Cc]apital\s+social\s*[:\-]\s*(?:<[^>]*>)*\s*([0-9][0-9\s\.,]*\s*(?:€|EUR|euros?))/i',
            '/[Cc]apital\s*[:\-]\s*(?:<[^>]*>)*\s*([0-9][0-9\s\.,]*\s*(?:€|EUR|euros?))/i',
        ]);

        // Format JSON : "capital":1500 ou "capital":"1500"
        if (!$raw) {
            $raw = $this->rx($html, [
                '/"capital"\s*:\s*"?([0-9]+(?:[.,][0-9]{2})?)"?\s*[,\}]/i',
                '/capitalSocial["\s:]+([0-9]+(?:[.,][0-9]{2})?)/i',
                '/capital_social["\s:]+([0-9]+(?:[.,][0-9]{2})?)/i',
                '/montant["\s:]+([0-9]+(?:[.,][0-9]{2})?)\s*[,\}]/i',
            ]);
        }

        return $this->formatMontant($raw);
    }

    /**
     * Extraire le SIRET depuis HTML — patterns robustes
     */
    private function extractSiretFromHtml(string $html, string $siren): ?string
    {
        // Pattern générique : 14 chiffres dont les 9 premiers = SIREN
        $siretRegex = '/' . $siren . '[0-9]{5}/';
        if (preg_match($siretRegex, $html, $m)) {
            return $m[0];
        }

        // Patterns textuels
        $raw = $this->rx($html, [
            '/SIRET\s+(?:si[eè]ge\s*)?[:\-]?\s*(?:<[^>]*>)*\s*([0-9][0-9 ]{12,16}[0-9])/i',
            '/siret["\s:=]+["\s]*([0-9]{14})/i',
            '/siretSiege["\s:=]+["\s]*([0-9]{14})/i',
            '/"siret_siege"\s*:\s*"([0-9]{14})"/i',
            '/data-siret="([0-9]{14})"/i',
        ]);

        return $this->toSiret($raw);
    }

    /**
     * Recherche récursive dans un JSON — retourne un tableau clé→valeur plat
     * SEULEMENT pour les champs dont on connaît les noms
     */
    private function deepSearch(array $data): array
    {
        $wanted = [
            // Capital
            'capital', 'capital_social', 'capitalSocial', 'montantCapital',
            // SIRET
            'siret', 'siretSiege', 'siret_siege',
            // Identité
            'denomination', 'denominationSociale', 'nom_entreprise',
            'forme_juridique', 'formeJuridique',
            'domaine_activite', 'libelle_code_naf', 'activite',
            'date_creation', 'dateCreation',
            'nom_dirigeant', 'nomDirigeant',
            'effectif', 'tranche_effectif',
        ];

        $result = [];
        array_walk_recursive($data, function($value, $key) use (&$result, $wanted) {
            if (in_array($key, $wanted, true) && !isset($result[$key]) && $value !== null && $value !== '') {
                $result[$key] = $value;
            }
        });

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // MERGE — remplit les trous sans écraser
    // ─────────────────────────────────────────────────────────────

    private function merge(array $result, array $extra, string $sourceName): array
    {
        if (empty($extra)) return $result;

        // Mapping des clés de $extra vers les clés de $result
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

        $capitalUpdated = false;

        foreach ($mapping as $extraKey => $resultKey) {
            $val = $extra[$extraKey] ?? null;
            if ($val === null || $val === '' || $val === '-') continue;

            if (empty($result[$resultKey])) {
                $result[$resultKey] = is_string($val) ? trim($val) : $val;
                if ($resultKey === 'capital_social') {
                    $capitalUpdated = true;
                }
            }
        }

        if ($capitalUpdated) {
            $result['source'] = $sourceName;
        } elseif ($result['source'] === 'scraper' && !empty($extra)) {
            $result['source'] = $sourceName;
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function missingCapital(array $r): bool { return empty($r['capital_social']); }
    private function missingSiret(array $r): bool    { return empty($r['siret_siege']); }

    private function formatMontant(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') return null;
        $raw = trim((string) $raw);

        // Déjà formaté avec €
        if (str_contains($raw, '€') || stripos($raw, 'eur') !== false) {
            return preg_replace('/\s+/', ' ', $raw);
        }

        // Nombre brut (peut avoir virgule ou point décimal)
        $clean = preg_replace('/[^0-9,.]/', '', $raw);
        // Enlever la décimale si c'est ",00" ou ".00"
        $clean = preg_replace('/[,.]00$/', '', $clean);
        // Garder seulement les chiffres
        $digits = preg_replace('/[^0-9]/', '', $clean);

        if ($digits !== '' && strlen($digits) >= 1) {
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
                $v = trim(strip_tags(html_entity_decode($m[1])));
                if ($v !== '') return $v;
            }
        }
        return null;
    }

    private function headers(): array
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

    private function emptyResult(string $siren, string $error = ''): array
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