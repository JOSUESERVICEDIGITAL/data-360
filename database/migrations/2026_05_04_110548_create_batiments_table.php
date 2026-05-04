<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batiments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('adresse_id')
                ->nullable()
                ->constrained('adresses')
                ->nullOnDelete();

            $table->string('identifiant_bdnb')->nullable()->index();
            $table->string('identifiant_cadastre')->nullable()->index();

            $table->enum('type_batiment', [
                'individuel',
                'collectif',
                'tertiaire',
                'mixte',
                'inconnu'
            ])->default('inconnu');

            $table->integer('annee_construction')->nullable();
            $table->integer('nombre_logements')->nullable();
            $table->integer('nombre_niveaux')->nullable();
            $table->decimal('hauteur', 8, 2)->nullable();
            $table->decimal('surface_habitable', 12, 2)->nullable();
            $table->decimal('surface_emprise_sol', 12, 2)->nullable();

            $table->string('classe_dpe')->nullable();
            $table->string('ges')->nullable();
            $table->string('type_chauffage')->nullable();
            $table->string('energie_chauffage')->nullable();

            $table->decimal('score_opportunite', 5, 2)->nullable();

            $table->json('raw_data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batiments');
    }
};