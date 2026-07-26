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

            // Ne pas break : importer tous les fichiers JSON du ZIP
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

    // ─────────────────────────────────────────────────────────────
    // MAPPING PRINCIPAL (CORRIGÉ)
    // ─────────────────────────────────────────────────────────────
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

    // ── HELPERS DE CONVERSION ──────────────────────────────────
    private function toJsonOrNull($value): ?string
    {
        if (!$value) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
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

    // ─────────────────────────────────────────────────────────────
    // MÉTHODES D'EXTRACTION (CORRIGÉES)
    // ─────────────────────────────────────────────────────────────

    /**
     * Extrait le SIREN en explorant toutes les clés possibles.
     */
    private function extractSiren(array $item): ?string
    {
        $paths = [
            'siren',
            'formality.siren',
            'formality.content.siren',
            'formality.content.personneMorale.siren',
            'formality.content.personnePhysique.identite.entreprise.siren',
            'formality.content.personneMorale.identite.entreprise.siren',
            'formality.content.personnePhysique.entreprise.siren',
            'formality.content.personneMorale.identite.description.siren',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value) {
                $clean = preg_replace('/\D/', '', (string) $value);
                if (strlen($clean) === 9) {
                    return $clean;
                }
            }
        }

        return null;
    }

    /**
     * Extrait le SIRET du siège.
     */
    private function extractSiretSiege(array $item): ?string
    {
        $paths = [
            'siret',
            'siretSiege',
            'formality.content.personneMorale.etablissementPrincipal.descriptionEtablissement.siret',
            'formality.content.personneMorale.etablissementPrincipal.siret',
            'formality.content.personneMorale.identite.entreprise.siretSiege',
            'formality.content.personnePhysique.etablissementPrincipal.descriptionEtablissement.siret',
            'formality.content.personnePhysique.identite.entreprise.siretSiege',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value) {
                $clean = preg_replace('/\D/', '', (string) $value);
                if (strlen($clean) === 14) {
                    return $clean;
                }
            }
        }

        return null;
    }

    /**
     * Extrait la dénomination (ou nom pour les personnes physiques).
     */
    private function extractDenomination(array $item): ?string
    {
        // Pour les personnes physiques, on peut prendre le nom + prénoms
        $nom = null;
        $prenoms = [];

        // Essayer d'abord les champs de dénomination classique
        $paths = [
            'denomination',
            'denominationSociale',
            'formality.content.personneMorale.identite.entreprise.denomination',
            'formality.content.personneMorale.identite.entreprise.denominationSociale',
            'formality.content.personneMorale.identite.description.denomination',
            'formality.content.personneMorale.identite.description.denominationSociale',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value && !is_array($value)) {
                return trim((string) $value);
            }
        }

        // Si c'est une personne physique, on assemble nom + prénoms
        $nom = data_get($item, 'formality.content.personnePhysique.identite.entrepreneur.descriptionPersonne.nom')
            ?? data_get($item, 'formality.content.personnePhysique.identite.descriptionPersonne.nom')
            ?? data_get($item, 'formality.content.personnePhysique.identite.entreprise.nom')
            ?? null;

        $prenoms = data_get($item, 'formality.content.personnePhysique.identite.entrepreneur.descriptionPersonne.prenoms')
            ?? data_get($item, 'formality.content.personnePhysique.identite.descriptionPersonne.prenoms')
            ?? [];

        if ($nom) {
            $prenomsStr = is_array($prenoms) ? implode(' ', $prenoms) : (string) $prenoms;
            return trim($nom . ' ' . $prenomsStr);
        }

        return null;
    }

    /**
     * Extrait le code de forme juridique.
     */
    private function extractFormeJuridique(array $item): ?string
    {
        $paths = [
            'formeJuridique',
            'formality.content.natureCreation.formeJuridique',
            'formality.content.personneMorale.identite.entreprise.formeJuridique',
            'formality.content.personneMorale.identite.description.formeJuridique',
            'formality.content.personnePhysique.identite.entreprise.formeJuridique',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value && !is_array($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Extrait le capital social (montant + devise).
     */
    private function extractCapital(array $item): ?string
    {
        $paths = [
            'capitalSocial',
            'formality.content.personneMorale.identite.entreprise.capitalSocial',
            'formality.content.personneMorale.identite.description.capitalSocial',
            'formality.content.personneMorale.identite.description.montantCapitalSocial',
            'formality.content.personneMorale.identite.entreprise.montantCapitalSocial',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value) {
                if (is_array($value)) {
                    $montant = $value['montant'] ?? $value['valeur'] ?? $value['value'] ?? null;
                    $devise = $value['devise'] ?? $value['currency'] ?? 'EUR';
                    if ($montant) {
                        return trim($montant . ' ' . $devise);
                    }
                } elseif (is_scalar($value)) {
                    return trim((string) $value);
                }
            }
        }

        return null;
    }

    /**
     * Extrait l'activité (code APE ou description).
     */
    private function extractActivite(array $item): ?string
    {
        $paths = [
            'activite',
            'activitePrincipale',
            'formality.content.personneMorale.etablissementPrincipal.descriptionEtablissement.activites.0.codeApe',
            'formality.content.personneMorale.etablissementPrincipal.activites.0.codeApe',
            'formality.content.personneMorale.identite.entreprise.activitePrincipale',
            'formality.content.personnePhysique.etablissementPrincipal.descriptionEtablissement.activites.0.codeApe',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value && !is_array($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Extrait la date de création / immatriculation.
     */
    private function extractDateCreation(array $item): ?string
    {
        $paths = [
            'dateCreation',
            'formality.content.natureCreation.dateCreation',
            'formality.content.personneMorale.identite.entreprise.dateCreation',
            'formality.content.personneMorale.identite.description.dateCreation',
            'formality.content.personneMorale.identite.description.dateImmatriculation',
            'formality.content.personnePhysique.identite.entreprise.dateImmat',
            'formality.content.personnePhysique.identite.entreprise.dateCreation',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value && !is_array($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Extrait l'adresse complète (à partir de l'établissement principal).
     */
    private function extractAdresse(array $item): ?string
    {
        $adresseArray = $this->extractAdresseArray($item);

        if (!$adresseArray) {
            return null;
        }

        // Si c'est déjà une chaîne, on la retourne
        if (is_string($adresseArray)) {
            return trim($adresseArray);
        }

        if (!is_array($adresseArray)) {
            return null;
        }

        $parts = [
            $adresseArray['numVoie'] ?? $adresseArray['numeroVoie'] ?? null,
            $adresseArray['typeVoie'] ?? null,
            $adresseArray['voie'] ?? $adresseArray['libelleVoie'] ?? null,
            $adresseArray['codePostal'] ?? null,
            $adresseArray['commune'] ?? $adresseArray['ville'] ?? null,
        ];

        return trim(collect($parts)->filter()->implode(' '));
    }

    /**
     * Extrait l'objet adresse (pour réutilisation).
     */
    private function extractAdresseArray(array $item): ?array
    {
        $paths = [
            'adresse',
            'formality.content.personneMorale.etablissementPrincipal.adresse',
            'formality.content.personneMorale.etablissementPrincipal.adresseEtablissement',
            'formality.content.personneMorale.adresseEntreprise',
            'formality.content.personnePhysique.etablissementPrincipal.adresse',
            'formality.content.personnePhysique.adresseEntreprise',
        ];

        foreach ($paths as $path) {
            $value = data_get($item, $path);
            if ($value) {
                if (is_string($value)) {
                    return ['adresse_complete' => $value];
                }
                if (is_array($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Extrait le code postal.
     */
    private function extractCodePostal(array $item): ?string
    {
        $adresse = $this->extractAdresseArray($item);
        if (is_array($adresse)) {
            return $adresse['codePostal'] ?? null;
        }
        return null;
    }

    /**
     * Extrait la ville.
     */
    private function extractVille(array $item): ?string
    {
        $adresse = $this->extractAdresseArray($item);
        if (is_array($adresse)) {
            return $adresse['commune'] ?? $adresse['ville'] ?? null;
        }
        return null;
    }

    /**
     * Extrait les dirigeants (pour personne morale, on prend les représentants ; pour personne physique, on prend la personne elle-même).
     */
    private function extractDirigeants(array $item): ?array
    {
        // 1. Essayer d'abord les dirigeants explicites (personne morale)
        $dirigeants = data_get($item, 'dirigeants')
            ?? data_get($item, 'representants')
            ?? data_get($item, 'formality.content.personneMorale.composition.pouvoirs')
            ?? data_get($item, 'formality.content.personneMorale.composition.representants');

        if ($dirigeants && is_array($dirigeants)) {
            return $dirigeants;
        }

        // 2. Si c'est une personne physique, le dirigeant est la personne elle-même
        $nom = data_get($item, 'formality.content.personnePhysique.identite.entrepreneur.descriptionPersonne.nom')
            ?? data_get($item, 'formality.content.personnePhysique.identite.descriptionPersonne.nom')
            ?? null;

        $prenoms = data_get($item, 'formality.content.personnePhysique.identite.entrepreneur.descriptionPersonne.prenoms')
            ?? data_get($item, 'formality.content.personnePhysique.identite.descriptionPersonne.prenoms')
            ?? [];

        if ($nom || !empty($prenoms)) {
            return [
                [
                    'nom' => $nom,
                    'prenoms' => is_array($prenoms) ? implode(' ', $prenoms) : $prenoms,
                    'type' => 'personne_physique',
                ]
            ];
        }

        return null;
    }

    /**
     * Extrait les établissements (liste).
     */
    private function extractEtablissements(array $item): ?array
    {
        $value = data_get($item, 'etablissements')
            ?? data_get($item, 'formality.content.etablissements')
            ?? data_get($item, 'formality.content.personneMorale.etablissements')
            ?? data_get($item, 'formality.content.personneMorale.etablissementPrincipal')
            ?? data_get($item, 'formality.content.personnePhysique.etablissements')
            ?? data_get($item, 'formality.content.personnePhysique.etablissementPrincipal');

        if (is_array($value)) {
            return $value;
        }

        return null;
    }
}
