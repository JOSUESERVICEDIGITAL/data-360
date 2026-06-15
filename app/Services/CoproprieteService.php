<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CoproprieteService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.copropriete.base_url'), '/');
        $this->timeout  = (int) config('services.copropriete.timeout', 15);
        $this->cacheTtl = (int) config('services.copropriete.cache_ttl', 3600);

        Log::debug('[COPRO DEBUG] ✅ CoproprieteService instancié', [
            'baseUrl'  => $this->baseUrl,
            'timeout'  => $this->timeout,
            'cacheTtl' => $this->cacheTtl,
        ]);
    }

    // =========================================================================
    // MÉTHODES PUBLIQUES
    // =========================================================================

    public function rechercherParAdresse(string $adresse, int $page = 1, int $perPage = 10): array
    {
        $cacheKey = 'copro_search_' . md5($adresse . $page . $perPage);

        Log::debug('[COPRO DEBUG] 🔍 rechercherParAdresse() appelé', [
            'adresse'  => $adresse,
            'page'     => $page,
            'perPage'  => $perPage,
            'cacheKey' => $cacheKey,
        ]);

        // Vider le cache pendant le debug pour toujours faire la vraie requête
        Cache::forget($cacheKey);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($adresse, $page, $perPage) {

            $url = "{$this->baseUrl}/annuaire";
            $params = [
                'recherche' => $adresse,
                'page'      => $page,
                'nombre'    => $perPage,
            ];

            Log::debug('[COPRO DEBUG] 📡 Requête HTTP vers RNIC', [
                'url'    => $url,
                'params' => $params,
                'full_url' => $url . '?' . http_build_query($params),
            ]);

            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->headers())
                    ->get($url, $params);

                Log::debug('[COPRO DEBUG] 📬 Réponse HTTP reçue', [
                    'status'         => $response->status(),
                    'headers'        => $response->headers(),
                    'body_length'    => strlen($response->body()),
                    'body_extrait'   => substr($response->body(), 0, 1000),
                    'json_decoded'   => $response->json(),
                ]);

                if ($response->failed()) {
                    Log::warning('[COPRO DEBUG] ❌ Requête échouée (status >= 400)', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                    return $this->erreur('Réponse API invalide', $response->status());
                }

                $json = $response->json();

                Log::debug('[COPRO DEBUG] 🧪 JSON parsé', [
                    'type'  => gettype($json),
                    'value' => $json,
                ]);

                if (!is_array($json)) {
                    Log::warning('[COPRO DEBUG] ⚠️ JSON non-array ou null', [
                        'adresse' => $adresse,
                        'type'    => gettype($json),
                        'body'    => $response->body(),
                    ]);
                    return $this->erreur('Réponse API inattendue (non-JSON ou null)');
                }

                Log::debug('[COPRO DEBUG] 🔑 Clés JSON disponibles', [
                    'keys' => array_keys($json),
                ]);

                // Tenter toutes les clés possibles
                $items = null;
                if (isset($json['results']))      { $items = $json['results'];      Log::debug('[COPRO DEBUG] ✅ Clé trouvée: results'); }
                elseif (isset($json['coproprietes'])) { $items = $json['coproprietes']; Log::debug('[COPRO DEBUG] ✅ Clé trouvée: coproprietes'); }
                elseif (isset($json['data']))     { $items = $json['data'];         Log::debug('[COPRO DEBUG] ✅ Clé trouvée: data'); }
                elseif (isset($json[0]))          { $items = $json;                 Log::debug('[COPRO DEBUG] ✅ Tableau indexé direct'); }
                else {
                    Log::warning('[COPRO DEBUG] ⚠️ Aucune clé connue trouvée dans JSON', [
                        'keys' => array_keys($json),
                        'json' => $json,
                    ]);
                    $items = [];
                }

                Log::debug('[COPRO DEBUG] 📦 Items extraits', [
                    'count' => count((array) $items),
                    'items' => $items,
                ]);

                return $this->formaterResultatsRecherche($json, $adresse, $items);

            } catch (\Exception $e) {
                Log::error('[COPRO DEBUG] 💥 Exception capturée', [
                    'adresse' => $adresse,
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                return $this->erreur($e->getMessage());
            }
        });
    }

    public function obtenirDetail(int|string $id): array
    {
        $cacheKey = 'copro_detail_' . $id;

        Log::debug('[COPRO DEBUG] 🔍 obtenirDetail() appelé', ['id' => $id]);

        Cache::forget($cacheKey); // forcer pendant debug

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($id) {

            $url = "{$this->baseUrl}/annuaire/coproannuairedetail/{$id}";

            Log::debug('[COPRO DEBUG] 📡 Requête détail RNIC', ['url' => $url]);

            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders($this->headers())
                    ->get($url);

                Log::debug('[COPRO DEBUG] 📬 Réponse détail reçue', [
                    'status'       => $response->status(),
                    'body_extrait' => substr($response->body(), 0, 1000),
                    'json'         => $response->json(),
                ]);

                if ($response->failed()) {
                    Log::warning('[COPRO DEBUG] ❌ Détail échoué', [
                        'id'     => $id,
                        'status' => $response->status(),
                    ]);
                    return $this->erreur('Copropriété introuvable', $response->status());
                }

                $json = $response->json();

                if (!is_array($json)) {
                    Log::warning('[COPRO DEBUG] ⚠️ Détail JSON null ou non-array', [
                        'id'   => $id,
                        'body' => $response->body(),
                    ]);
                    return $this->erreur('Réponse détail inattendue');
                }

                Log::debug('[COPRO DEBUG] ✅ Détail JSON valide', [
                    'keys' => array_keys($json),
                    'json' => $json,
                ]);

                return $this->formaterDetail($json, $id);

            } catch (\Exception $e) {
                Log::error('[COPRO DEBUG] 💥 Exception détail', [
                    'id'      => $id,
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                return $this->erreur($e->getMessage());
            }
        });
    }

    public function enrichirParAdresse(string $adresse): ?array
    {
        Log::debug('[COPRO DEBUG] 🔍 enrichirParAdresse() appelé', ['adresse' => $adresse]);

        $resultats = $this->rechercherParAdresse($adresse, 1, 1);

        Log::debug('[COPRO DEBUG] 📦 Résultats enrichirParAdresse', ['resultats' => $resultats]);

        if (!$resultats['success'] || empty($resultats['data'])) {
            Log::warning('[COPRO DEBUG] ⚠️ Aucun résultat pour enrichissement', ['adresse' => $adresse]);
            return null;
        }

        $premier = $resultats['data'][0];

        Log::debug('[COPRO DEBUG] 🥇 Premier résultat', ['premier' => $premier]);

        if (!empty($premier['id'])) {
            Log::debug('[COPRO DEBUG] 🔗 Récupération du détail', ['id' => $premier['id']]);
            $detail = $this->obtenirDetail($premier['id']);
            return $detail['success'] ? $detail['data'] : $premier;
        }

        return $premier;
    }

    public function viderCache(string $adresse = null, int|string $id = null): void
    {
        if ($adresse) Cache::forget('copro_search_' . md5($adresse . '1' . '10'));
        if ($id)      Cache::forget('copro_detail_' . $id);
    }

    // =========================================================================
    // FORMATAGE
    // =========================================================================

    protected function formaterResultatsRecherche(array $raw, string $adresse, array $items = []): array
    {
        Log::debug('[COPRO DEBUG] 🏗️ formaterResultatsRecherche()', [
            'items_count' => count($items),
            'items'       => $items,
        ]);

        $formatted = collect($items)->map(function ($item) {
            Log::debug('[COPRO DEBUG] 🔧 normaliserItem()', ['item' => $item]);
            return $this->normaliserItem($item);
        })->values()->all();

        Log::debug('[COPRO DEBUG] ✅ Items formatés', ['formatted' => $formatted]);

        return [
            'success'       => true,
            'source'        => 'RNIC (Registre National des Copropriétés - ANAH)',
            'source_url'    => 'https://www.registre-coproprietes.gouv.fr',
            'adresse_query' => $adresse,
            'total'         => $raw['total'] ?? $raw['count'] ?? count($formatted),
            'data'          => $formatted,
            'fetched_at'    => now()->toIso8601String(),
        ];
    }

    protected function formaterDetail(array $raw, int|string $id): array
    {
        Log::debug('[COPRO DEBUG] 🏗️ formaterDetail()', ['id' => $id, 'raw' => $raw]);

        return [
            'success'    => true,
            'source'     => 'RNIC (Registre National des Copropriétés - ANAH)',
            'source_url' => "https://www.registre-coproprietes.gouv.fr/annuaire/detail/{$id}",
            'data'       => $this->normaliserDetail($raw),
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    protected function normaliserItem(array $item): array
    {
        $representant = $this->extraireRepresentantLegal($item);

        Log::debug('[COPRO DEBUG] 👤 Représentant extrait', [
            'representant' => $representant,
            'item_keys'    => array_keys($item),
            'nomSyndic'    => $item['nomSyndic'] ?? 'N/A',
            'representantLegal' => $item['representantLegal'] ?? 'N/A',
            'typeSyndic'   => $item['typeSyndic'] ?? 'N/A',
            'siretSyndic'  => $item['siretSyndic'] ?? 'N/A',
        ]);

        return [
            'id'                   => $item['id'] ?? $item['identifiant'] ?? null,
            'nom'                  => $item['nom'] ?? $item['nomCopropriete'] ?? null,
            'adresse'              => $this->extraireAdresse($item),
            'code_postal'          => $item['codePostal'] ?? $item['cp'] ?? null,
            'ville'                => $item['commune'] ?? $item['ville'] ?? null,
            'representant_legal'   => $representant,
            'nb_lots_total'        => $item['nbLotsTotalParking'] ?? $item['nbLots'] ?? null,
            'nb_lots_habitation'   => $item['nbLotsHabitation'] ?? null,
            'date_immatriculation' => $item['dateImmatriculation'] ?? null,
            'procedures_en_cours'  => $this->extraireProcedueres($item),
            'lien_officiel'        => isset($item['id'])
                ? "https://www.registre-coproprietes.gouv.fr/annuaire/detail/{$item['id']}"
                : null,
            '_raw_item'            => $item, // ← dump complet pour debug
        ];
    }

    protected function normaliserDetail(array $raw): array
    {
        $base = $this->normaliserItem($raw);

        Log::debug('[COPRO DEBUG] 🏗️ normaliserDetail() raw complet', ['raw' => $raw]);

        return array_merge($base, [
            'siret_syndic'              => $raw['siretSyndic'] ?? $raw['siret'] ?? null,
            'siren_syndic'              => $raw['sirenSyndic'] ?? $raw['siren'] ?? null,
            'type_syndic'               => $raw['typeSyndic'] ?? null,
            'mandat_syndic'             => $raw['mandatSyndic'] ?? $raw['mandatRepresentantLegal'] ?? null,
            'nb_lots_stationnement'     => $raw['nbLotsStationnement'] ?? null,
            'nb_lots_bureaux'           => $raw['nbLotsBureaux'] ?? null,
            'periode_construction'      => $raw['periodeConstruction'] ?? null,
            'etiquette_energie'         => $raw['etiquetteEnergie'] ?? null,
            'montant_charges'           => $raw['montantCharges'] ?? null,
            'arrete_peril'              => $raw['nbArretesPeril'] ?? 0,
            'arrete_insalubrite'        => $raw['nbArretesInsalubrite'] ?? 0,
            'plan_sauvegarde'           => $raw['enPlanSauvegarde'] ?? false,
            'administration_provisoire' => $raw['enAdministrationProvisoire'] ?? false,
        ]);
    }

    protected function extraireAdresse(array $item): ?string
    {
        if (!empty($item['adresse'])) {
            return is_array($item['adresse'])
                ? implode(', ', array_filter($item['adresse']))
                : $item['adresse'];
        }

        $parts = array_filter([
            $item['numeroVoie'] ?? null,
            $item['typeVoie']   ?? null,
            $item['nomVoie']    ?? null,
        ]);

        return $parts ? implode(' ', $parts) : null;
    }

    protected function extraireRepresentantLegal(array $item): array
    {
        Log::debug('[COPRO DEBUG] 🔎 extraireRepresentantLegal() — toutes les clés', [
            'all_keys' => array_keys($item),
            'full_item' => $item,
        ]);

        if (!empty($item['nomSyndic']) || !empty($item['representantLegal'])) {
            return [
                'present' => true,
                'nom'     => $item['nomSyndic'] ?? $item['representantLegal'] ?? null,
                'siret'   => $item['siretSyndic'] ?? null,
                'type'    => $item['typeSyndic'] ?? 'professionnel',
                'mandat'  => $item['mandatSyndic'] ?? null,
            ];
        }

        if (!empty($item['prenomBeneficiaire']) || !empty($item['nomBeneficiaire'])) {
            return [
                'present' => true,
                'nom'     => trim(($item['prenomBeneficiaire'] ?? '') . ' ' . ($item['nomBeneficiaire'] ?? '')),
                'siret'   => null,
                'type'    => 'bénévole',
                'mandat'  => $item['mandatSyndic'] ?? null,
            ];
        }

        Log::warning('[COPRO DEBUG] ⚠️ Aucun représentant trouvé dans item', [
            'item' => $item,
        ]);

        return [
            'present' => false,
            'nom'     => null,
            'siret'   => null,
            'type'    => null,
            'mandat'  => null,
        ];
    }

    protected function extraireProcedueres(array $item): array
    {
        return array_filter([
            'peril'           => ($item['nbArretesPeril']        ?? 0) > 0,
            'insalubrite'     => ($item['nbArretesInsalubrite']  ?? 0) > 0,
            'plan_sauvegarde' =>  $item['enPlanSauvegarde']      ?? false,
            'adm_provisoire'  =>  $item['enAdministrationProvisoire'] ?? false,
        ]);
    }

    protected function erreur(string $message, int $status = 0): array
    {
        Log::error('[COPRO DEBUG] 🚨 Erreur retournée', [
            'message' => $message,
            'status'  => $status,
        ]);

        return [
            'success'    => false,
            'source'     => 'RNIC (Registre National des Copropriétés - ANAH)',
            'error'      => $message,
            'status'     => $status,
            'data'       => null,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    protected function headers(): array
    {
        return [
            'Accept'     => 'application/json',
            'User-Agent' => 'Data360/1.0 (contact: datainvest360@gmail.com)',
        ];
    }
}