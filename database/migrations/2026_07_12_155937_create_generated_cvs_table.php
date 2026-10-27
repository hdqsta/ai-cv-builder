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
        Schema::create('generated_cvs', function (Blueprint $table) {
            $table->id();
            $table->text('job_description');
            $table->json('tailored_cv_raw')->nullable();
            $table->json('tailored_cv_final')->nullable();
            $table->json('ats_score')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_cvs');
    }
};
