<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();

            $table->string('api_name'); // BAN, BDNB, Cadastre, Sirene, RNIC...
            $table->string('endpoint')->nullable();
            $table->text('query')->nullable();

            $table->integer('status_code')->nullable();
            $table->boolean('success')->default(false);

            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};