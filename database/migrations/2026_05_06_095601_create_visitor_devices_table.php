<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('fingerprint_hash')->index();
            $table->string('ip_address', 64)->nullable()->index();
            $table->string('user_agent_hash')->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();

            $table->unsignedInteger('free_searches_used')->default(0);
            $table->timestamp('free_searches_reset_at')->nullable();

            $table->boolean('is_vpn')->default(false);
            $table->boolean('is_proxy')->default(false);
            $table->boolean('is_tor')->default(false);
            $table->boolean('is_datacenter')->default(false);

            $table->unsignedInteger('risk_score')->default(0);
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason')->nullable();

            $table->timestamp('last_seen_at')->nullable();

            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['fingerprint_hash', 'ip_address'], 'visitor_device_fingerprint_ip_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_devices');
    }
};