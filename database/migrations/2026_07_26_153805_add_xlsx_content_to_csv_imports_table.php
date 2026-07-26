<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            if (!Schema::hasColumn('csv_imports', 'xlsx_content')) {
                $table->longText('xlsx_content')->nullable()->after('filename_result');
            }
        });
    }

    public function down(): void
    {
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->dropColumn('xlsx_content');
        });
    }
};
