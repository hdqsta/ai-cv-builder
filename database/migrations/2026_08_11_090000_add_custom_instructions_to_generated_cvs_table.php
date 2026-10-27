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
            if (!Schema::hasColumn('generated_cvs', 'custom_instructions')) {
                $table->text('custom_instructions')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_cvs', function (Blueprint $table) {
            $table->dropColumn(['custom_instructions']);
        });
    }
};
