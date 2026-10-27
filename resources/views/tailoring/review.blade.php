@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-6 space-y-8">

    @php
        // ---- Stage detection (used by the failed-state retry button) ----
        $hasTailoredRaw = !empty($generatedCv->tailored_cv_raw) && !empty($generatedCv->tailored_cv_raw['tailored_cv']);
        $hasFinal = !empty($generatedCv->tailored_cv_final) && !empty($generatedCv->tailored_cv_final['tailored_cv']);
        $hasValidAtsScore = is_array($atsScore) && isset($atsScore['score']['match_percentage']);
        $isScoringStageFailed = $generatedCv->status->value === 'failed' && $hasFinal && !$hasValidAtsScore;
        $isTailoringStageFailed = $generatedCv->status->value === 'failed' && !$hasTailoredRaw;
    @endphp

    <!-- Top Header & Status Banner -->
    <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden ai-sparkle-bg">
        <div class="space-y-2">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-xs font-bold text-slate-400">CV Session #{{ $generatedCv->id }}</span>

                @if ($generatedCv->status->value === 'pending')
                    <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-semibold">
                        Status: Pending (Siap Generate)
                    </span>
                @elseif ($generatedCv->status->value === 'tailoring')
                    <span class="px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold flex items-center gap-1.5 animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
                        Status: Memproses AI Tailoring...
                    </span>
                @elseif ($generatedCv->status->value === 'scoring')
                    <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-semibold flex items-center gap-1.5 animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        Status: Menghitung Skor ATS...
                    </span>
                @elseif ($generatedCv->status->value === 'failed')
                    <span class="px-3 py-1 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-semibold">
                        Status: Gagal
                        @if ($isScoringStageFailed)
                            (Tahap ATS Scoring)
                        @else
                            (Error / Hard Constraint Failed)
                        @endif
                    </span>
                @elseif ($generatedCv->status->value === 'completed')
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Status: Selesai
                    </span>
                @else
                    <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Status: Review & Edit Versi Final
                    </span>
                @endif

                @if ($hasFinal)
                    <span class="px-3 py-1 rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/30 text-xs font-semibold">
                        ✓ Versi Final Tersimpan
                    </span>
                @endif

                @if ($hasValidAtsScore)
                    <span class="px-3 py-1 rounded-full bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/30 text-xs font-semibold">
                        ATS: {{ $atsScore['score']['match_percentage'] }}%
                    </span>
                @endif

                @if (!empty($generatedCv->custom_instructions))
                    <span class="px-3 py-1 rounded-full bg-violet-500/20 text-violet-300 border border-violet-500/30 text-xs font-semibold max-w-[280px] truncate"
                          title="{{ $generatedCv->custom_instructions }}">
                        ✎ Prompt tambahan aktif
                    </span>
                @endif
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">AI CV Tailoring Studio</h1>
            <p class="text-xs text-slate-400">Review dan edit hasil reframing AI. Simpan versi final untuk menghasilkan dokumen PDF siap kirim yang ATS-friendly, lalu cek skor kecocokan terhadap JD.</p>
        </div>

        <!-- Header Action Buttons -->
        <div class="flex items-center gap-3 flex-wrap">
            @if ($hasFinal || ($generatedCv->is_instant_check && empty($generatedCv->raw_cv_text) && $generatedCv->status->value === 'completed'))
                <a href="{{ route('tailoring.pdf', $generatedCv) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-950/40 border border-emerald-400/30 transition-all hover:scale-[1.02]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download PDF</span>
                </a>
                <a href="{{ route('tailoring.docx', $generatedCv) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-bold text-xs shadow-lg shadow-blue-950/40 border border-blue-400/30 transition-all hover:scale-[1.02]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download Word (.docx)</span>
                </a>
            @endif

            {{-- promote to tailoring button (only for instant check, completed state, NOT raw pdf upload) --}}
            @if ($generatedCv->is_instant_check && empty($generatedCv->raw_cv_text) && $generatedCv->status->value === 'completed')
                <form action="{{ route('tailoring.promote', $generatedCv) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-bold text-xs shadow-lg shadow-indigo-950/40 border border-indigo-400/30 transition-all hover:scale-[1.02]">
                        <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Mulai AI Tailoring Posisi Ini</span>
                    </button>
                </form>
            @endif

            {{-- Re-tailor Key Projects (translate + align with JD) for the saved version --}}
            @if (!$generatedCv->is_instant_check && !empty($tailoredData['projects']) && !in_array($generatedCv->status->value, ['scoring', 'tailoring']))
                <form action="{{ route('tailoring.projects.retailor', $generatedCv) }}" method="POST"
                      onsubmit="return confirm('AI akan men-tailor ulang SEMUA key project (terjemah + sesuaikan dengan JD). Editan yang belum disimpan akan hilang. Lanjutkan?');">
                    @csrf
                    <button type="submit" title="Terjemahkan & sesuaikan semua key project dengan bahasa CV dan Job Description"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-100 font-bold text-xs border border-slate-600/70 shadow-lg transition-all hover:scale-[1.02]">
                        <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Tailor Ulang Key Projects</span>
                    </button>
                </form>
            @endif

            {{-- Epic 4: ATS Score button (only meaningful when final version exists, or instant check) --}}
            @if ((($hasFinal && !$generatedCv->is_instant_check) || ($generatedCv->is_instant_check && $generatedCv->status->value === 'completed')) && !in_array($generatedCv->status->value, ['scoring', 'tailoring']))
                <form action="{{ route('tailoring.score', $generatedCv) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-500 hover:to-pink-500 text-white font-bold text-xs shadow-lg shadow-fuchsia-950/40 border border-fuchsia-400/30 transition-all hover:scale-[1.02]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>
                            @if ($hasValidAtsScore)
                                Hitung Ulang Skor ATS
                            @else
                                Cek Skor ATS
                            @endif
                        </span>
                    </button>
                </form>
            @endif

            @if (in_array($generatedCv->status->value, ['pending', 'failed']) && $isTailoringStageFailed)
                <form action="{{ route('tailoring.generate', $generatedCv) }}" method="POST" id="generate-form">
                    @csrf
                    <button type="submit" id="generate-btn" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-semibold text-xs shadow-xl shadow-indigo-600/30 border border-indigo-400/30 transition-all hover:scale-[1.02]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Coba Lagi (Tailoring)</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="glass-panel p-4 rounded-2xl border border-emerald-500/40 bg-emerald-950/20 text-xs text-emerald-200">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="glass-panel p-4 rounded-2xl border border-rose-500/40 bg-rose-950/20 text-xs text-rose-200">
            {{ session('error') }}
        </div>
    @endif

    <!-- Job Description Collapsible Accordion -->
    <details class="glass-panel rounded-2xl border border-slate-800/80 overflow-hidden group">
        <summary class="p-4 cursor-pointer flex items-center justify-between text-xs font-semibold text-slate-300 hover:bg-slate-900/50 transition-colors">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Lihat Teks Job Description Target
            </span>
            <span class="text-xs text-indigo-400 group-open:rotate-180 transition-transform">&darr;</span>
        </summary>
        <div class="p-4 border-t border-slate-800/80 bg-slate-950/80">
            <p class="text-xs text-slate-300 font-mono whitespace-pre-wrap leading-relaxed">{{ $generatedCv->job_description }}</p>
        </div>
    </details>

    <!-- STATE 1: PENDING -->
    @if ($generatedCv->status->value === 'pending')
        @if ($generatedCv->is_instant_check)
            <div class="glass-panel p-10 rounded-3xl border border-slate-800 text-center space-y-6">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-fuchsia-500/10 border border-fuchsia-500/20 flex items-center justify-center text-fuchsia-400">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="space-y-2 max-w-md mx-auto">
                    <h3 class="text-xl font-extrabold text-white">Siap untuk Cek ATS & Kesesuaian Posisi</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Sistem akan menganalisis kesesuaian <strong>Master Profile Anda saat ini</strong> dengan posisi target <strong>"{{ $generatedCv->target_position }}"</strong> dan Job Description yang diberikan secara langsung.
                    </p>
                </div>
                <form action="{{ route('tailoring.score', $generatedCv) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-500 hover:to-pink-500 text-white font-bold text-sm shadow-xl shadow-fuchsia-600/30 border border-fuchsia-400/30 transition-all hover:scale-[1.02]">
                        Mulai Analisis ATS Instan &rarr;
                    </button>
                </form>
            </div>
        @else
            <div class="glass-panel p-10 rounded-3xl border border-slate-800 text-center space-y-6">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="space-y-2 max-w-md mx-auto">
                    <h3 class="text-xl font-extrabold text-white">Siap untuk Memulai AI Tailoring</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Klik tombol di bawah untuk memproses Master Profile Anda dengan Job Description di atas menggunakan AI Gemini.
                    </p>
                </div>
                <form action="{{ route('tailoring.generate', $generatedCv) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-8 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-bold text-sm shadow-xl shadow-indigo-600/30 border border-indigo-400/30 transition-all hover:scale-[1.02]">
                        Mulai AI Tailoring Now &rarr;
                    </button>
                </form>
            </div>

            @include('tailoring.partials.refine-panel')
        @endif

    <!-- STATE 2: FAILED -->
    @elseif ($generatedCv->status->value === 'failed')
        <div class="glass-panel p-8 rounded-3xl border border-rose-500/40 bg-rose-950/20 space-y-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="space-y-2 flex-1">
                    <h3 class="text-lg font-bold text-rose-200">
                        @if ($isScoringStageFailed)
                            Proses ATS Scoring Gagal
                        @else
                            Proses Tailoring Gagal / Hard Constraint Violations
                        @endif
                    </h3>
                    <p class="text-xs text-rose-300/90 leading-relaxed">
                        @if ($isScoringStageFailed && is_array($atsError))
                            {{ $atsError['error'] ?? 'Terjadi kesalahan saat menghitung skor ATS.' }}
                        @else
                            {{ is_string($errorData) ? $errorData : ($errorData['error'] ?? 'Terjadi kesalahan sistem saat pemrosesan AI.') }}
                        @endif
                    </p>

                    @if (!$isScoringStageFailed && is_array($errorData) && !empty($errorData['invalid_ids']))
                        <div class="mt-3 p-3 rounded-xl bg-slate-950/80 border border-rose-900/50 text-xs font-mono text-rose-300">
                            <strong>Invalid/Fabricated IDs Detected:</strong> {{ implode(', ', $errorData['invalid_ids']) }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end pt-2 border-t border-rose-900/40">
                @if ($isScoringStageFailed)
                    {{-- Tailoring sudah berhasil; hanya ulangi tahap scoring --}}
                    <form action="{{ route('tailoring.score', $generatedCv) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition-colors shadow-lg shadow-rose-950/40">
                            Coba Lagi (Scoring)
                        </button>
                    </form>
                @else
                    {{-- Gagal sebelum final tersimpan → regenerate dari awal --}}
                    <form action="{{ route('tailoring.generate', $generatedCv) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs transition-colors shadow-lg shadow-rose-950/40">
                            Coba Lagi (Re-generate)
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @include('tailoring.partials.refine-panel')

    <!-- STATE 3: SCORING (in progress) -->
    @elseif ($generatedCv->status->value === 'scoring')
        <div class="glass-panel p-8 rounded-3xl border border-amber-500/40 bg-amber-950/10 space-y-4 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 animate-pulse">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-amber-200">Sedang menghitung skor ATS...</h3>
            <p class="text-xs text-amber-300/80 max-w-md mx-auto leading-relaxed">
                AI sedang menganalisis kecocokan antara CV final Anda dengan Job Description target. Halaman akan otomatis menampilkan hasilnya setelah selesai.
            </p>
            <script>
                setTimeout(function () { window.location.reload(); }, 4000);
            </script>
        </div>

    <!-- STATE 3.5: INSTANT CHECK COMPLETED (READ-ONLY PROFILE PREVIEW) -->
    @elseif ($generatedCv->is_instant_check && $generatedCv->status->value === 'completed')
        @if (!empty($generatedCv->raw_cv_text))
            <div class="space-y-6">
                <!-- Alert Banner -->
                <div class="glass-panel p-4 rounded-2xl border border-fuchsia-500/30 bg-fuchsia-950/10 text-xs text-slate-200">
                    <span class="font-bold text-fuchsia-300">💡 Info Cek PDF Instan:</span> Penilaian ATS di bawah dihitung langsung berdasarkan file dokumen yang Anda unggah secara independen tanpa dipengaruhi oleh Master Profile Anda.
                </div>

                <!-- Raw Text Preview Card -->
                <div class="glass-panel p-6 rounded-2xl border border-slate-800 space-y-3 shadow-md">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ekstraksi Teks CV Dokumen</h3>
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-900 text-xs text-slate-300 font-mono leading-relaxed max-h-[400px] overflow-y-auto whitespace-pre-wrap">{{ $generatedCv->raw_cv_text }}</div>
                </div>
            </div>
        @else
            <div class="space-y-6">
                <!-- Alert Banner -->
                <div class="glass-panel p-4 rounded-2xl border border-indigo-500/30 bg-indigo-950/10 text-xs text-slate-200">
                    <span class="font-bold text-indigo-300">💡 Info Cek Instan:</span> Penilaian ATS di bawah dihitung langsung berdasarkan <strong>Master Profile Anda saat ini</strong> (tanpa tailoring). Jika ingin meningkatkan relevansi dan menyelaraskan isi CV Anda dengan Job Description ini, silakan klik tombol <strong>"Mulai AI Tailoring Posisi Ini"</strong> di kanan atas.
                </div>

                <!-- Profile Summary Preview -->
                <div class="glass-panel p-6 rounded-2xl border border-slate-800 space-y-3 shadow-md">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Executive Summary</h3>
                    <p class="text-xs text-slate-200 leading-relaxed font-sans">{{ $masterProfile['summary'] ?? 'Tidak ada ringkasan eksekutif.' }}</p>
                </div>

                <!-- Experiences Preview -->
                <div class="glass-panel p-6 rounded-2xl border border-slate-800 space-y-4 shadow-md">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pengalaman Kerja</h3>
                    <div class="space-y-4">
                        @forelse ($masterProfile['experiences'] ?? [] as $exp)
                            <div class="border-l-2 border-indigo-500/40 pl-4 space-y-1">
                                <h4 class="text-xs font-bold text-white">{{ $exp['position'] }} di <span class="text-indigo-300 font-semibold">{{ $exp['company'] }}</span></h4>
                                <ul class="list-disc list-inside text-xs text-slate-300 space-y-1 mt-1 font-sans">
                                    @foreach ($exp['bullet_points'] ?? [] as $bullet)
                                        <li>{{ is_array($bullet) ? ($bullet['content'] ?? '') : $bullet }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic">Tidak ada riwayat pengalaman.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Skills Preview -->
                <div class="glass-panel p-6 rounded-2xl border border-slate-800 space-y-3 shadow-md">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Skills Inventory</h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse ($masterProfile['skills'] ?? [] as $sk)
                            <span class="px-2.5 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs text-slate-300">{{ $sk }}</span>
                        @empty
                            <p class="text-xs text-slate-500 italic">Tidak ada keahlian terdaftar.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Projects Preview -->
                <div class="glass-panel p-6 rounded-2xl border border-slate-800 space-y-4 shadow-md">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Proyek & Portofolio</h3>
                    <div class="space-y-4">
                        @forelse ($masterProfile['projects'] ?? [] as $proj)
                            <div class="border-l-2 border-purple-500/40 pl-4 space-y-1">
                                <h4 class="text-xs font-bold text-white">{{ $proj['name'] }}</h4>
                                <ul class="list-disc list-inside text-xs text-slate-300 space-y-1 mt-1 font-sans">
                                    @foreach ($proj['bullet_points'] ?? [] as $bullet)
                                        <li>{{ is_array($bullet) ? ($bullet['content'] ?? '') : $bullet }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic">Tidak ada proyek terdaftar.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

    <!-- STATE 4: REVIEW / EDITABLE FORM -->
    @elseif ($tailoredData)
        @php
            $rawPayload = $generatedCv->tailored_cv_raw ?? [];
            $gaps = $rawPayload['gaps'] ?? ($rawPayload['tailored_cv']['gaps'] ?? []);
            $suggestions = $rawPayload['suggestions'] ?? ($rawPayload['tailored_cv']['suggestions'] ?? []);
            $warnings = $rawPayload['warnings'] ?? [];
            $headline = $generatedCv->target_position ?: ($rawPayload['headline'] ?? ($rawPayload['tailored_cv']['headline'] ?? ''));
        @endphp

        {{-- Deterministic Faithfulness Warnings Banner --}}
        @if (!empty($warnings))
            <div class="glass-panel p-5 rounded-2xl border border-amber-500/40 bg-amber-950/20 space-y-2">
                <div class="flex items-center gap-2 text-amber-300 font-bold text-xs uppercase tracking-wider">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Catatan Integritas & Verifikasi Klaim (AI Quality Check)</span>
                </div>
                <p class="text-xs text-amber-200/80 leading-relaxed">
                    Sistem mendeteksi beberapa kata klaim kuat, keahlian, atau angka yang memerlukan perhatian Anda sebelum dikirim ke HRD:
                </p>
                <ul class="list-disc list-inside text-xs text-amber-100/90 space-y-1 pt-1 font-sans">
                    @foreach ($warnings as $w)
                        <li>{{ $w }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Gaps & Suggestions Cards --}}
        @if (!empty($gaps) || !empty($suggestions))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if (!empty($gaps))
                    <div class="glass-panel p-5 rounded-2xl border border-rose-500/30 bg-rose-950/15 space-y-2">
                        <div class="flex items-center gap-2 text-rose-300 font-bold text-xs uppercase tracking-wider">
                            <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Kesenjangan Kualifikasi (JD vs Profil)</span>
                        </div>
                        <p class="text-[11px] text-rose-200/80 leading-relaxed">
                            Poin berikut diminta oleh JD tetapi tidak ditemukan buktinya di Master Profile Anda. Engine <strong>sengaja tidak mengarangnya</strong> di riwayat kerja:
                        </p>
                        <ul class="list-disc list-inside text-xs text-rose-100/90 space-y-1 pt-1">
                            @foreach ($gaps as $g)
                                <li>{{ $g }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($suggestions))
                    <div class="glass-panel p-5 rounded-2xl border border-sky-500/30 bg-sky-950/15 space-y-2">
                        <div class="flex items-center gap-2 text-sky-300 font-bold text-xs uppercase tracking-wider">
                            <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <span>Saran Penguatan Konten</span>
                        </div>
                        <p class="text-[11px] text-sky-200/80 leading-relaxed">
                            Rekomendasi dari AI untuk memperkuat daya saing CV Anda:
                        </p>
                        <ul class="list-disc list-inside text-xs text-sky-100/90 space-y-1 pt-1">
                            @foreach ($suggestions as $s)
                                <li>{{ $s }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        @include('tailoring.partials.refine-panel')

        <form action="{{ route('tailoring.finalize', $generatedCv) }}" method="POST" class="space-y-8">
            @csrf

            <!-- Target Headline -->
            <div class="glass-panel p-5 rounded-2xl border border-slate-800 space-y-2">
                <label class="block text-xs font-bold text-slate-300">
                    Target Headline / Posisi CV <span class="text-slate-400 font-normal">(Ditampilkan di bawah nama kandidat pada PDF & Word)</span>
                </label>
                <input
                    type="text"
                    name="headline"
                    value="{{ old('headline', $headline) }}"
                    placeholder="Contoh: Staf IT / IT Generalist"
                    class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                >
            </div>

            <!-- 1. Tailored Summary -->
            <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        1. Executive Summary (Tailored)
                    </h3>
                    <span class="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                        Dapat Diedit
                    </span>
                </div>
                <textarea
                    name="summary"
                    rows="4"
                    class="w-full p-4 text-xs bg-slate-950/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 font-sans leading-relaxed"
                    required
                >{{ old('summary', $tailoredData['summary'] ?? '') }}</textarea>
            </div>

            <!-- 2. Key Projects -->
            @php
                $tailoredProjects = $tailoredData['projects'] ?? [];
                $projectsInCv = collect($tailoredProjects)
                    ->pluck('source_id')
                    ->filter()
                    ->map(fn ($id) => (string) $id)
                    ->all();
                $pickableProjects = ($masterProjects ?? collect())
                    ->reject(fn ($project) => in_array((string) $project->id, $projectsInCv, true));
            @endphp

            @if (!empty($tailoredProjects) || ($masterProjects ?? collect())->isNotEmpty())
                <div id="tailored-projects" class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-800 pb-3">
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            2. Key Projects
                        </h3>
                        <span class="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-sky-500/20 text-sky-300 border border-sky-500/30">
                            {{ count($tailoredProjects) }} Proyek di CV
                        </span>
                    </div>

                    @if (empty($tailoredProjects))
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Belum ada proyek di versi CV ini. Pilih proyek dari Master Profile di bawah untuk menambahkannya.
                        </p>
                    @endif

                    <div class="space-y-4">
                        @foreach ($tailoredProjects as $projIndex => $proj)
                            <div data-project-card class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3 transition-all">
                                <input type="hidden" name="projects[{{ $projIndex }}][source_id]" value="{{ $proj['source_id'] ?? '' }}">
                                <input type="checkbox" name="projects[{{ $projIndex }}][remove]" value="1" class="hidden" data-project-remove-input>

                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-slate-400 mb-1">Nama Proyek</label>
                                        <input type="text" name="projects[{{ $projIndex }}][name]" value="{{ $proj['name'] }}" required
                                               class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                                    </div>

                                    <button type="button" data-project-remove-toggle
                                            class="shrink-0 mt-5 px-2.5 py-1.5 rounded-lg bg-rose-500/10 border border-rose-500/40 text-[11px] font-semibold text-rose-300 hover:bg-rose-950/40 transition-colors">
                                        Hapus dari CV
                                    </button>
                                </div>

                                @if (!empty($proj['tech_stack']))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($proj['tech_stack'] as $ts)
                                            <span class="px-1.5 py-0.5 rounded bg-slate-800/80 border border-slate-700 text-[10px] text-slate-300">{{ $ts }}</span>
                                            <input type="hidden" name="projects[{{ $projIndex }}][tech_stack][]" value="{{ $ts }}">
                                        @endforeach
                                    </div>
                                @endif

                                <div class="space-y-2 pt-2">
                                    <label class="block text-xs font-bold text-slate-300">Deskripsi Poin Proyek</label>
                                    @foreach ($proj['bullet_points'] ?? [] as $bIndex => $b)
                                        <div>
                                            <input type="hidden" name="projects[{{ $projIndex }}][bullet_points][{{ $bIndex }}][source_bullet_id]" value="{{ is_array($b) ? $b['source_bullet_id'] : '' }}">
                                            <textarea name="projects[{{ $projIndex }}][bullet_points][{{ $bIndex }}][content]" rows="2" required
                                                      class="w-full p-2.5 text-xs bg-slate-900 border border-slate-700/80 rounded-xl text-slate-200">{{ is_array($b) ? ($b['content'] ?? '') : $b }}</textarea>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Reselect projects from Master Profile (re-add ones dropped by AI or removed earlier) --}}
                    @if ($pickableProjects->isNotEmpty())
                        <div class="p-5 rounded-2xl bg-slate-900/40 border border-dashed border-slate-700 space-y-3">
                            <div class="flex items-start gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-300 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-xs font-bold text-slate-200">Pilih Ulang Proyek dari Master Profile</h4>
                                    <p class="text-[11px] text-slate-400 leading-relaxed">
                                        Centang proyek yang ingin dimasukkan (kembali) ke CV ini, lalu klik <strong class="text-indigo-300">Simpan Versi Final</strong>.
                                        Proyek yang baru ditambahkan akan <strong class="text-sky-300">di-tailor otomatis oleh AI</strong> (diterjemahkan ke bahasa CV &amp; disesuaikan dengan Job Description).
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                @foreach ($pickableProjects as $masterProject)
                                    <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800 hover:border-sky-500/40 cursor-pointer transition-colors">
                                        <input type="checkbox" name="add_project_ids[]" value="{{ $masterProject->id }}"
                                               class="mt-0.5 w-4 h-4 rounded border-slate-600 bg-slate-900 accent-sky-500 cursor-pointer focus:ring-sky-500">
                                        <span class="flex-1 space-y-1">
                                            <span class="block text-xs font-semibold text-slate-100">{{ $masterProject->name }}</span>
                                            @if (!empty($masterProject->tech_stack))
                                                <span class="flex flex-wrap gap-1">
                                                    @foreach ($masterProject->tech_stack as $tech)
                                                        <span class="px-1.5 py-0.5 rounded bg-slate-800/80 border border-slate-700 text-[10px] text-slate-300">{{ $tech }}</span>
                                                    @endforeach
                                                </span>
                                            @endif
                                            <span class="block text-[10px] text-slate-500">{{ $masterProject->bulletPoints->count() }} poin pencapaian</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @elseif (!empty($tailoredProjects))
                        <p class="text-[11px] text-slate-500">
                            Semua proyek dari Master Profile sudah ada di CV ini.
                        </p>
                    @endif
                </div>

                <script>
                    (function () {
                        document.querySelectorAll('[data-project-card]').forEach(function (card) {
                            var input = card.querySelector('[data-project-remove-input]');
                            var toggle = card.querySelector('[data-project-remove-toggle]');
                            if (!input || !toggle) return;

                            function sync() {
                                var marked = input.checked;
                                card.classList.toggle('opacity-50', marked);
                                card.classList.toggle('border-rose-500/60', marked);
                                toggle.textContent = marked ? 'Batalkan hapus' : 'Hapus dari CV';
                                toggle.classList.toggle('border-rose-500/40', !marked);
                                toggle.classList.toggle('text-rose-300', !marked);
                                toggle.classList.toggle('border-slate-600', marked);
                                toggle.classList.toggle('text-slate-300', marked);
                            }

                            toggle.addEventListener('click', function () {
                                input.checked = !input.checked;
                                sync();
                            });

                            sync();
                        });
                    })();
                </script>
            @endif

            <!-- 3. Tailored Work Experiences -->
            <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6">
                <h3 class="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    3. Pengalaman Kerja (Tailored Bullets)
                </h3>

                <div class="space-y-6">
                    @foreach ($tailoredData['experiences'] ?? [] as $expIndex => $exp)
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-4">
                            <!-- Hidden Source ID -->
                            <input type="hidden" name="experiences[{{ $expIndex }}][source_id]" value="{{ $exp['source_id'] }}">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">Posisi / Jabatan</label>
                                    <input type="text" name="experiences[{{ $expIndex }}][position]" value="{{ $exp['position'] }}" required
                                           class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">Nama Perusahaan</label>
                                    <input type="text" name="experiences[{{ $expIndex }}][company]" value="{{ $exp['company'] }}" required
                                           class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">Tanggal Mulai</label>
                                    <input type="text" name="experiences[{{ $expIndex }}][start_date]" value="{{ $exp['start_date'] }}" required
                                           class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1">Tanggal Selesai (Kosongkan jika aktif)</label>
                                    <input type="text" name="experiences[{{ $expIndex }}][end_date]" value="{{ $exp['end_date'] ?? '' }}"
                                           class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                                </div>
                            </div>

                            <!-- Bullet Points -->
                            <div class="space-y-3 pt-2">
                                <label class="block text-xs font-bold text-slate-300">Poin Pencapaian Reframed</label>
                                @foreach ($exp['bullet_points'] ?? [] as $bIndex => $b)
                                    <div class="space-y-1">
                                        <input type="hidden" name="experiences[{{ $expIndex }}][bullet_points][{{ $bIndex }}][source_bullet_id]" value="{{ is_array($b) ? $b['source_bullet_id'] : '' }}">
                                        <textarea name="experiences[{{ $expIndex }}][bullet_points][{{ $bIndex }}][content]" rows="2" required
                                                  class="w-full p-2.5 text-xs bg-slate-900 border border-slate-700/80 rounded-xl text-slate-200">{{ is_array($b) ? ($b['content'] ?? '') : $b }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 4. Education -->
            <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6">
                <h3 class="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                    </svg>
                    4. Pendidikan
                </h3>

                <div class="space-y-4">
                    @foreach ($tailoredData['education'] ?? [] as $eduIndex => $edu)
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-800/80 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input type="hidden" name="education[{{ $eduIndex }}][source_id]" value="{{ $edu['source_id'] }}">

                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Institusi Pendidikan</label>
                                <input type="text" name="education[{{ $eduIndex }}][institution]" value="{{ $edu['institution'] }}" required
                                       class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Gelar / Jurusan</label>
                                <input type="text" name="education[{{ $eduIndex }}][degree]" value="{{ $edu['degree'] }}" required
                                       class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Tanggal Mulai</label>
                                <input type="text" name="education[{{ $eduIndex }}][start_date]" value="{{ $edu['start_date'] }}" required
                                       class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1">Tanggal Selesai</label>
                                <input type="text" name="education[{{ $eduIndex }}][end_date]" value="{{ $edu['end_date'] ?? '' }}"
                                       class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-slate-400 mb-1">Catatan Akademik / IPK (Opsional)</label>
                                <input type="text" name="education[{{ $eduIndex }}][notes]" value="{{ $edu['notes'] ?? 'IPK 3.63 / 4.00' }}"
                                       placeholder="Contoh: IPK 3.63 / 4.00"
                                       class="w-full text-xs bg-slate-900 border border-slate-700/80 rounded-xl p-2.5 text-white">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 5. Highlighted Skills -->
            <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6">
                <h3 class="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                    <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    5. Highlighted Skills & Competencies
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($tailoredData['skills_highlighted'] ?? [] as $skIndex => $skGroup)
                        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3">
                            <input type="text" name="skills_highlighted[{{ $skIndex }}][category]" value="{{ $skGroup['category'] }}" required
                                   class="w-full text-xs font-bold bg-slate-900 border border-slate-700/80 text-teal-300 rounded-xl p-2">

                            <div class="space-y-2">
                                @foreach ($skGroup['skills'] ?? [] as $sSubIndex => $skName)
                                    <input type="text" name="skills_highlighted[{{ $skIndex }}][skills][]" value="{{ $skName }}" required
                                           class="w-full text-xs bg-slate-900 border border-slate-800 rounded-lg p-2 text-slate-200">
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Submit Finalize Form Bar -->
            <div class="p-6 rounded-3xl glass-panel border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 sticky bottom-4 z-40 bg-slate-950/90 shadow-2xl backdrop-blur-xl">
                <div class="text-xs text-slate-300">
                    Periksa kembali suntingan Anda. Klik <strong class="text-indigo-300">Simpan Versi Final</strong> untuk mengunci konten yang siap dicetak ke PDF.
                </div>

                <div class="flex items-center gap-3">
                    @if ($hasFinal)
                        <a href="{{ route('tailoring.pdf', $generatedCv) }}" target="_blank" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 transition-colors">
                            Pratinjau PDF
                        </a>
                        <a href="{{ route('tailoring.docx', $generatedCv) }}" class="px-4 py-2.5 rounded-xl bg-blue-900/40 hover:bg-blue-800/60 text-xs font-semibold text-blue-300 border border-blue-700/60 transition-colors">
                            Download Word (.docx)
                        </a>
                    @endif

                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-bold text-xs shadow-xl shadow-indigo-600/30 border border-indigo-400/30 transition-all hover:scale-[1.02]">
                        Simpan Versi Final &rarr;
                    </button>
                </div>
            </div>
        </form>

        <!-- ============================================================== -->
        <!-- EPIC 4: ATS SCORING RESULT SECTION (rendered after the form)    -->
        <!-- ============================================================== -->
        @if ($hasValidAtsScore)
            @php
                $score = $atsScore['score'];
                $feedback = $atsScore['feedback'] ?? [];
                $matched = $score['matched_keywords'] ?? [];
                $missing = $score['missing_keywords'] ?? [];
                $percentage = (int) $score['match_percentage'];

                // colour band for the big percentage number
                if ($percentage >= 75) {
                    $pctColor = 'text-emerald-300';
                    $pctRing = 'from-emerald-500 to-teal-500';
                    $pctLabel = 'Kecocokan Kuat';
                } elseif ($percentage >= 50) {
                    $pctColor = 'text-amber-300';
                    $pctRing = 'from-amber-500 to-orange-500';
                    $pctLabel = 'Kecocokan Cukup';
                } else {
                    $pctColor = 'text-rose-300';
                    $pctRing = 'from-rose-500 to-pink-500';
                    $pctLabel = 'Kecocokan Lemah';
                }
            @endphp
            <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-fuchsia-500/30 space-y-6 relative overflow-hidden">
                <div class="absolute inset-0 ai-sparkle-bg opacity-30 pointer-events-none"></div>

                <div class="relative flex items-center justify-between gap-4 flex-wrap">
                    <h3 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        6. ATS Scoring Result
                    </h3>
                    <span class="text-[10px] font-semibold uppercase tracking-wider px-2.5 py-1 rounded-full bg-fuchsia-500/20 text-fuchsia-300 border border-fuchsia-500/30">
                        Epic 4 — Gemini Call 2
                    </span>
                </div>

                {{-- Big percentage + progress bar --}}
                <div class="relative grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                    <div class="md:col-span-1 flex flex-col items-center justify-center p-6 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-2">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Match Score</div>
                        <div class="text-6xl font-extrabold {{ $pctColor }} drop-shadow-lg">{{ $percentage }}<span class="text-2xl align-top">%</span></div>
                        <div class="text-xs font-semibold {{ $pctColor }}">{{ $pctLabel }}</div>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <div class="w-full h-3 rounded-full bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r {{ $pctRing }} transition-all" style="width: {{ $percentage }}%"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <div class="p-3 rounded-xl bg-emerald-950/30 border border-emerald-500/20">
                                <div class="text-[10px] font-bold uppercase text-emerald-300/80 tracking-wider">Matched</div>
                                <div class="text-2xl font-extrabold text-emerald-300">{{ count($matched) }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-rose-950/30 border border-rose-500/20">
                                <div class="text-[10px] font-bold uppercase text-rose-300/80 tracking-wider">Missing</div>
                                <div class="text-2xl font-extrabold text-rose-300">{{ count($missing) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Applied Position & Suitability block --}}
                @if (!empty($atsScore['suitability']))
                    @php
                        $suit = $atsScore['suitability'];
                        $rating = $suit['rating'] ?? 'Kurang';
                        
                        $ratingColors = match($rating) {
                            'Kuat' => [
                                'bg' => 'bg-emerald-500/15',
                                'text' => 'text-emerald-300',
                                'border' => 'border-emerald-500/30',
                            ],
                            'Cukup' => [
                                'bg' => 'bg-amber-500/15',
                                'text' => 'text-amber-300',
                                'border' => 'border-amber-500/30',
                            ],
                            'Kurang' => [
                                'bg' => 'bg-rose-500/15',
                                'text' => 'text-rose-300',
                                'border' => 'border-rose-500/30',
                            ],
                            default => [
                                'bg' => 'bg-slate-500/15',
                                'text' => 'text-slate-300',
                                'border' => 'border-slate-500/30',
                            ],
                        };
                    @endphp
                    <div class="relative p-5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-4 shadow-inner">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>Posisi yang Dilamar: <strong class="text-indigo-300">"{{ $generatedCv->target_position ?? 'Belum ditentukan' }}"</strong></span>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $ratingColors['bg'] }} {{ $ratingColors['text'] }} {{ $ratingColors['border'] }} border">
                                Tingkat Kecocokan: {{ $rating }}
                            </span>
                        </div>
                        
                        <div class="space-y-1.5">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Analisis Kesesuaian</h4>
                            <p class="text-xs text-slate-200 leading-relaxed font-sans">{{ $suit['reason'] ?? 'Tidak ada analisis.' }}</p>
                        </div>

                        @if (!empty($suit['suggestions']))
                            <div class="space-y-1.5 pt-2 border-t border-slate-900">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Saran Tambahan untuk Posisi Ini</h4>
                                <ul class="list-disc list-inside text-xs text-slate-300 space-y-1 mt-1 font-sans">
                                    @foreach ($suit['suggestions'] as $suggestion)
                                        <li>{{ $suggestion }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Keywords --}}
                <div class="relative grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-300">Matched Keywords</h4>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($matched as $kw)
                                <span class="px-2.5 py-1 rounded-full bg-emerald-500/15 text-emerald-200 border border-emerald-500/30 text-xs font-medium">{{ $kw }}</span>
                            @empty
                                <span class="text-xs text-slate-500 italic">Tidak ada keyword yang cocok terdeteksi.</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-rose-300">Missing Keywords</h4>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($missing as $kw)
                                <span class="px-2.5 py-1 rounded-full bg-rose-500/15 text-rose-200 border border-rose-500/30 text-xs font-medium">{{ $kw }}</span>
                            @empty
                                <span class="text-xs text-slate-500 italic">Tidak ada keyword penting yang hilang. Mantap!</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Feedback list --}}
                @if (!empty($feedback))
                    <div class="relative space-y-3 pt-3 border-t border-slate-800">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Feedback untuk Anda</h4>
                        <ul class="space-y-2">
                            @foreach ($feedback as $f)
                                @php
                                    $typeColor = match($f['type'] ?? '') {
                                        'missing_keyword' => 'rose',
                                        'weak_bullet' => 'amber',
                                        'format_suggestion' => 'sky',
                                        default => 'slate',
                                    };
                                    $typeLabel = match($f['type'] ?? '') {
                                        'missing_keyword' => 'Keyword Kurang',
                                        'weak_bullet' => 'Bullet Lemah',
                                        'format_suggestion' => 'Saran Format',
                                        default => 'Catatan',
                                    };
                                @endphp
                                <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                                    <span class="shrink-0 px-2 py-0.5 rounded-full bg-{{ $typeColor }}-500/15 text-{{ $typeColor }}-300 border border-{{ $typeColor }}-500/30 text-[10px] font-bold uppercase tracking-wider">
                                        {{ $typeLabel }}
                                    </span>
                                    <p class="text-xs text-slate-200 leading-relaxed flex-1">{{ $f['message'] ?? '' }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (!empty($atsScore['meta']['model_used']))
                    <p class="relative text-[10px] text-slate-500 text-right">
                        Model: {{ $atsScore['meta']['model_used'] }}
                        @if (!empty($atsScore['meta']['generated_at']))
                            &middot; {{ \Carbon\Carbon::parse($atsScore['meta']['generated_at'])->format('Y-m-d H:i') }}
                        @endif
                    </p>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
