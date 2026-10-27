<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CertificationController;
use App\Http\Controllers\CvUploadController;
use App\Http\Controllers\TailoringController;
use App\Http\Controllers\DocsQaController;
use App\Http\Controllers\AgentController;

Route::get('/', function () {
    $profile = \App\Models\Profile::first();
    $stats = [
        'profile_filled' => (bool) $profile,
        'experiences_count' => \App\Models\Experience::count(),
        'projects_count' => \App\Models\Project::count(),
        'education_count' => \App\Models\Education::count(),
        'skills_count' => \App\Models\Skill::count(),
        'certifications_count' => \App\Models\Certification::count(),
    ];
    return view('welcome', compact('profile', 'stats'));
})->name('home');

// Profile Route (Single-record)
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

// Resources
Route::resource('experiences', ExperienceController::class);
Route::resource('education', EducationController::class);
Route::resource('skills', SkillController::class)->only(['index', 'store', 'destroy']);
Route::resource('projects', ProjectController::class);
Route::resource('certifications', CertificationController::class);

// CV Upload & AI-Parsing routes
Route::get('/upload', [CvUploadController::class, 'showUploadForm'])->name('cv.upload.form');
Route::post('/upload', [CvUploadController::class, 'upload'])->name('cv.upload');
Route::get('/review', [CvUploadController::class, 'showReviewForm'])->name('cv.review');
Route::post('/review/save', [CvUploadController::class, 'save'])->name('cv.review.save');

// Epic 2 & 3 & 4 & 5: Job Description, AI Tailoring, ATS Scoring, Finalize & PDF Generation
Route::get('/pdf-checker', [TailoringController::class, 'createPdfChecker'])->name('pdf-checker.create');
Route::post('/pdf-checker', [TailoringController::class, 'storePdfChecker'])->name('pdf-checker.store');
Route::get('/master-check', [TailoringController::class, 'createMasterCheck'])->name('master-check.create');
Route::get('/tailoring/create', [TailoringController::class, 'create'])->name('tailoring.create');
Route::post('/tailoring', [TailoringController::class, 'store'])->name('tailoring.store');
Route::get('/tailoring/{generatedCv}/review', [TailoringController::class, 'review'])->name('tailoring.review');
Route::post('/tailoring/{generatedCv}/generate', [TailoringController::class, 'generate'])->name('tailoring.generate');
Route::post('/tailoring/{generatedCv}/refine', [TailoringController::class, 'refine'])->name('tailoring.refine');
Route::post('/tailoring/{generatedCv}/projects/retailor', [TailoringController::class, 'retailorProjects'])->name('tailoring.projects.retailor');
Route::post('/tailoring/{generatedCv}/score', [TailoringController::class, 'score'])->name('tailoring.score');
Route::post('/tailoring/{generatedCv}/promote', [TailoringController::class, 'promoteToTailoring'])->name('tailoring.promote');
Route::post('/tailoring/{generatedCv}/finalize', [TailoringController::class, 'finalize'])->name('tailoring.finalize');
Route::get('/tailoring/{generatedCv}/pdf', [TailoringController::class, 'downloadPdf'])->name('tailoring.pdf');
Route::get('/tailoring/{generatedCv}/docx', [TailoringController::class, 'downloadDocx'])->name('tailoring.docx');

// Internal Dev Tool: Mini-RAG Docs QA Chatbot
Route::get('/docs-qa', [DocsQaController::class, 'index'])->name('docs-qa.index');
Route::post('/docs-qa', [DocsQaController::class, 'ask'])->name('docs-qa.ask');

// Internal Dev Tool: CV Assistant Agent (Function Calling / Tool-Calling)
Route::get('/agent-qa', [AgentController::class, 'index'])->name('agent-qa.index');
Route::post('/agent-qa', [AgentController::class, 'ask'])->name('agent-qa.ask');
