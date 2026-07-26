<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_csvs', function (Blueprint $table) {

            $table->string('nom_fichier')->nullable()->after('id');

            $table->string('statut')
                ->default('termine')
                ->after('nom_fichier');

            $table->integer('total_lignes')
                ->default(0)
                ->after('statut');

            $table->integer('lignes_traitees')
                ->default(0)
                ->after('total_lignes');

            $table->string('filename_result')
                ->nullable()
                ->after('lignes_traitees');

            $table->text('erreur_message')
                ->nullable()
                ->after('filename_result');

        });
    }

    public function down(): void
    {
        Schema::table('import_csvs', function (Blueprint $table) {

            $table->dropColumn([
                'nom_fichier',
                'statut',
                'total_lignes',
                'lignes_traitees',
                'filename_result',
                'erreur_message',
            ]);

        });
    }
};
