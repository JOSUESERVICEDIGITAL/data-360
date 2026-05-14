<?php
// database/migrations/2026_05_12_000000_create_notifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // admin, info, warning, success, danger
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_global')->default(false); // notification pour tous
            $table->json('meta')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['is_global', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};