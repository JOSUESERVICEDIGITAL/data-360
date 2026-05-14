<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qpv_zones', function (Blueprint $table) {
            $table->id();

            $table->string('type')->index(); // qp_2024, qp_2015, zfu
            $table->string('code')->nullable()->index();
            $table->string('nom')->nullable();

            $table->longText('geojson')->nullable();

            $table->timestamps();

            $table->index(['type', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qpv_zones');
    }
};
