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

        Log::warning('Format INPI non géré', ['path' => $path, 'extension' => $extension]);
        return 0;
    }

    private function importZipStreaming(string $zipPath): int
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Impossible d'ouvrir le ZIP : {$zipPath}");
        }

        $total = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fileName = $zip->getNameIndex($i);
            $lower    = strtolower($fileName);

            if (!str_ends_with($lower, '.json') && !str_ends_with($lower, '.ndjson')) continue;

            echo "Lecture streaming : {$fileName}" . PHP_EOL;

            $stream = $zip->getStream($fileName);
            if (!$stream) { echo "Impossible de lire : {$fileName}" . PHP_EOL; continue; }

            $total += $this->importJsonStream($stream);
            if (is_resource($stream)) fclose($stream);
        }

        $zip->close();
        return $total;
    }

    private function importJsonFileStreaming(string $path): int
    {
        $stream = fopen($path, 'r');
        if (!$stream) return 0;
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
            if (!is_array($data)) continue;

            $row = $this->mapEntrepriseRow($data);
            if (!$row) continue;

            $chunk[] = $row;
            $total++;

            if (count($chunk) >= $this->chunkSize) {
                $this->bulkUpsert($chunk);
                $chunk = [];
                echo "Importés : {$total}" . PHP_EOL;
            }
        }

        if (!empty($chunk)) $this->bulkUpsert($chunk);
        return $total;
    }

    private function bulkUpsert(array $rows): void
    {
        RneEntreprise::upsert($rows, ['siren'], [
            'siret_siege', 'denomination', 'forme_juridique',
            'capital_social', 'capital_social_numeric', 'activite',
            'date_creation', 'adresse_complete', 'code_postal', 'ville',
            'dirigeants', 'etablissements', 'raw_data', 'updated_at',
        ]);
    }

    private function mapEntrepriseRow(array $item): ?array
    {
        $siren = $this->extractSiren($item);
        if (!$siren || strlen($siren) !== 9) return null;

        // ── Détecter le type de personne ─────────────────────
        $typePersonne = strtoupper($item['typePersonne'] ?? '');
        $content      = $item['formality']['content'] ?? $item['content'] ?? [];
        $isPhysique   = isset($content['personnePhysique']) || $typePersonne === 'P';

        $capital = $this->extractCapital($item, $content);

        return [
            'siren'                  => $siren,
            'siret_siege'            => $this->extractSiretSiege($item, $content, $siren, $isPhysique),
            'denomination'           => $this->extractDenomination($item, $content, $isPhysique),
            'forme_juridique'        => $this->extractFormeJuridique($item, $content),
            'capital_social'         => $capital,
            'capital_social_numeric' => $this->capitalToDecimal($capital),
            'activite'               => $this->extractActivite($item, $content, $isPhysique),
            'date_creation'          => $this->extractDateCreation($item, $content),
            'adresse_complete'       => $this->extractAdresse($item, $content, $isPhysique),
            'code_postal'            => $this->extractCodePostal($item, $content, $isPhysique),
            'ville'                  => $this->extractVille($item, $content, $isPhysique),
            'dirigeants'             => $this->toJsonOrNull($this->extractDirigeants($item, $content, $isPhysique)),
            'etablissements'         => $this->toJsonOrNull($this->extractEtablissements($item, $content, $isPhysique)),
            'raw_data'               => json_encode($item, JSON_UNESCAPED_UNICODE),
            'created_at'             => now(),
            'updated_at'             => now(),
        ];
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — SIREN
    // ════════════════════════════════════════════════════════════
    private function extractSiren(array $item): ?string
    {
        $value = data_get($item, 'siren')
            ?? data_get($item, 'formality.siren')
            ?? data_get($item, 'formality.content.siren')
            ?? data_get($item, 'formality.content.personneMorale.identite.entreprise.siren')
            ?? data_get($item, 'formality.content.personnePhysique.identite.entreprise.siren');

        $value = preg_replace('/\D/', '', (string) $value);
        return $value ?: null;
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — SIRET SIÈGE
    // Personne morale : établissementPrincipal.descriptionEtablissement.siret
    // Personne physique : siren + nicSiege
    // ════════════════════════════════════════════════════════════
    private function extractSiretSiege(array $item, array $content, string $siren, bool $isPhysique): ?string
    {
        if ($isPhysique) {
            $nicSiege = data_get($content, 'personnePhysique.identite.entreprise.nicSiege')
                ?? data_get($content, 'personnePhysique.etablissementPrincipal.descriptionEtablissement.siret');

            if ($nicSiege && strlen($nicSiege) === 5) return $siren . $nicSiege;

            $siret = data_get($content, 'personnePhysique.etablissementPrincipal.descriptionEtablissement.siret');
            $siret = preg_replace('/\D/', '', (string) $siret);
            return strlen($siret) === 14 ? $siret : null;
        }

        $siret = data_get($content, 'personneMorale.etablissementPrincipal.descriptionEtablissement.siret')
            ?? data_get($content, 'personneMorale.etablissementPrincipal.siret')
            ?? data_get($content, 'personneMorale.identite.entreprise.siretSiege')
            ?? data_get($item, 'siret')
            ?? data_get($item, 'siretSiege');

        $siret = preg_replace('/\D/', '', (string) $siret);
        return strlen($siret) === 14 ? $siret : null;
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — DÉNOMINATION
    // Personne morale  : denomination / denominationSociale
    // Personne physique: nom + prénom
    // ════════════════════════════════════════════════════════════
    private function extractDenomination(array $item, array $content, bool $isPhysique): ?string
    {
        if ($isPhysique) {
            $desc = data_get($content, 'personnePhysique.identite.entrepreneur.descriptionPersonne');
            if ($desc) {
                $nom    = trim($desc['nom']     ?? '');
                $prenoms= is_array($desc['prenoms'] ?? null)
                    ? implode(' ', $desc['prenoms'])
                    : ($desc['prenoms'] ?? '');
                $full   = trim($nom . ' ' . $prenoms);
                if ($full) return strtoupper($full);
            }
            // Fallback : nom entrepreneur
            $nom = data_get($content, 'personnePhysique.identite.entreprise.nom')
                ?? data_get($content, 'personnePhysique.identite.description.nom');
            return $nom ? strtoupper($nom) : null;
        }

        return data_get($item, 'denomination')
            ?? data_get($item, 'denominationSociale')
            ?? data_get($content, 'personneMorale.identite.entreprise.denomination')
            ?? data_get($content, 'personneMorale.identite.entreprise.denominationSociale')
            ?? data_get($content, 'personneMorale.identite.description.denomination')
            ?? data_get($content, 'personneMorale.identite.description.denominationSociale');
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — FORME JURIDIQUE
    // ════════════════════════════════════════════════════════════
    private function extractFormeJuridique(array $item, array $content): ?string
    {
        return data_get($item, 'formeJuridique')
            ?? data_get($content, 'natureCreation.formeJuridique')
            ?? data_get($content, 'personneMorale.identite.entreprise.formeJuridique')
            ?? data_get($content, 'personneMorale.identite.description.formeJuridique')
            ?? data_get($content, 'personnePhysique.identite.entreprise.formeJuridique');
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — CAPITAL SOCIAL
    // ════════════════════════════════════════════════════════════
    private function extractCapital(array $item, array $content): ?string
    {
        $capital = data_get($item, 'capitalSocial')
            ?? data_get($content, 'personneMorale.identite.entreprise.capitalSocial')
            ?? data_get($content, 'personneMorale.identite.description.capitalSocial')
            ?? data_get($content, 'personneMorale.identite.description.montantCapitalSocial');

        if (is_array($capital)) {
            $montant = $capital['montant'] ?? $capital['valeur'] ?? $capital['value'] ?? null;
            $devise  = $capital['devise']  ?? $capital['currency'] ?? 'EUR';
            return $montant ? trim($montant . ' ' . $devise) : null;
        }

        return $capital ? trim((string) $capital) : null;
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — ACTIVITÉ
    // ════════════════════════════════════════════════════════════
    private function extractActivite(array $item, array $content, bool $isPhysique): ?string
    {
        if ($isPhysique) {
            return data_get($content, 'personnePhysique.etablissementPrincipal.descriptionEtablissement.codeApe')
                ?? data_get($content, 'personnePhysique.identite.entreprise.codeApe');
        }

        return data_get($item, 'activite')
            ?? data_get($item, 'activitePrincipale')
            ?? data_get($content, 'personneMorale.etablissementPrincipal.descriptionEtablissement.activites.0.codeApe')
            ?? data_get($content, 'personneMorale.etablissementPrincipal.activites.0.codeApe')
            ?? data_get($content, 'personneMorale.identite.entreprise.activitePrincipale');
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — DATE CRÉATION
    // ════════════════════════════════════════════════════════════
    private function extractDateCreation(array $item, array $content): ?string
    {
        return data_get($item, 'dateCreation')
            ?? data_get($content, 'natureCreation.dateCreation')
            ?? data_get($content, 'personnePhysique.identite.entreprise.dateDebutActiv')
            ?? data_get($content, 'personnePhysique.identite.entreprise.dateImmat')
            ?? data_get($content, 'personneMorale.identite.entreprise.dateCreation')
            ?? data_get($content, 'personneMorale.identite.description.dateCreation')
            ?? data_get($content, 'personneMorale.identite.description.dateImmatriculation');
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — ADRESSE COMPLÈTE
    // Personne physique : personnePhysique.adresseEntreprise.adresse
    //                  OU etablissementPrincipal.adresse
    // Personne morale  : personneMorale.etablissementPrincipal.adresse
    // ════════════════════════════════════════════════════════════
    private function extractAdresse(array $item, array $content, bool $isPhysique): ?string
    {
        if ($isPhysique) {
            $adresse = data_get($content, 'personnePhysique.adresseEntreprise.adresse')
                ?? data_get($content, 'personnePhysique.etablissementPrincipal.adresse');
        } else {
            $adresse = data_get($item, 'adresse')
                ?? data_get($content, 'personneMorale.etablissementPrincipal.adresse')
                ?? data_get($content, 'personneMorale.etablissementPrincipal.adresseEtablissement')
                ?? data_get($content, 'personneMorale.adresseEntreprise');
        }

        if (is_string($adresse)) return $adresse;

        if (is_array($adresse)) {
            return trim(collect([
                $adresse['numVoie']    ?? null,
                $adresse['typeVoie']   ?? null,
                $adresse['voie']       ?? null,
                $adresse['libelleVoie']?? null,
                $adresse['codePostal'] ?? null,
                $adresse['commune']    ?? $adresse['ville'] ?? null,
            ])->filter()->implode(' ')) ?: null;
        }

        return null;
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — CODE POSTAL
    // ════════════════════════════════════════════════════════════
    private function extractCodePostal(array $item, array $content, bool $isPhysique): ?string
    {
        if ($isPhysique) {
            return data_get($content, 'personnePhysique.adresseEntreprise.adresse.codePostal')
                ?? data_get($content, 'personnePhysique.etablissementPrincipal.adresse.codePostal');
        }

        return data_get($item, 'codePostal')
            ?? data_get($content, 'personneMorale.etablissementPrincipal.adresse.codePostal')
            ?? data_get($content, 'personneMorale.etablissementPrincipal.adresseEtablissement.codePostal')
            ?? data_get($content, 'personneMorale.adresseEntreprise.codePostal');
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — VILLE
    // ════════════════════════════════════════════════════════════
    private function extractVille(array $item, array $content, bool $isPhysique): ?string
    {
        if ($isPhysique) {
            return data_get($content, 'personnePhysique.adresseEntreprise.adresse.commune')
                ?? data_get($content, 'personnePhysique.etablissementPrincipal.adresse.commune');
        }

        return data_get($item, 'ville')
            ?? data_get($item, 'commune')
            ?? data_get($content, 'personneMorale.etablissementPrincipal.adresse.commune')
            ?? data_get($content, 'personneMorale.etablissementPrincipal.adresseEtablissement.commune')
            ?? data_get($content, 'personneMorale.adresseEntreprise.commune');
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — DIRIGEANTS
    // Personne physique : l'entrepreneur lui-même
    // Personne morale  : composition.pouvoirs / representants
    // ════════════════════════════════════════════════════════════
    private function extractDirigeants(array $item, array $content, bool $isPhysique): ?array
    {
        if ($isPhysique) {
            $desc = data_get($content, 'personnePhysique.identite.entrepreneur.descriptionPersonne');
            if ($desc) {
                return [[
                    'nom'     => $desc['nom']     ?? null,
                    'prenoms' => $desc['prenoms']  ?? null,
                    'role'    => 'Entrepreneur individuel',
                ]];
            }
            return null;
        }

        return data_get($item, 'dirigeants')
            ?? data_get($item, 'representants')
            ?? data_get($content, 'personneMorale.composition.pouvoirs')
            ?? data_get($content, 'personneMorale.composition.representants');
    }

    // ════════════════════════════════════════════════════════════
    // EXTRACTION — ÉTABLISSEMENTS
    // ════════════════════════════════════════════════════════════
    private function extractEtablissements(array $item, array $content, bool $isPhysique): ?array
    {
        if ($isPhysique) {
            $etab = data_get($content, 'personnePhysique.etablissementPrincipal');
            return $etab ? [$etab] : null;
        }

        $value = data_get($item, 'etablissements')
            ?? data_get($content, 'etablissements')
            ?? data_get($content, 'personneMorale.etablissements')
            ?? data_get($content, 'personneMorale.etablissementPrincipal');

        return is_array($value) ? $value : null;
    }

    // ════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ════════════════════════════════════════════════════════════
    private function toJsonOrNull($value): ?string
    {
        if (!$value) return null;
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function capitalToDecimal(?string $capital): ?float
    {
        if (!$capital) return null;

        $value = strtoupper($capital);
        $value = str_replace(['€', 'EUR', 'EUROS'], '', $value);
        $value = str_replace(["\xc2\xa0", ' '], '', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        if (substr_count($value, '.') > 1) {
            $parts = explode('.', $value);
            $last  = array_pop($parts);
            $value = implode('', $parts) . '.' . $last;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
