<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coproprietes', function (Blueprint $table) {
            if (!Schema::hasColumn('coproprietes', 'representant_legal_nom')) {
                $table->string('representant_legal_nom')->nullable()->after('siren_copropriete');
            }

            if (!Schema::hasColumn('coproprietes', 'representant_legal_type')) {
                $table->string('representant_legal_type')->nullable()->after('representant_legal_nom');
            }

            if (!Schema::hasColumn('coproprietes', 'representant_legal_connu')) {
                $table->boolean('representant_legal_connu')->default(false)->after('representant_legal_type');
            }

            if (!Schema::hasColumn('coproprietes', 'message_representant')) {
                $table->string('message_representant')->nullable()->after('representant_legal_connu');
            }

            if (!Schema::hasColumn('coproprietes', 'nombre_adresses_associees')) {
                $table->integer('nombre_adresses_associees')->nullable()->after('nombre_batiments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coproprietes', function (Blueprint $table) {
            $columns = [
                'representant_legal_nom',
                'representant_legal_type',
                'representant_legal_connu',
                'message_representant',
                'nombre_adresses_associees',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('coproprietes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};