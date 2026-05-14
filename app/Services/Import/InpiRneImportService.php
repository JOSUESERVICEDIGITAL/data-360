<?php

namespace App\Services\Import;

use App\Models\Back\RneEntreprise;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class InpiRneImportService
{
    public function importFile(string $path): int
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Fichier introuvable : {$path}");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'zip') {
            return $this->importZip($path);
        }

        if (in_array($extension, ['json', 'ndjson'], true)) {
            return $this->importJsonLikeFile($path);
        }

        Log::warning('Format INPI non encore géré', [
            'path' => $path,
            'extension' => $extension,
        ]);

        return 0;
    }

    private function importZip(string $zipPath): int
    {
        $extractDir = storage_path('app/imports/inpi/extracted/' . pathinfo($zipPath, PATHINFO_FILENAME));

        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0777, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Impossible d’ouvrir le ZIP : {$zipPath}");
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $count = 0;

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractDir)
        );

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());

            if (in_array($ext, ['json', 'ndjson'], true)) {
                $count += $this->importJsonLikeFile($file->getPathname());
            }
        }

        return $count;
    }

    private function importJsonLikeFile(string $path): int
    {
        $handle = fopen($path, 'r');

        if (!$handle) {
            return 0;
        }

        $imported = 0;
        $buffer = '';

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $this->upsertEntreprise($decoded);
                $imported++;
                continue;
            }

            $buffer .= $line;
        }

        fclose($handle);

        if ($buffer) {
            $json = json_decode($buffer, true);

            if (isset($json[0]) && is_array($json[0])) {
                foreach ($json as $item) {
                    $this->upsertEntreprise($item);
                    $imported++;
                }
            } elseif (is_array($json)) {
                $this->upsertEntreprise($json);
                $imported++;
            }
        }

        return $imported;
    }

    private function upsertEntreprise(array $item): void
    {
        $siren = $this->extractSiren($item);

        if (!$siren || strlen($siren) !== 9) {
            return;
        }

        $capital = $this->extractCapital($item);

        RneEntreprise::updateOrCreate(
            ['siren' => $siren],
            [
                'siret_siege' => $this->extractSiretSiege($item),
                'denomination' => $this->extractDenomination($item),
                'forme_juridique' => $this->extractFormeJuridique($item),
                'capital_social' => $capital,
                'capital_social_numeric' => $this->capitalToDecimal($capital),
                'activite' => $this->extractActivite($item),
                'date_creation' => $this->extractDateCreation($item),
                'adresse_complete' => $this->extractAdresse($item),
                'code_postal' => $this->extractCodePostal($item),
                'ville' => $this->extractVille($item),
                'dirigeants' => $this->extractDirigeants($item),
                'etablissements' => $item['formality']['content']['personneMorale']['etablissementPrincipal'] ?? null,
                'raw_data' => $item,
            ]
        );
    }

    private function extractSiren(array $item): ?string
    {
        $value =
            $item['siren']
            ?? $item['formality']['siren']
            ?? $item['formality']['content']['siren']
            ?? $item['formality']['content']['personneMorale']['siren']
            ?? null;

        $value = preg_replace('/\D/', '', (string) $value);

        return $value ?: null;
    }

    private function extractSiretSiege(array $item): ?string
    {
        $value =
            $item['siret']
            ?? $item['siretSiege']
            ?? $item['formality']['content']['personneMorale']['etablissementPrincipal']['descriptionEtablissement']['siret']
            ?? null;

        $value = preg_replace('/\D/', '', (string) $value);

        return strlen($value) === 14 ? $value : null;
    }

    private function extractDenomination(array $item): ?string
    {
        return
            $item['denomination']
            ?? $item['denominationSociale']
            ?? $item['formality']['content']['personneMorale']['identite']['entreprise']['denomination']
            ?? $item['formality']['content']['personneMorale']['identite']['entreprise']['denominationSociale']
            ?? null;
    }

    private function extractFormeJuridique(array $item): ?string
    {
        return
            $item['formeJuridique']
            ?? $item['forme_juridique']
            ?? $item['formality']['content']['personneMorale']['identite']['entreprise']['formeJuridique']
            ?? null;
    }

    private function extractCapital(array $item): ?string
    {
        $capital =
            $item['capitalSocial']
            ?? $item['capital_social']
            ?? $item['formality']['content']['personneMorale']['identite']['entreprise']['capitalSocial']
            ?? $item['formality']['content']['personneMorale']['identite']['description']['montantCapital']
            ?? null;

        if (is_array($capital)) {
            $montant = $capital['montant'] ?? $capital['valeur'] ?? $capital['capital'] ?? null;
            $devise = $capital['devise'] ?? 'EUR';

            return $montant ? trim($montant . ' ' . $devise) : null;
        }

        return $capital ? trim((string) $capital) : null;
    }

    private function capitalToDecimal(?string $capital): ?float
    {
        if (!$capital) {
            return null;
        }

        $value = str_replace(['€', 'EUR', ' '], '', $capital);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function extractActivite(array $item): ?string
    {
        return
            $item['activite']
            ?? $item['codeApe']
            ?? $item['formality']['content']['personneMorale']['etablissementPrincipal']['descriptionEtablissement']['activites'][0]['codeApe']
            ?? null;
    }

    private function extractDateCreation(array $item): ?string
    {
        return
            $item['dateCreation']
            ?? $item['date_creation']
            ?? $item['formality']['content']['personneMorale']['identite']['entreprise']['dateCreation']
            ?? null;
    }

    private function extractAdresse(array $item): ?string
    {
        $adresse =
            $item['adresse']
            ?? $item['adresse_complete']
            ?? $item['formality']['content']['personneMorale']['etablissementPrincipal']['adresse']
            ?? null;

        if (is_string($adresse)) {
            return $adresse;
        }

        if (is_array($adresse)) {
            return trim(collect([
                $adresse['numVoie'] ?? null,
                $adresse['typeVoie'] ?? null,
                $adresse['voie'] ?? null,
                $adresse['codePostal'] ?? null,
                $adresse['commune'] ?? null,
            ])->filter()->implode(' '));
        }

        return null;
    }

    private function extractCodePostal(array $item): ?string
    {
        return
            $item['codePostal']
            ?? $item['code_postal']
            ?? $item['formality']['content']['personneMorale']['etablissementPrincipal']['adresse']['codePostal']
            ?? null;
    }

    private function extractVille(array $item): ?string
    {
        return
            $item['ville']
            ?? $item['commune']
            ?? $item['formality']['content']['personneMorale']['etablissementPrincipal']['adresse']['commune']
            ?? null;
    }

    private function extractDirigeants(array $item): ?array
    {
        return
            $item['dirigeants']
            ?? $item['representants']
            ?? $item['formality']['content']['personneMorale']['composition']['pouvoirs']
            ?? null;
    }
}