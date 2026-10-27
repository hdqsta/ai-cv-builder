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
            if (!Schema::hasColumn('generated_cvs', 'ai_model')) {
                $table->string('ai_model')->nullable()->after('output_language');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_cvs', function (Blueprint $table) {
            $table->dropColumn(['ai_model']);
        });
    }
};
