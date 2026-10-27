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
            if (!Schema::hasColumn('generated_cvs', 'raw_cv_text')) {
                $table->text('raw_cv_text')->nullable()->after('target_position');
            }
            if (!Schema::hasColumn('generated_cvs', 'target_position')) {
                $table->string('target_position')->nullable()->after('job_description');
            }
            if (!Schema::hasColumn('generated_cvs', 'is_instant_check')) {
                $table->boolean('is_instant_check')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_cvs', function (Blueprint $table) {
            $table->dropColumn(['raw_cv_text']);
        });
    }
};
