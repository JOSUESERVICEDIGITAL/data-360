<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('syndics', function (Blueprint $table) {
            $table->id();

            $table->string('nom')->nullable();
            $table->string('siren', 20)->nullable()->index();
            $table->string('siret', 20)->nullable()->index();

            $table->string('forme_juridique')->nullable();
            $table->string('activite')->nullable();

            $table->string('adresse_complete')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('ville')->nullable();

            $table->string('telephone')->nullable();
            $table->string('email')->nullable();

            $table->json('raw_data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syndics');
    }
};