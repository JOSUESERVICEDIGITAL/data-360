<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visitor_device_id')->nullable()->constrained('visitor_devices')->nullOnDelete();

            $table->string('query');
            $table->string('normalized_address')->nullable();

            $table->string('ip_address', 64)->nullable()->index();
            $table->string('fingerprint_hash')->nullable()->index();

            $table->boolean('is_authenticated')->default(false);
            $table->boolean('is_free_search')->default(false);
            $table->boolean('credit_consumed')->default(false);

            $table->boolean('success')->default(false);
            $table->string('status')->default('pending'); 
            // pending, allowed, blocked, no_credit, vpn_blocked, suspicious

            $table->boolean('is_vpn')->default(false);
            $table->boolean('is_proxy')->default(false);
            $table->boolean('is_tor')->default(false);
            $table->boolean('is_datacenter')->default(false);

            $table->unsignedInteger('risk_score')->default(0);
            $table->string('block_reason')->nullable();

            $table->json('result_summary')->nullable();
            $table->json('raw_security_data')->nullable();

            $table->timestamps();

            $table->index(['ip_address', 'created_at']);
            $table->index(['fingerprint_hash', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_attempts');
    }
};