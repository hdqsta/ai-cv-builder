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
            $table->string('target_position')->nullable()->after('job_description');
            $table->text('raw_cv_text')->nullable()->after('target_position');
            $table->boolean('is_instant_check')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_cvs', function (Blueprint $table) {
            $table->dropColumn(['target_position', 'raw_cv_text', 'is_instant_check']);
        });
    }
};
