<?php

namespace App\Http\Controllers;

use App\Enums\CvStatus;
use App\Http\Requests\RefineTailoringRequest;
use App\Http\Requests\SaveFinalCvRequest;
use App\Http\Requests\StoreJobDescriptionRequest;
use App\Models\Certification;
use App\Models\GeneratedCv;
use App\Models\Profile;
use App\Models\Experience;
use App\Models\Education;
use App\Models\Skill;
use App\Models\Project;
use App\Services\ATSScoringEngine;
use App\Services\DocxGeneratorService;
use App\Services\TailoringEngine;
use App\Services\MasterProfileFormatter;
use App\Services\TextExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TailoringController extends Controller
{
    public function __construct(
        protected TailoringEngine $tailoringEngine,
        protected ATSScoringEngine $atsScoringEngine,
        protected MasterProfileFormatter $formatter,
        protected TextExtractor $extractor,
        protected DocxGeneratorService $docxGenerator
    ) {}

    /**
     * Display the form to paste a new Job Description.
     */
    public function create(): View
    {
        return view('tailoring.create');
    }

    /**
     * Store a newly created Job Description and prepare for AI Tailoring.
     */
    public function store(StoreJobDescriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $action = $validated['action'] ?? 'tailor';

        $outputLanguage = $validated['output_language'] ?? 'id';
        if ($outputLanguage === 'auto') {
            $outputLanguage = \App\Support\CvLocale::detectLanguage($validated['job_description']);
        }

        $generatedCv = GeneratedCv::create([
            'job_description' => $validated['job_description'],
            'target_position' => $validated['target_position'],
            'is_instant_check' => ($action === 'instant_check'),
            'output_language' => $outputLanguage,
            'ai_model' => $validated['ai_model'],
            'status' => CvStatus::Pending,
            'tailored_cv_raw' => null,
            'tailored_cv_final' => null,
            'ats_score' => null,
        ]);

        return redirect()->route('tailoring.review', $generatedCv);
    }

    /**
     * Run the AI Tailoring Engine for the specified GeneratedCv.
     */
    public function generate(GeneratedCv $generatedCv): RedirectResponse
    {
        $this->tailoringEngine->generate($generatedCv);

        return redirect()->route('tailoring.review', $generatedCv);
    }

    /**
     * Re-run AI tailoring with an extra user instruction ("prompt tambahan").
     *
     * The instruction is stored on the session so any later re-generate keeps it,
     * then the engine re-tailors the wording (summary, bullets, suggestions) while
     * still obeying the no-fabrication hard constraints.
     */
    public function refine(RefineTailoringRequest $request, GeneratedCv $generatedCv): RedirectResponse
    {
        // Instant check sessions have no tailoring payload to re-word.
        if ($generatedCv->is_instant_check || !empty($generatedCv->raw_cv_text)) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Prompt tambahan hanya tersedia untuk sesi AI Tailoring. Mulai AI Tailoring posisi ini terlebih dahulu.');
        }

        if (in_array($generatedCv->status->value, ['tailoring', 'scoring'], true)) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'AI masih memproses sesi ini. Tunggu sampai selesai sebelum menambah prompt baru.');
        }

        $clearing = $request->boolean('clear');

        $generatedCv->custom_instructions = $clearing
            ? null
            : trim((string) $request->input('custom_instructions'));

        // User edits on the previous result are no longer valid for the new wording,
        // and a stale ATS score must not be shown for freshly tailored content.
        $generatedCv->tailored_cv_final = null;
        $generatedCv->ats_score = null;
        $generatedCv->save();

        $this->tailoringEngine->generate($generatedCv);

        $message = $clearing
            ? 'AI telah men-tailor ulang CV Anda tanpa prompt tambahan.'
            : 'AI telah men-tailor ulang CV Anda dengan prompt tambahan. Periksa kembali hasilnya sebelum menyimpan versi final.';

        return redirect()->route('tailoring.review', $generatedCv)->with('success', $message);
    }

    /**
     * Review tailored CV output and execution status.
     */
    public function review(GeneratedCv $generatedCv): View
    {
        // Use tailored_cv_final if user saved edits, otherwise fallback to tailored_cv_raw
        $tailoredData = $generatedCv->tailored_cv_final['tailored_cv']
            ?? $generatedCv->tailored_cv_raw['tailored_cv']
            ?? null;

        $errorData = $generatedCv->tailored_cv_raw['error'] ?? null;
        $atsScore = $generatedCv->ats_score;
        $atsError = is_array($atsScore) && isset($atsScore['error']) ? $atsScore : null;

        // If instant check, also pass master profile formatted details
        $masterProfile = null;
        if ($generatedCv->is_instant_check) {
            $masterProfile = $this->formatter->toScoringPayloadFromMaster()['tailored_cv_data'];
        }

        // Master Profile projects feed the "reselect project" picker on the review form.
        $masterProjects = $generatedCv->is_instant_check
            ? collect()
            : Project::with('bulletPoints')->orderBy('id')->get();

        return view('tailoring.review', compact('generatedCv', 'tailoredData', 'errorData', 'atsScore', 'atsError', 'masterProfile', 'masterProjects'));
    }

    /**
     * Remove the project cards the user flagged with `projects.*.remove`.
     */
    protected function filterRemovedProjects(array $submittedProjects): array
    {
        $projects = [];

        foreach ($submittedProjects as $project) {
            if (!empty($project['remove'])) {
                continue;
            }

            unset($project['remove']);
            $projects[] = $project;
        }

        return $projects;
    }

    /**
     * Master Profile project payloads (with source ids) for the given project ids.
     */
    protected function masterProjectPayloads(array $projectIds): array
    {
        $wantedIds = array_map('strval', array_filter(array_map('intval', $projectIds)));

        if ($wantedIds === []) {
            return [];
        }

        return collect($this->formatter->toTailoringPayload()['master_profile']['projects'] ?? [])
            ->filter(fn ($project) => in_array((string) ($project['id'] ?? ''), $wantedIds, true))
            ->values()
            ->all();
    }

    /**
     * Last-resort mapping when the AI cannot tailor the projects: paste Master Profile wording as-is.
     */
    protected function verbatimProjects(array $masterProjects): array
    {
        return array_map(fn ($project) => [
            'source_id' => (string) ($project['id'] ?? ''),
            'name' => (string) ($project['name'] ?? ''),
            'tech_stack' => array_values((array) ($project['tech_stack'] ?? [])),
            'bullet_points' => array_map(fn ($bullet) => [
                'source_bullet_id' => (string) ($bullet['id'] ?? ''),
                'content' => (string) ($bullet['content'] ?? ''),
            ], $project['bullet_points'] ?? []),
        ], $masterProjects);
    }

    /**
     * Save final user edits into tailored_cv_final.
     */
    public function finalize(SaveFinalCvRequest $request, GeneratedCv $generatedCv): RedirectResponse
    {
        $validatedData = $request->validated();

        if (!empty($validatedData['headline'])) {
            $generatedCv->target_position = $validatedData['headline'];
        }

        // 1. Drop the projects the user flagged for removal.
        $projects = $this->filterRemovedProjects($validatedData['projects'] ?? []);
        unset($validatedData['add_project_ids']);

        // 2. Projects picked in the "reselect from Master Profile" panel are re-tailored by AI
        //    (translated + aligned with the JD) instead of being pasted verbatim.
        $selectedIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('add_project_ids', [])))));
        $addedProjects = [];
        $tailoringFailed = false;

        if ($selectedIds !== []) {
            $existingSourceIds = array_map('strval', array_filter(array_column($projects, 'source_id')));

            $projectsToAdd = array_values(array_filter(
                $this->masterProjectPayloads($selectedIds),
                fn ($project) => !in_array((string) ($project['id'] ?? ''), $existingSourceIds, true)
            ));

            if ($projectsToAdd !== []) {
                $addedProjects = $this->tailoringEngine->tailorProjects($generatedCv, $projectsToAdd);

                if ($addedProjects === []) {
                    $tailoringFailed = true;
                    $addedProjects = $this->verbatimProjects($projectsToAdd);
                }
            }
        }

        $validatedData['projects'] = array_values(array_merge($projects, $addedProjects));

        $generatedCv->tailored_cv_final = [
            'tailored_cv' => $validatedData,
        ];

        // Status remains in Review (or current status), NOT changed to Completed prematurely
        $generatedCv->save();

        // 3. Re-run the deterministic QA checks against the content the user just saved.
        $this->tailoringEngine->refreshWarnings($generatedCv);

        $message = 'Versi final CV berhasil disimpan! Anda sekarang dapat mengunduh PDF.';

        if ($tailoringFailed) {
            $message .= ' Catatan: AI gagal men-tailor proyek baru, jadi teks Master Profile dipakai apa adanya — klik "Tailor Ulang Key Projects".';
        } elseif (!empty($addedProjects)) {
            $message .= ' Proyek baru otomatis di-tailor AI sesuai bahasa output & JD.';
        }

        return redirect()->route('tailoring.review', $generatedCv)->with('success', $message);
    }

    /**
     * Re-tailor every Key Project of this session with AI (translate + align with the JD).
     *
     * Needed because projects added before this feature was introduced may still hold
     * verbatim Master Profile wording (e.g. Indonesian text inside an English CV).
     */
    public function retailorProjects(GeneratedCv $generatedCv): RedirectResponse
    {
        if ($generatedCv->is_instant_check || !empty($generatedCv->raw_cv_text)) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Tailor ulang proyek hanya tersedia untuk sesi AI Tailoring.');
        }

        if (in_array($generatedCv->status->value, ['tailoring', 'scoring'], true)) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'AI masih memproses sesi ini. Tunggu sampai selesai.');
        }

        $tailored = $generatedCv->tailored_cv_final['tailored_cv']
            ?? $generatedCv->tailored_cv_raw['tailored_cv']
            ?? [];

        if (empty($tailored['projects'])) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Belum ada proyek di CV ini. Pilih proyek dari Master Profile terlebih dahulu.');
        }

        if (!$this->tailoringEngine->retailorSessionProjects($generatedCv)) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'AI gagal men-tailor ulang proyek. Pastikan proyek masih terhubung ke Master Profile, lalu coba lagi.');
        }

        return redirect()->route('tailoring.review', $generatedCv)
            ->with('success', 'Key Projects berhasil di-tailor ulang sesuai bahasa output & Job Description.');
    }

    /**
     * Run the ATS Scoring Engine (Call 2) for the user-edited final CV.
     * Requires `tailored_cv_final` to be set (user must have saved their edits).
     */
    public function score(GeneratedCv $generatedCv): RedirectResponse
    {
        // Guard: user must have saved a final version before scoring, EXCEPT for instant check
        if (!$generatedCv->is_instant_check && (empty($generatedCv->tailored_cv_final) || empty($generatedCv->tailored_cv_final['tailored_cv']))) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Simpan hasil final dulu sebelum cek skor ATS.');
        }

        $this->atsScoringEngine->score($generatedCv);

        return redirect()->route('tailoring.review', $generatedCv)
            ->with('success', 'Skor ATS berhasil dihitung.');
    }

    /**
     * Convert an instant check session into a full tailoring session.
     */
    public function promoteToTailoring(GeneratedCv $generatedCv): RedirectResponse
    {
        // 1. Promote to normal tailoring mode
        $generatedCv->is_instant_check = false;
        $generatedCv->status = CvStatus::Pending;
        $generatedCv->tailored_cv_raw = null;
        $generatedCv->tailored_cv_final = null;
        $generatedCv->ats_score = null;
        $generatedCv->save();

        // 2. Trigger tailoring generation
        $this->tailoringEngine->generate($generatedCv);

        return redirect()->route('tailoring.review', $generatedCv)
            ->with('success', 'Berhasil beralih ke mode tailoring! AI telah menyesuaikan CV Anda.');
    }

    /**
     * Show the direct PDF checker input form.
     */
    public function createPdfChecker(): View
    {
        return view('tailoring.pdf-create');
    }

    /**
     * Show the Master Profile ATS checker input form.
     */
    public function createMasterCheck(): View
    {
        return view('tailoring.master-create');
    }

    /**
     * Store the uploaded PDF, extract text, and redirect to review.
     */
    public function storePdfChecker(Request $request): RedirectResponse
    {
        $request->validate([
            'cv_file' => ['required', 'file', 'mimes:pdf,docx', 'max:5120'],
            'target_position' => ['required', 'string', 'max:255'],
            'job_description' => ['required', 'string', 'min:100'],
            'output_language' => ['required', 'string', 'in:id,en'],
            'ai_model' => ['required', 'string', 'in:' . implode(',', array_keys(GeneratedCv::AI_MODELS))],
        ], [
            'cv_file.required' => 'File CV wajib diunggah.',
            'cv_file.mimes' => 'Format file harus PDF atau DOCX.',
            'cv_file.max' => 'Ukuran file maksimal adalah 5MB.',
            'target_position.required' => 'Posisi yang dilamar wajib diisi.',
            'job_description.required' => 'Job Description wajib diisi.',
            'job_description.min' => 'Job Description minimal 100 karakter.',
            'output_language.required' => 'Bahasa output wajib dipilih.',
            'output_language.in' => 'Bahasa output yang dipilih tidak valid.',
            'ai_model.required' => 'Model AI wajib dipilih.',
            'ai_model.in' => 'Model AI yang dipilih tidak valid.',
        ]);

        $file = $request->file('cv_file');
        $extractedText = $this->extractor->extract($file->path(), $file->getClientOriginalExtension());

        if (trim($extractedText) === '') {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal mengekstrak teks dari file CV yang diunggah. Pastikan file tidak terenkripsi atau kosong.');
        }

        $generatedCv = GeneratedCv::create([
            'job_description' => $request->input('job_description'),
            'target_position' => $request->input('target_position'),
            'raw_cv_text' => $extractedText,
            'is_instant_check' => true,
            'output_language' => $request->input('output_language'),
            'ai_model' => $request->input('ai_model'),
            'status' => CvStatus::Pending,
            'tailored_cv_raw' => null,
            'tailored_cv_final' => null,
            'ats_score' => null,
        ]);

        return redirect()->route('tailoring.review', $generatedCv)
            ->with('success', 'CV berhasil diunggah dan teks berhasil diekstrak! Anda sekarang dapat memulai analisis ATS.');
    }

    /**
     * Resolve the tailored CV dataset (either tailored final or instant master data).
     */
    protected function resolveTailoredCvData(GeneratedCv $generatedCv, ?Profile $profile): array
    {
        if ($generatedCv->is_instant_check) {
            $experiences = Experience::with('bulletPoints')->get();
            $education = Education::all();
            $skills = Skill::all();
            $projects = Project::with('bulletPoints')->get();

            $skillsHighlighted = [];
            $skillsByCategory = [];
            foreach ($skills as $sk) {
                $skillsByCategory[$sk->category][] = $sk->name;
            }
            foreach ($skillsByCategory as $cat => $skNames) {
                $skillsHighlighted[] = [
                    'category' => $cat,
                    'skills' => $skNames,
                ];
            }

            return [
                'summary' => $profile->summary ?? '',
                'experiences' => $experiences->map(function ($exp) {
                    return [
                        'start_date' => $exp->start_date ? $exp->start_date->format('Y-m-d') : '',
                        'end_date' => $exp->end_date ? $exp->end_date->format('Y-m-d') : 'Present',
                        'position' => $exp->position,
                        'company' => $exp->company,
                        'bullet_points' => $exp->bulletPoints->map(fn($b) => ['content' => $b->content])->all(),
                    ];
                })->all(),
                'education' => $education->map(function ($edu) {
                    return [
                        'start_date' => $edu->start_date ? $edu->start_date->format('Y-m-d') : '',
                        'end_date' => $edu->end_date ? $edu->end_date->format('Y-m-d') : 'Present',
                        'degree' => $edu->degree,
                        'institution' => $edu->institution,
                    ];
                })->all(),
                'skills_highlighted' => $skillsHighlighted,
                'projects' => $projects->map(function ($proj) {
                    return [
                        'name' => $proj->name,
                        'tech_stack' => $proj->tech_stack,
                        'bullet_points' => $proj->bulletPoints->map(fn($b) => ['content' => $b->content])->all(),
                    ];
                })->all(),
            ];
        }

        return $generatedCv->tailored_cv_final['tailored_cv'] ?? [];
    }

    /**
     * Download the final tailored CV as an ATS-friendly PDF.
     */
    public function downloadPdf(GeneratedCv $generatedCv): Response|RedirectResponse
    {
        // Guard check for raw pdf text check
        if (!empty($generatedCv->raw_cv_text)) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Fitur download PDF dinonaktifkan untuk Cek PDF Instan karena Anda sudah memiliki dokumen aslinya.');
        }

        // Guard check: tailored_cv_final must not be null, EXCEPT for instant check
        if (!$generatedCv->is_instant_check && (empty($generatedCv->tailored_cv_final) || empty($generatedCv->tailored_cv_final['tailored_cv']))) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Selesaikan review & simpan dulu sebelum download PDF.');
        }

        $profile = Profile::first();
        $certifications = Certification::all();
        $tailoredCv = $this->resolveTailoredCvData($generatedCv, $profile);
        $lang = $generatedCv->output_language ?? 'id';
        $targetPosition = $generatedCv->target_position ?: ($tailoredCv['headline'] ?? '');

        $pdf = Pdf::loadView('pdf.cv-template', compact('profile', 'certifications', 'tailoredCv', 'lang', 'targetPosition', 'generatedCv'));
        $pdf->setPaper('a4', 'portrait');

        $candidateName = $profile->name ?? 'Candidate';
        $sanitizedName = Str::studly($candidateName);
        $dateStr = now()->format('Y-m-d');
        $fileName = "CV_{$sanitizedName}_{$dateStr}.pdf";

        return $pdf->download($fileName);
    }

    /**
     * Download the final tailored CV as an ATS-friendly DOCX document.
     */
    public function downloadDocx(GeneratedCv $generatedCv): BinaryFileResponse|RedirectResponse
    {
        // Guard check for raw pdf text check
        if (!empty($generatedCv->raw_cv_text)) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Fitur download DOCX dinonaktifkan untuk Cek PDF Instan karena Anda sudah memiliki dokumen aslinya.');
        }

        // Guard check: tailored_cv_final must not be null, EXCEPT for instant check
        if (!$generatedCv->is_instant_check && (empty($generatedCv->tailored_cv_final) || empty($generatedCv->tailored_cv_final['tailored_cv']))) {
            return redirect()->route('tailoring.review', $generatedCv)
                ->with('error', 'Selesaikan review & simpan dulu sebelum download DOCX.');
        }

        $profile = Profile::first();
        $certifications = Certification::all();
        $tailoredCv = $this->resolveTailoredCvData($generatedCv, $profile);
        $lang = $generatedCv->output_language ?? 'id';
        $targetPosition = $generatedCv->target_position ?: ($tailoredCv['headline'] ?? '');

        $tempDocx = $this->docxGenerator->generate($profile, $certifications, $tailoredCv, $lang, $targetPosition);

        $candidateName = $profile->name ?? 'Candidate';
        $sanitizedName = Str::studly($candidateName);
        $dateStr = now()->format('Y-m-d');
        $fileName = "CV_{$sanitizedName}_{$dateStr}.docx";

        return response()->download($tempDocx, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
