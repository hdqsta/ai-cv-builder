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
        Schema::table('generated_cvs', function (Blueprint $table) {
            if (!Schema::hasColumn('generated_cvs', 'output_language')) {
                $table->string('output_language', 10)->default('id')->after('is_instant_check');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_cvs', function (Blueprint $table) {
            $table->dropColumn(['output_language']);
        });
    }
};
