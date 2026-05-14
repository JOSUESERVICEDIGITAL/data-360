<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adresses', function (Blueprint $table) {
            $table->id();

            $table->string('adresse_complete');
            $table->string('numero')->nullable();
            $table->string('voie')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('ville')->nullable();
            $table->string('code_insee', 10)->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('source')->nullable(); // BAN, API Adresse, manuel...
            $table->json('raw_data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adresses');
    }
};