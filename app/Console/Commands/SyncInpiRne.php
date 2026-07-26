<?php

namespace App\Console\Commands;

use App\Services\Import\InpiRneImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncInpiRne extends Command
{
    protected $signature = '
    inpi:sync
    {--limit=1        : Nombre max de fichiers (défaut 1 = uniquement le plus récent)}
    {--type=formalites: formalites ou comptes}
    {--fresh          : Vide la table avant import}
    {--stock-only     : Télécharger uniquement les fichiers stock (pas les flux quotidiens)}
    {--no-delete      : Garder le fichier ZIP local après import}
';
    protected $description = 'Synchronise le(s) fichier(s) RNE depuis le FTP INPI et importe les entreprises en local';

    public function handle(InpiRneImportService $importService): int
    {
        if ($this->option('fresh')) {
            $this->warn('Vidage de la table rne_entreprises...');
            \App\Models\Back\RneEntreprise::truncate();
            $this->info('Table vidée.');
        } else {
            $this->info('Import en mode incrémental (les entreprises déjà présentes ne seront pas réimportées).');
        }

        ini_set('memory_limit', env('INPI_RNE_MEMORY_LIMIT', '2048M'));
        set_time_limit((int) env('INPI_RNE_TIMEOUT', 0));

        $this->info('Connexion au FTP INPI...');

        $disk  = Storage::disk('inpi_ftp');
        $type  = strtolower((string) $this->option('type'));
        $limit = (int) $this->option('limit');
        $stockOnly = $this->option('stock-only');

        $files = collect($disk->files('/'))
            ->filter(function ($file) use ($type, $stockOnly) {
                $lower = strtolower($file);

                // Extension valide
                $isValid = str_ends_with($lower, '.zip')
                    || str_ends_with($lower, '.json')
                    || str_ends_with($lower, '.ndjson');
                if (!$isValid) return false;

                // ── Stock uniquement (exclure les flux quotidiens) ──
                if ($stockOnly && !str_contains($lower, 'stock_')) return false;

                // Type formalités ou comptes
                if ($type === 'formalites') return str_contains($lower, 'formalites');
                if ($type === 'comptes')    return str_contains($lower, 'comptes');

                return true;
            })
            ->sortDesc()           // Plus récent en premier (tri YYYYMMDD)
            ->take($limit)         // --limit=1 par défaut = 1 seul fichier
            ->values();

        if ($files->isEmpty()) {
            $this->warn('Aucun fichier trouvé sur le FTP.');
            return self::SUCCESS;
        }

        $this->info("Fichiers à traiter : " . $files->count());
        $files->each(fn($f) => $this->line("  → {$f}"));

        $localDir = storage_path('app/imports/inpi');
        if (!is_dir($localDir)) mkdir($localDir, 0777, true);

        foreach ($files as $remoteFile) {
            $filename  = basename($remoteFile);
            $localPath = $localDir . DIRECTORY_SEPARATOR . $filename;

            // ── Téléchargement (skip si déjà présent) ────────────
            if (file_exists($localPath)) {
                $sizeMb = round(filesize($localPath) / 1024 / 1024, 1);
                $this->line("Déjà téléchargé : {$filename} ({$sizeMb} Mo) → import direct");
            } else {
                $this->info("Téléchargement : {$remoteFile}");

                try {
                    $stream = $disk->readStream($remoteFile);
                    if (!$stream) {
                        $this->error("Impossible de lire : {$remoteFile}");
                        continue;
                    }

                    $localStream = fopen($localPath, 'w+b');
                    $startTime   = microtime(true);
                    stream_copy_to_stream($stream, $localStream);
                    $elapsed     = round(microtime(true) - $startTime);
                    $sizeMb      = round(filesize($localPath) / 1024 / 1024, 1);

                    is_resource($stream)      && fclose($stream);
                    is_resource($localStream) && fclose($localStream);

                    $this->info("Téléchargement terminé : {$filename} ({$sizeMb} Mo en {$elapsed}s)");

                } catch (\Throwable $e) {
                    $this->error("Erreur téléchargement {$remoteFile} : " . $e->getMessage());
                    continue;
                }
            }

            // ── Import ────────────────────────────────────────────
            $this->info("Import : {$filename}");
            $startImport = microtime(true);

            try {
                $count   = $importService->importFile($localPath);
                $elapsed = round(microtime(true) - $startImport);
                $this->info("Import terminé : {$count} enregistrements en {$elapsed}s.");
            } catch (\Throwable $e) {
                $this->error("Erreur import {$filename} : " . $e->getMessage());
            }

            // ── Nettoyage du ZIP (libère de l'espace disque) ──────
            if (!$this->option('no-delete') && file_exists($localPath)) {
                unlink($localPath);
                $this->line("Fichier ZIP supprimé (espace libéré).");
            }
        }

        return self::SUCCESS;
    }
}
