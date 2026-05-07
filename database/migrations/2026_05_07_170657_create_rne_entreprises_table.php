<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rne_entreprises', function (Blueprint $table) {
            $table->id();

            $table->string('siren', 9)->unique();
            $table->string('siret_siege', 14)->nullable()->index();

            $table->string('denomination')->nullable()->index();
            $table->string('forme_juridique')->nullable();

            $table->string('capital_social')->nullable();
            $table->decimal('capital_social_numeric', 15, 2)->nullable();

            $table->string('activite')->nullable();
            $table->date('date_creation')->nullable();

            $table->string('adresse_complete')->nullable();
            $table->string('code_postal')->nullable()->index();
            $table->string('ville')->nullable()->index();

            $table->json('dirigeants')->nullable();
            $table->json('etablissements')->nullable();
            $table->json('raw_data')->nullable();

            $table->timestamps();

            $table->index(['denomination', 'siren']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rne_entreprises');
    }
};