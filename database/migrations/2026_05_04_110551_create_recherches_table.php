<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recherches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('adresse_id')
                ->nullable()
                ->constrained('adresses')
                ->nullOnDelete();

            $table->string('requete');
            $table->enum('statut', [
                'en_attente',
                'trouve',
                'partiel',
                'introuvable',
                'erreur'
            ])->default('en_attente');

            $table->text('message')->nullable();

            $table->json('resultat')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recherches');
    }
};