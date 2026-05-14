<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnic_coproprietes', function (Blueprint $table) {
            $table->id();

            // IDENTITÉ
            $table->string('numero_immatriculation')->nullable()->index();
            $table->string('nom_copropriete')->nullable();

            // ADRESSE
            $table->string('adresse_complete')->nullable()->index();
            $table->string('code_postal', 10)->nullable()->index();
            $table->string('ville')->nullable()->index();
            $table->string('code_insee', 10)->nullable()->index();

            // ENTREPRISE / COPRO
            $table->string('siren_copropriete', 20)->nullable()->index();

            // DONNÉES BÂTIMENT
            $table->integer('nombre_lots_total')->nullable();
            $table->integer('nombre_lots_habitation')->nullable();
            $table->integer('nombre_batiments')->nullable();
            $table->integer('nombre_adresses_associees')->nullable();

            // REPRÉSENTANT
            $table->boolean('representant_legal_connu')->default(false);
            $table->string('representant_legal_nom')->nullable();
            $table->string('representant_legal_type')->nullable();
            $table->string('message_representant')->nullable();

            // SYNDIC
            $table->string('syndic_nom')->nullable();
            $table->string('siren_syndic', 20)->nullable()->index();
            $table->string('siret_syndic', 20)->nullable()->index();

            // STATUT
            $table->string('statut')->nullable();
            $table->date('date_immatriculation')->nullable();

            // RAW
            $table->json('raw_data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnic_coproprietes');
    }
};