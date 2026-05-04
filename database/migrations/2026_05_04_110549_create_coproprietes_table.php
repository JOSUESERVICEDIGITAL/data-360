<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coproprietes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('adresse_id')
                ->nullable()
                ->constrained('adresses')
                ->nullOnDelete();

            $table->foreignId('batiment_id')
                ->nullable()
                ->constrained('batiments')
                ->nullOnDelete();

            $table->string('numero_immatriculation')->nullable()->index();
            $table->string('nom_copropriete')->nullable();
            $table->string('siren_copropriete', 20)->nullable()->index();

            $table->integer('nombre_lots_total')->nullable();
            $table->integer('nombre_lots_habitation')->nullable();
            $table->integer('nombre_batiments')->nullable();

            $table->string('statut')->nullable();
            $table->string('date_immatriculation')->nullable();

            $table->json('raw_data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coproprietes');
    }
};