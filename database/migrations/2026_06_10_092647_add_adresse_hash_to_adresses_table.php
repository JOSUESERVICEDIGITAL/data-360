<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adresses', function (Blueprint $table) {
            // Colonne hash — char(32) fixe = plus rapide que varchar pour index
            $table->char('adresse_hash', 32)
                ->nullable()
                ->after('adresse_complete')
                ->comment('md5(strtolower(trim(adresse_complete))) — recherche O(1)');

            // Index unique sur le hash (recherche directe)
            $table->unique('adresse_hash', 'idx_adresses_hash');
        });

        // Remplir le hash pour les adresses déjà en base
        DB::statement("
            UPDATE adresses
            SET adresse_hash = MD5(LOWER(TRIM(adresse_complete)))
            WHERE adresse_hash IS NULL
              AND adresse_complete IS NOT NULL
              AND adresse_complete != ''
        ");
    }

    public function down(): void
    {
        Schema::table('adresses', function (Blueprint $table) {
            $table->dropUnique('idx_adresses_hash');
            $table->dropColumn('adresse_hash');
        });
    }
};