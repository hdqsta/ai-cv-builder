<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Services\TextExtractor;
use App\Services\GeminiParserService;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;
use App\Models\Experience;
use App\Models\Education;
use App\Models\Skill;
use App\Models\Project;
use App\Models\Certification;

class CvUploadController extends Controller
{
    protected TextExtractor $extractor;
    protected GeminiParserService $parser;

    public function __construct(TextExtractor $extractor, GeminiParserService $parser)
    {
        $this->extractor = $extractor;
        $this->parser = $parser;
    }

    /**
     * Display the CV upload form.
     */
    public function showUploadForm()
    {
        return view('cv.upload');
    }

    /**
     * Process CV upload, extract text, call Gemini parser, store to session.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'cv_file' => 'required|file|mimes:pdf,docx|max:5120', // max 5MB
        ]);

        $file = $request->file('cv_file');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();

        try {
            // 1. Extract raw text
            $rawText = $this->extractor->extract($filePath, $extension);
            
            if (empty(trim($rawText))) {
                return back()->withErrors(['cv_file' => 'Gagal mengekstrak teks dari file. Pastikan dokumen tidak terenkripsi atau kosong.']);
            }

            // 2. Parse using Gemini
            $parsedData = $this->parser->parse($rawText);

            // 3. Put into session
            session(['parsed_cv' => $parsedData]);

            return redirect()->route('cv.review')->with('success', 'CV berhasil diparse! Silakan review data sebelum disimpan.');

        } catch (Exception $e) {
            // Handle rate limits or connection issue friendly
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, '429') || str_contains($errorMessage, 'Rate Limit')) {
                $friendlyError = 'Layanan AI sedang sibuk, coba lagi beberapa saat.';
            } else {
                $friendlyError = 'Gagal memproses parsing CV dengan AI: ' . $errorMessage;
            }

            return back()->withErrors(['cv_file' => $friendlyError]);
        }
    }

    /**
     * Show the pre-filled review form.
     */
    public function showReviewForm()
    {
        if (!session()->has('parsed_cv')) {
            return redirect()->route('cv.upload.form')->withErrors(['cv_file' => 'Silakan upload CV terlebih dahulu.']);
        }

        $parsedCv = session('parsed_cv');

        return view('cv.review', compact('parsedCv'));
    }

    /**
     * Save the reviewed CV data into the Master Profile.
     */
    public function save(Request $request)
    {
        // Transform YYYY-MM from 'month' inputs to YYYY-MM-01 before validation
        $experiences = $request->input('experiences', []);
        if (is_array($experiences)) {
            foreach ($experiences as $key => $exp) {
                if (!empty($exp['start_date']) && strlen($exp['start_date']) === 7) {
                    $experiences[$key]['start_date'] = $exp['start_date'] . '-01';
                }
                if (!empty($exp['end_date']) && strlen($exp['end_date']) === 7) {
                    $experiences[$key]['end_date'] = $exp['end_date'] . '-01';
                }
            }
        }

        $education = $request->input('education', []);
        if (is_array($education)) {
            foreach ($education as $key => $edu) {
                if (!empty($edu['start_date']) && strlen($edu['start_date']) === 7) {
                    $education[$key]['start_date'] = $edu['start_date'] . '-01';
                }
                if (!empty($edu['end_date']) && strlen($edu['end_date']) === 7) {
                    $education[$key]['end_date'] = $edu['end_date'] . '-01';
                }
            }
        }

        $request->merge([
            'experiences' => $experiences,
            'education' => $education,
        ]);

        // Simple validation checks
        $request->validate([
            'profile.name' => 'required|string|max:255',
            'profile.email' => 'required|email|max:255',
            'experiences.*.company' => 'required_with:experiences.*.position|string|max:255',
            'experiences.*.position' => 'required_with:experiences.*.company|string|max:255',
            'experiences.*.start_date' => 'required_with:experiences.*.company|date|nullable',
            'experiences.*.end_date' => 'date|nullable',
            'education.*.institution' => 'required_with:education.*.degree|string|max:255',
            'education.*.degree' => 'required_with:education.*.institution|string|max:255',
            'education.*.start_date' => 'required_with:education.*.institution|date|nullable',
            'education.*.end_date' => 'date|nullable',
            'skills.*.name' => 'string|max:255',
            'projects.*.name' => 'string|max:255',
            'certifications.*.name' => 'string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // 1. Save Profile (single-record update)
                $profile = Profile::firstOrNew();
                $profile->fill([
                    'name' => $request->input('profile.name'),
                    'email' => $request->input('profile.email'),
                    'phone' => $request->input('profile.phone'),
                    'location' => $request->input('profile.location'),
                    'summary' => $request->input('profile.summary'),
                    'links' => array_values(array_filter(array_map('trim', $request->input('profile.links', [])))),
                ])->save();

                // 2. Save Experiences & bullets
                $experiences = $request->input('experiences', []);
                foreach ($experiences as $expData) {
                    if (empty($expData['company']) && empty($expData['position'])) {
                        continue;
                    }
                    $exp = Experience::create([
                        'company' => $expData['company'],
                        'position' => $expData['position'],
                        'start_date' => $expData['start_date'],
                        'end_date' => $expData['end_date'] ?: null,
                    ]);

                    $bullets = $expData['bullets'] ?? [];
                    $order = 0;
                    foreach ($bullets as $bulletData) {
                        if (empty($bulletData['content'])) {
                            continue;
                        }
                        $exp->bulletPoints()->create([
                            'content' => $bulletData['content'],
                            'order' => $order++,
                        ]);
                    }
                }

                // 3. Save Education
                $educations = $request->input('education', []);
                foreach ($educations as $eduData) {
                    if (empty($eduData['institution']) && empty($eduData['degree'])) {
                        continue;
                    }
                    Education::create([
                        'institution' => $eduData['institution'],
                        'degree' => $eduData['degree'],
                        'start_date' => $eduData['start_date'],
                        'end_date' => $eduData['end_date'] ?: null,
                        'notes' => $eduData['notes'] ?? null,
                    ]);
                }

                // 4. Save Skills (avoid duplicate names)
                $skills = $request->input('skills', []);
                foreach ($skills as $skillData) {
                    $skillName = trim($skillData['name'] ?? '');
                    if (empty($skillName)) {
                        continue;
                    }
                    Skill::firstOrCreate(
                        ['name' => $skillName],
                        ['category' => $skillData['category'] ?: 'Other']
                    );
                }

                // 5. Save Projects
                $projects = $request->input('projects', []);
                foreach ($projects as $projData) {
                    if (empty($projData['name'])) {
                        continue;
                    }
                    $techStack = [];
                    if (!empty($projData['tech_stack'])) {
                        $techStack = array_values(array_filter(array_map('trim', explode(',', $projData['tech_stack']))));
                    }
                    $proj = Project::create([
                        'name' => $projData['name'],
                        'link' => $projData['link'] ?? null,
                        'tech_stack' => $techStack,
                    ]);

                    $bullets = $projData['bullets'] ?? [];
                    $order = 0;
                    foreach ($bullets as $bulletData) {
                        if (empty($bulletData['content'])) {
                            continue;
                        }
                        $proj->bulletPoints()->create([
                            'content' => $bulletData['content'],
                            'order' => $order++,
                        ]);
                    }
                }

                // 6. Save Certifications
                $certs = $request->input('certifications', []);
                foreach ($certs as $certData) {
                    if (empty($certData['name'])) {
                        continue;
                    }
                    Certification::create([
                        'name' => $certData['name'],
                        'issuer' => $certData['issuer'],
                        'year' => $certData['year'],
                    ]);
                }
            });

            // Clear session state
            session()->forget('parsed_cv');

            return redirect()->route('home')->with('success', 'Master Profile berhasil di-update dari CV yang diparse!');

        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Gagal menyimpan data ke Master Profile: ' . $e->getMessage()])->withInput();
        }
    }
}
