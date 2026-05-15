<?php

namespace App\Services\Import;

use App\Models\Back\RneEntreprise;
use Illuminate\Support\Facades\Log;
use JsonMachine\Items;

class InpiRneImportService
{
    private int $chunkSize = 500;

    public function importFile(string $path): int
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Fichier introuvable : {$path}");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'zip') {
            return $this->importZipStreaming($path);
        }

        if (in_array($extension, ['json', 'ndjson'], true)) {
            return $this->importJsonFileStreaming($path);
        }

        Log::warning('Format INPI non géré', [
            'path' => $path,
            'extension' => $extension,
        ]);

        return 0;
    }

    private function importZipStreaming(string $zipPath): int
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Impossible d’ouvrir le ZIP : {$zipPath}");
        }

        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            $lower = strtolower($fileName);

            if (!str_ends_with($lower, '.json') && !str_ends_with($lower, '.ndjson')) {
                continue;
            }

            echo "Lecture streaming : {$fileName}" . PHP_EOL;

            $stream = $zip->getStream($fileName);

            if (!$stream) {
                echo "Impossible de lire : {$fileName}" . PHP_EOL;
                continue;
            }

            $total += $this->importJsonStream($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            // TEST : garde ça pour importer seulement le premier fichier JSON.
            // Quand ça fonctionne, commente cette ligne.
            // break;
        }

        $zip->close();

        return $total;
    }

    private function importJsonFileStreaming(string $path): int
    {
        $stream = fopen($path, 'r');

        if (!$stream) {
            return 0;
        }

        $total = $this->importJsonStream($stream);

        fclose($stream);

        return $total;
    }

    private function importJsonStream($stream): int
    {
        $items = Items::fromStream($stream);

        $chunk = [];
        $total = 0;

        foreach ($items as $item) {
            $data = json_decode(json_encode($item), true);

            if (!is_array($data)) {
                continue;
            }

            $row = $this->mapEntrepriseRow($data);

            if (!$row) {
                continue;
            }

            $chunk[] = $row;
            $total++;

            if (count($chunk) >= $this->chunkSize) {
                $this->bulkUpsert($chunk);
                $chunk = [];

                echo "Importés : {$total}" . PHP_EOL;
            }
        }

        if (!empty($chunk)) {
            $this->bulkUpsert($chunk);
        }

        return $total;
    }

    private function bulkUpsert(array $rows): void
    {
        RneEntreprise::upsert(
            $rows,
            ['siren'],
            [
                'siret_siege',
                'denomination',
                'forme_juridique',
                'capital_social',
                'capital_social_numeric',
                'activite',
                'date_creation',
                'adresse_complete',
                'code_postal',
                'ville',
                'dirigeants',
                'etablissements',
                'raw_data',
                'updated_at',
            ]
        );
    }

    private function mapEntrepriseRow(array $item): ?array
    {
        $siren = $this->extractSiren($item);

        if (!$siren || strlen($siren) !== 9) {
            return null;
        }

        $capital = $this->extractCapital($item);

        return [
            'siren' => $siren,
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
            'dirigeants' => $this->toJsonOrNull($this->extractDirigeants($item)),
            'etablissements' => $this->toJsonOrNull($this->extractEtablissements($item)),
            'raw_data' => json_encode($item, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function toJsonOrNull($value): ?string
    {
        if (!$value) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function extractSiren(array $item): ?string
    {
        $value =
            data_get($item, 'siren')
            ?? data_get($item, 'formality.siren')
            ?? data_get($item, 'formality.content.siren')
            ?? data_get($item, 'formality.content.personneMorale.siren')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.siren')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.siren');

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
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.siretSiege');

        $value = preg_replace('/\D/', '', (string) $value);

        return strlen($value) === 14 ? $value : null;
    }

    private function extractDenomination(array $item): ?string
    {
        return
            data_get($item, 'denomination')
            ?? data_get($item, 'denominationSociale')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.denomination')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.denominationSociale')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.denomination')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.denominationSociale');
    }

    private function extractFormeJuridique(array $item): ?string
    {
        return
            data_get($item, 'formeJuridique')
            ?? data_get($item, 'formality.content.natureCreation.formeJuridique')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.formeJuridique')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.formeJuridique');
    }

    private function extractCapital(array $item): ?string
    {
        $capital =
            data_get($item, 'capitalSocial')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.capitalSocial')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.capitalSocial')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.montantCapitalSocial');

        if (is_array($capital)) {
            $montant = $capital['montant'] ?? $capital['valeur'] ?? $capital['value'] ?? null;
            $devise = $capital['devise'] ?? $capital['currency'] ?? 'EUR';

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
            ?? data_get($item, 'activitePrincipale')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.descriptionEtablissement.activites.0.codeApe')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.activites.0.codeApe')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.activitePrincipale');
    }

    private function extractDateCreation(array $item): ?string
    {
        return
            data_get($item, 'dateCreation')
            ?? data_get($item, 'formality.content.natureCreation.dateCreation')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.dateCreation')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.dateCreation')
            ?? data_get($item, 'formality.content.personneMorale.identite.description.dateImmatriculation');
    }

    private function extractAdresse(array $item): ?string
    {
        $adresse =
            data_get($item, 'adresse')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresse')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresseEtablissement')
            ?? data_get($item, 'formality.content.personneMorale.adresseEntreprise');

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
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresse.codePostal')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresseEtablissement.codePostal')
            ?? data_get($item, 'formality.content.personneMorale.adresseEntreprise.codePostal');
    }

    private function extractVille(array $item): ?string
    {
        return
            data_get($item, 'ville')
            ?? data_get($item, 'commune')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresse.commune')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal.adresseEtablissement.commune')
            ?? data_get($item, 'formality.content.personneMorale.adresseEntreprise.commune');
    }

    private function extractDirigeants(array $item): ?array
    {
        return
            data_get($item, 'dirigeants')
            ?? data_get($item, 'representants')
            ?? data_get($item, 'formality.content.personneMorale.composition.pouvoirs')
            ?? data_get($item, 'formality.content.personneMorale.composition.representants');
    }

    private function extractEtablissements(array $item): ?array
    {
        $value =
            data_get($item, 'etablissements')
            ?? data_get($item, 'formality.content.etablissements')
            ?? data_get($item, 'formality.content.personneMorale.etablissements')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal');

        return is_array($value) ? $value : null;
    }
}