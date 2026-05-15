<?php

namespace App\Console\Commands;

use App\Services\Import\InpiRneImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncInpiRne extends Command
{
    protected $signature = '
    inpi:sync
    {--limit=3 : Nombre max de fichiers}
    {--type=formalites : formalites ou comptes}
    {--fresh : Vide la table avant import}
';
    protected $description = 'Synchronise les fichiers RNE depuis le FTP INPI et importe les entreprises en local';

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

        $disk = Storage::disk('inpi_ftp');

        $type = strtolower((string) $this->option('type'));

        $files = collect($disk->files('/'))
            ->filter(function ($file) use ($type) {
                $lower = strtolower($file);

                $isValid = str_ends_with($lower, '.zip')
                    || str_ends_with($lower, '.json')
                    || str_ends_with($lower, '.ndjson');

                if (!$isValid) {
                    return false;
                }

                if ($type === 'formalites') {
                    return str_contains($lower, 'formalites');
                }

                if ($type === 'comptes') {
                    return str_contains($lower, 'comptes');
                }

                return true;
            })
            ->sortDesc()
            ->take((int) $this->option('limit'))
            ->values();

        if ($files->isEmpty()) {
            $this->warn('Aucun fichier ZIP/JSON/NDJSON trouvé sur le FTP.');
            return self::SUCCESS;
        }

        $localDir = storage_path('app/imports/inpi');

        if (!is_dir($localDir)) {
            mkdir($localDir, 0777, true);
        }

        foreach ($files as $remoteFile) {
            $filename = basename($remoteFile);
            $localPath = $localDir . DIRECTORY_SEPARATOR . $filename;

            $this->info("Téléchargement : {$remoteFile}");

            if (!file_exists($localPath)) {
                try {
                    $stream = $disk->readStream($remoteFile);

                    if (!$stream) {
                        $this->error("Impossible de lire : {$remoteFile}");
                        continue;
                    }

                    $localStream = fopen($localPath, 'w+b');

                    stream_copy_to_stream($stream, $localStream);

                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    if (is_resource($localStream)) {
                        fclose($localStream);
                    }

                    $this->info("Téléchargement terminé : {$filename}");
                } catch (\Throwable $e) {
                    $this->error("Erreur téléchargement {$remoteFile} : " . $e->getMessage());
                    continue;
                }
            } else {
                $this->line("Déjà téléchargé : {$filename}");
            }

            $this->info("Import : {$filename}");

            try {
                $count = $importService->importFile($localPath);

                $this->info("Import terminé : {$count} enregistrements traités.");
            } catch (\Throwable $e) {
                $this->error("Erreur import {$filename} : " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
