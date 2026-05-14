<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;

class PappersApiService
{
    public function __construct(
        protected ApiLoggerService $logger
    ) {}

    public function searchBySiren(?string $siren): ?array
    {
        if (!$siren) {
            return null;
        }

        $siren = preg_replace('/\D/', '', $siren);

        if (strlen($siren) !== 9) {
            return null;
        }

        $apiKey = trim((string) config('services.pappers.api_key'));

        if (!$apiKey) {
            return null;
        }

        $endpoint = rtrim(config('services.pappers.base_url'), '/') . '/entreprise';

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->retry(2, 300)
                ->get($endpoint, [
                    'api_token' => $apiKey,
                    'siren' => $siren,
                ]);

            $json = $response->json();

            $this->logger->log(
                'PAPPERS',
                $endpoint,
                $siren,
                $response->status(),
                $response->successful(),
                ['siren' => $siren],
                $json
            );

            if (!$response->successful() || !is_array($json)) {
                return null;
            }

            $siege = $json['siege'] ?? [];
            $dirigeant = $json['representants'][0] ?? null;

            $capital = $this->extractCapital($json);

            return [
                'siren' => $json['siren'] ?? $siren,
                'siret' => $siege['siret'] ?? null,

                'nom' => $json['nom_entreprise']
                    ?? $json['denomination']
                    ?? $json['denomination_sociale']
                    ?? null,

                'forme_juridique' => $json['forme_juridique']
                    ?? $json['forme_juridique_code']
                    ?? null,

                'capital_social' => $capital,

                'chiffre_affaires' => $this->formatMoney(
                    $json['chiffre_affaires']
                        ?? $json['chiffre_affaires_2024']
                        ?? $json['chiffre_affaires_2023']
                        ?? $json['chiffre_affaires_2022']
                        ?? null
                ),

                'resultat' => $this->formatMoney(
                    $json['resultat']
                        ?? $json['resultat_2024']
                        ?? $json['resultat_2023']
                        ?? $json['resultat_2022']
                        ?? null
                ),

                'effectif' => $json['effectif']
                    ?? $json['effectif_min']
                    ?? null,

                'date_creation' => $json['date_creation'] ?? null,

                'dirigeant_principal' => $this->formatDirigeant($dirigeant),

                'adresse_complete' => $siege['adresse_ligne_1']
                    ?? $siege['adresse_ligne_2']
                    ?? $siege['adresse']
                    ?? null,

                'code_postal' => $siege['code_postal'] ?? null,
                'ville' => $siege['ville'] ?? null,

                'url_pappers' => 'https://www.pappers.fr/entreprise/' . $siren,

                'raw_data' => $json,
            ];
        } catch (\Throwable $e) {
            $this->logger->log(
                'PAPPERS',
                $endpoint,
                $siren,
                null,
                false,
                ['siren' => $siren],
                null,
                $e->getMessage()
            );

            return null;
        }
    }

   private function extractCapital(array $json): ?string
{
    $capital = $json['capital']
        ?? $json['capital_social']
        ?? $json['capital_actuel_si_variable']
        ?? $json['capital_formate']
        ?? data_get($json, 'details.capital');

    if (!$capital) {
        $capital = collect($json['publications_bodacc'] ?? [])
            ->filter(fn ($publication) => !empty($publication['capital']))
            ->sortByDesc(fn ($publication) => $publication['date'] ?? '')
            ->pluck('capital')
            ->first();
    }

    if (!$capital) {
        $capital = collect($json['publications_bodacc'] ?? [])
            ->filter(fn ($publication) => !empty($publication['description']))
            ->map(fn ($publication) => $this->extractCapitalFromText($publication['description']))
            ->filter()
            ->first();
    }

    return $this->formatMoney($capital);
}

private function extractCapitalFromText(?string $text): ?string
{
    if (!$text) {
        return null;
    }

    if (preg_match('/capital(?: social)?[^0-9]*(\d[\d\s.,]*)\s*(?:€|euros|eur)?/i', $text, $matches)) {
        return $matches[1];
    }
    return null;
}


    private function formatMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $clean = str_replace([' ', '€', ','], ['', '', '.'], $value);

            if (is_numeric($clean)) {
                return number_format((float) $clean, 0, ',', ' ') . ' €';
            }

            return trim($value);
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 0, ',', ' ') . ' €';
        }

        return null;
    }

    private function formatDirigeant(?array $dirigeant): ?string
    {
        if (!$dirigeant) {
            return null;
        }

        return trim(
            ($dirigeant['prenom'] ?? '') . ' ' .
                ($dirigeant['nom'] ?? '') . ' ' .
                ($dirigeant['denomination'] ?? '')
        ) ?: null;
    }
}
