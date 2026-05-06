<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_identities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type');
            // ip, fingerprint, user, email_domain, phone, device

            $table->string('value')->index();

            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamp('expires_at')->nullable();

            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['type', 'value']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_identities');
    }
};