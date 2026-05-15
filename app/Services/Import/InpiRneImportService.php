<?php

namespace App\Services\Import;

use App\Models\Back\RneEntreprise;
use Illuminate\Support\Facades\Log;
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

        Log::warning('Format INPI non géré', [
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
                $handled = $this->handleDecodedJson($decoded);
                $imported += $handled;
                continue;
            }

            $buffer .= $line;
        }

        fclose($handle);

        if ($buffer) {
            $json = json_decode($buffer, true);

            if (is_array($json)) {
                $imported += $this->handleDecodedJson($json);
            }
        }

        return $imported;
    }

    private function handleDecodedJson(array $json): int
    {
        $count = 0;

        if (isset($json[0]) && is_array($json[0])) {
            foreach ($json as $item) {
                if ($this->upsertEntreprise($item)) {
                    $count++;
                }
            }

            return $count;
        }

        if (isset($json['data']) && is_array($json['data'])) {
            foreach ($json['data'] as $item) {
                if (is_array($item) && $this->upsertEntreprise($item)) {
                    $count++;
                }
            }

            return $count;
        }

        if ($this->upsertEntreprise($json)) {
            $count++;
        }

        return $count;
    }

    private function upsertEntreprise(array $item): bool
    {
        $siren = $this->extractSiren($item);

        if (!$siren || strlen($siren) !== 9) {
            return false;
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
                'etablissements' => $this->extractEtablissements($item),
                'raw_data' => $item,
            ]
        );

        return true;
    }

    private function extractSiren(array $item): ?string
    {
        $value =
            data_get($item, 'siren')
            ?? data_get($item, 'formality.siren')
            ?? data_get($item, 'formality.content.siren')
            ?? data_get($item, 'formality.content.personneMorale.siren')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.siren')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.siren')
            ?? data_get($item, 'personneMorale.siren')
            ?? null;

        $value = preg_replace('/\D/', '', (string) $value);

        return $value ?: null;
    }

    private function extractSiretSiege(array $item): ?string
    {
        $value =
            data_get($item, 'siret')
            ?? data_get($item, 'siretSiege')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.descriptionEtablissement.siret')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.siret')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.siretSiege')
            ?? null;

        $value = preg_replace('/\D/', '', (string) $value);

        return strlen($value) === 14 ? $value : null;
    }

    private function extractDenomination(array $item): ?string
    {
        return
            data_get($item, 'denomination')
            ?? data_get($item, 'denominationSociale')
            ?? data_get($item, 'nomCommercial')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.denomination')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.denominationSociale')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.denomination')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.denominationSociale')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.nom')
            ?? null;
    }

    private function extractFormeJuridique(array $item): ?string
    {
        return
            data_get($item, 'formeJuridique')
            ?? data_get($item, 'forme_juridique')
            ?? data_get($item, 'formeJuridiqueCode')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.formeJuridique')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.formeJuridique')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.formeJuridiqueCode')
            ?? null;
    }

    private function extractCapital(array $item): ?string
    {
        $capital =
            data_get($item, 'capitalSocial')
            ?? data_get($item, 'capital_social')
            ?? data_get($item, 'montantCapital')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.capitalSocial')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.montantCapital')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.capitalSocial')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.montantCapital')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.montantCapitalSocial')
            ?? null;

        if (is_array($capital)) {
            $montant =
                $capital['montant']
                ?? $capital['valeur']
                ?? $capital['capital']
                ?? $capital['value']
                ?? null;

            $devise =
                $capital['devise']
                ?? $capital['currency']
                ?? 'EUR';

            return $montant ? trim($montant . ' ' . $devise) : null;
        }

        return $capital ? trim((string) $capital) : null;
    }

    private function capitalToDecimal(?string $capital): ?float
    {
        if (!$capital) {
            return null;
        }

        $value = strtoupper($capital);
        $value = str_replace(['€', 'EUR', 'EUROS'], '', $value);
        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        if (substr_count($value, '.') > 1) {
            $parts = explode('.', $value);
            $last = array_pop($parts);
            $value = implode('', $parts) . '.' . $last;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function extractActivite(array $item): ?string
    {
        return
            data_get($item, 'activite')
            ?? data_get($item, 'codeApe')
            ?? data_get($item, 'activitePrincipale')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.descriptionEtablissement.activites.0.codeApe')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.activites.0.codeApe')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.activitePrincipale')
            ?? null;
    }

    private function extractDateCreation(array $item): ?string
    {
        return
            data_get($item, 'dateCreation')
            ?? data_get($item, 'date_creation')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.dateCreation')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.dateCreation')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.dateImmatriculation')
            ?? null;
    }

    private function extractAdresse(array $item): ?string
    {
        $adresse =
            data_get($item, 'adresse')
            ?? data_get($item, 'adresse_complete')
            ?? data_get($item, 'adresseComplete')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresse')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresseEtablissement')
            ?? data_get($item, 'formality.content.personneMorale.adresseEntreprise')
            ?? null;

        if (is_string($adresse)) {
            return $adresse;
        }

        if (is_array($adresse)) {
            return trim(collect([
                $adresse['numVoie'] ?? null,
                $adresse['numeroVoie'] ?? null,
                $adresse['typeVoie'] ?? null,
                $adresse['voie'] ?? null,
                $adresse['libelleVoie'] ?? null,
                $adresse['codePostal'] ?? null,
                $adresse['commune'] ?? null,
                $adresse['ville'] ?? null,
            ])->filter()->implode(' '));
        }

        return null;
    }

    private function extractCodePostal(array $item): ?string
    {
        return
            data_get($item, 'codePostal')
            ?? data_get($item, 'code_postal')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresse.codePostal')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresseEtablissement.codePostal')
            ?? data_get($item, 'formality.content.personneMorale.adresseEntreprise.codePostal')
            ?? null;
    }

    private function extractVille(array $item): ?string
    {
        return
            data_get($item, 'ville')
            ?? data_get($item, 'commune')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresse.commune')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresseEtablissement.commune')
            ?? data_get($item, 'formality.content.personneMorale.adresseEntreprise.commune')
            ?? null;
    }

    private function extractDirigeants(array $item): ?array
    {
        return
            data_get($item, 'dirigeants')
            ?? data_get($item, 'representants')
            ?? data_get($item, 'formality.content.personneMorale.composition.pouvoirs')
            ?? data_get($item, 'formality.content.personneMorale.composition.representants')
            ?? null;
    }

    private function extractEtablissements(array $item): ?array
    {
        $value =
            data_get($item, 'etablissements')
            ?? data_get($item, 'formality.content.etablissements')
            ?? data_get($item, 'formality.content.personneMorale.etablissements')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal')
            ?? null;

        if (!$value) {
            return null;
        }

        return is_array($value) ? $value : null;
    }
}