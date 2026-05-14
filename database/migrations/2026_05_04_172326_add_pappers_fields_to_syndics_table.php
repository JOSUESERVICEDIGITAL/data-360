<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syndics', function (Blueprint $table) {
            $table->string('capital_social')->nullable()->after('activite');
            $table->string('chiffre_affaires')->nullable()->after('capital_social');
            $table->string('resultat')->nullable()->after('chiffre_affaires');
            $table->string('effectif')->nullable()->after('resultat');
            $table->string('date_creation')->nullable()->after('effectif');
            $table->string('dirigeant_principal')->nullable()->after('date_creation');
            $table->string('url_pappers')->nullable()->after('dirigeant_principal');
        });
    }

    public function down(): void
    {
        Schema::table('syndics', function (Blueprint $table) {
            $table->dropColumn([
                'capital_social',
                'chiffre_affaires',
                'resultat',
                'effectif',
                'date_creation',
                'dirigeant_principal',
                'url_pappers',
            ]);
        });
    }
};