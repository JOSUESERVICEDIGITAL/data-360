<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('csv_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename_original');
            $table->string('filename_result')->nullable();
            $table->enum('statut', ['en_attente', 'en_cours', 'termine', 'erreur'])->default('en_attente');
            $table->integer('total_lignes')->default(0);
            $table->integer('lignes_traitees')->default(0);
            $table->text('erreur_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_imports');
    }
};
