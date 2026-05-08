<?php

namespace App\Console\Commands;

use App\Services\Import\InpiRneImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncInpiRne extends Command
{
    protected $signature = 'inpi:sync {--limit=3 : Nombre max de fichiers à télécharger}';
    protected $description = 'Synchronise les fichiers RNE depuis le SFTP INPI et importe les entreprises en local';

    public function handle(InpiRneImportService $importService): int
    {
        $this->info('Connexion au SFTP INPI...');

        $disk = Storage::disk('inpi_sftp');

        $files = collect($disk->files('/'))
            ->filter(function ($file) {
                $lower = strtolower($file);

                return str_ends_with($lower, '.zip')
                    || str_ends_with($lower, '.json')
                    || str_ends_with($lower, '.ndjson');
            })
            ->sortDesc()
            ->take((int) $this->option('limit'))
            ->values();

        if ($files->isEmpty()) {
            $this->warn('Aucun fichier ZIP/JSON/NDJSON trouvé sur le SFTP.');
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
            } else {
                $this->line("Déjà téléchargé : {$filename}");
            }

            $this->info("Import : {$filename}");

            $count = $importService->importFile($localPath);

            $this->info("Import terminé : {$count} enregistrements traités.");
        }

        return self::SUCCESS;
    }
}