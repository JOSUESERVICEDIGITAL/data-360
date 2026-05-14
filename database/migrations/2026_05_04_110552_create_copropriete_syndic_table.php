<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('copropriete_syndic', function (Blueprint $table) {
            $table->id();

            $table->foreignId('copropriete_id')
                ->constrained('coproprietes')
                ->cascadeOnDelete();

            $table->foreignId('syndic_id')
                ->constrained('syndics')
                ->cascadeOnDelete();

            $table->string('role')->nullable(); // syndic principal, ancien syndic...
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            $table->timestamps();

            $table->unique(['copropriete_id', 'syndic_id'], 'copro_syndic_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copropriete_syndic');
    }
};