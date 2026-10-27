@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 space-y-8">
    <!-- Header Section -->
    <div class="glass-panel p-8 rounded-3xl border border-slate-800 shadow-2xl space-y-3 relative overflow-hidden ai-sparkle-bg">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-fuchsia-500/10 border border-fuchsia-500/20 text-fuchsia-300 text-xs font-semibold">
            <svg class="w-4 h-4 text-fuchsia-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Cek ATS PDF Instan
        </div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Cek ATS PDF Instan</h1>
        <p class="text-sm text-slate-300 leading-relaxed max-w-2xl">
            Unggah file CV Anda yang sudah jadi (PDF/DOCX) beserta deskripsi pekerjaan dan nama posisi yang dilamar. Sistem akan langsung mengekstrak teks dokumen tersebut untuk dianalisis oleh AI Gemini guna menilai skor ATS dan kecocokan posisi.
        </p>
    </div>

    @if (session('error'))
        <div class="glass-panel p-4 rounded-2xl border border-rose-500/40 bg-rose-950/20 text-xs text-rose-200">
            {{ session('error') }}
        </div>
    @endif

    <!-- Form Section -->
    <div class="glass-panel p-8 rounded-3xl border border-slate-800 shadow-2xl">
        <form action="{{ route('pdf-checker.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- CV File Upload -->
            <div class="space-y-2">
                <label for="cv_file" class="block text-sm font-bold text-slate-200">
                    Unggah Dokumen CV (PDF / DOCX) <span class="text-fuchsia-400">*</span>
                </label>
                <div class="relative group">
                    <input
                        type="file"
                        id="cv_file"
                        name="cv_file"
                        accept=".pdf,.docx"
                        class="w-full text-xs text-slate-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-100 hover:file:bg-slate-700 transition-all border border-slate-700/80 rounded-2xl p-3.5 bg-slate-950/80"
                        required
                    >
                </div>
                <p class="text-[10px] text-slate-400">Mendukung format .pdf dan .docx (Maksimal 5MB).</p>
                @error('cv_file')
                    <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Target Applied Position -->
            <div class="space-y-2">
                <label for="target_position" class="block text-sm font-bold text-slate-200">
                    Posisi yang Dilamar <span class="text-fuchsia-400">*</span>
                </label>
                <input
                    type="text"
                    id="target_position"
                    name="target_position"
                    value="{{ old('target_position') }}"
                    placeholder="Contoh: Backend Engineer, Senior Project Manager, Marketing Support..."
                    class="w-full p-4 text-sm bg-slate-950/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/30 transition-all leading-relaxed"
                    required
                >
                @error('target_position')
                    <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Output Language Selection -->
            <div class="space-y-2">
                <label class="block text-sm font-bold text-slate-200">
                    Bahasa Output CV & Analisis <span class="text-fuchsia-400">*</span>
                </label>
                <div class="flex items-center gap-6 p-4 bg-slate-950/80 border border-slate-700/80 rounded-2xl">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-300 hover:text-white transition-colors">
                        <input type="radio" name="output_language" value="id" checked
                               class="w-4 h-4 text-fuchsia-600 bg-slate-900 border-slate-700 focus:ring-fuchsia-500 focus:ring-2 focus:ring-offset-slate-900">
                        <span>🇮🇩 Bahasa Indonesia</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-300 hover:text-white transition-colors">
                        <input type="radio" name="output_language" value="en"
                               class="w-4 h-4 text-fuchsia-600 bg-slate-900 border-slate-700 focus:ring-fuchsia-500 focus:ring-2 focus:ring-offset-slate-900">
                        <span>🇬🇧 English</span>
                    </label>
                </div>
            </div>

            <!-- Model AI Selection -->
            <div class="space-y-3">
                <label class="block text-sm font-bold text-slate-200">
                    Model AI yang Digunakan <span class="text-fuchsia-400">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach (\App\Models\GeneratedCv::AI_MODELS as $key => $info)
                        <label class="relative flex flex-col p-4 bg-slate-950/80 border border-slate-700/80 rounded-2xl cursor-pointer hover:border-fuchsia-500/50 has-[:checked]:border-fuchsia-500 has-[:checked]:bg-fuchsia-950/20 has-[:checked]:ring-1 has-[:checked]:ring-fuchsia-500 transition-all">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-bold text-slate-100">{{ $info['label'] }}</span>
                                <input type="radio" name="ai_model" value="{{ $key }}"
                                       {{ old('ai_model', 'gemini-3.1-flash-lite') === $key ? 'checked' : '' }}
                                       class="w-4 h-4 text-fuchsia-600 bg-slate-900 border-slate-700 focus:ring-fuchsia-500 focus:ring-2 focus:ring-offset-slate-900">
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">{{ $info['description'] }}</p>
                        </label>
                    @endforeach
                </div>
                @error('ai_model')
                    <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Job Description -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label for="job_description" class="block text-sm font-bold text-slate-200">
                        Teks Job Description <span class="text-fuchsia-400">*</span>
                    </label>
                    <span id="char-counter" class="text-xs font-semibold text-slate-400">
                        0 Karakter (Min. 100)
                    </span>
                </div>

                <textarea
                    id="job_description"
                    name="job_description"
                    rows="10"
                    placeholder="Tempelkan deskripsi pekerjaan, kualifikasi, tanggung jawab, dan skill yang dibutuhkan di sini..."
                    class="w-full p-4 text-sm bg-slate-950/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-500/30 transition-all font-mono leading-relaxed"
                    required
                >{{ old('job_description') }}</textarea>

                <p class="text-xs text-slate-400 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-fuchsia-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Masukkan minimal 100 karakter agar AI Gemini memiliki cukup konteks analisis.
                </p>
                @error('job_description')
                    <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-sm font-semibold text-slate-300 transition-colors">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-fuchsia-600 via-purple-600 to-pink-600 hover:from-fuchsia-500 hover:to-pink-500 text-white font-semibold text-sm shadow-xl shadow-fuchsia-600/20 border border-fuchsia-400/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <span>Lanjut ke Analisis ATS PDF</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('job_description');
        const counter = document.getElementById('char-counter');

        function updateCounter() {
            const len = textarea.value.length;
            counter.textContent = `${len} Karakter ${len < 100 ? '(Min. 100)' : '✓'}`;
            if (len >= 100) {
                counter.classList.remove('text-slate-400', 'text-rose-400');
                counter.classList.add('text-emerald-400');
            } else {
                counter.classList.remove('text-emerald-400');
                counter.classList.add('text-slate-400');
            }
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
</script>
@endsection
