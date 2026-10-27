@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 space-y-8">
    <!-- Header Section -->
    <div class="glass-panel p-8 rounded-3xl border border-slate-800 shadow-2xl space-y-3 relative overflow-hidden ai-sparkle-bg">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold">
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Epic 2: Job Description Context
        </div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Input Job Description</h1>
        <p class="text-sm text-slate-300 leading-relaxed max-w-2xl">
            Tempelkan (paste) teks Job Description (JD) dari lowongan pekerjaan yang ingin Anda lamar. Engine AI akan menganalisis kata kunci, syarat kualifikasi, dan konteks posisi tersebut untuk menyesuaikan (tailor) Master Profile Anda.
        </p>
    </div>

    <!-- Form Section -->
    <div class="glass-panel p-8 rounded-3xl border border-slate-800 shadow-2xl">
        <form action="{{ route('tailoring.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Applied Position -->
            <div class="space-y-2">
                <label for="target_position" class="block text-sm font-bold text-slate-200">
                    Posisi yang Dilamar <span class="text-indigo-400">*</span>
                </label>
                <input
                    type="text"
                    id="target_position"
                    name="target_position"
                    value="{{ old('target_position') }}"
                    placeholder="Contoh: Backend Engineer, Senior Project Manager, Marketing Support..."
                    class="w-full p-4 text-sm bg-slate-950/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all leading-relaxed"
                    required
                >
            </div>

            <!-- Output Language Selection -->
            <div class="space-y-2">
                <label class="block text-sm font-bold text-slate-200">
                    Bahasa Output CV & Analisis <span class="text-indigo-400">*</span>
                </label>
                <div class="flex items-center gap-6 p-4 bg-slate-950/80 border border-slate-700/80 rounded-2xl flex-wrap">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-300 hover:text-white transition-colors">
                        <input type="radio" name="output_language" value="auto" checked
                               class="w-4 h-4 text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 focus:ring-2 focus:ring-offset-slate-900">
                        <span>🌐 Otomatis (Deteksi dari JD)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-300 hover:text-white transition-colors">
                        <input type="radio" name="output_language" value="id"
                               class="w-4 h-4 text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 focus:ring-2 focus:ring-offset-slate-900">
                        <span>🇮🇩 Bahasa Indonesia</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-slate-300 hover:text-white transition-colors">
                        <input type="radio" name="output_language" value="en"
                               class="w-4 h-4 text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 focus:ring-2 focus:ring-offset-slate-900">
                        <span>🇬🇧 English</span>
                    </label>
                </div>
            </div>

            <!-- Model AI Selection -->
            <div class="space-y-3">
                <label class="block text-sm font-bold text-slate-200">
                    Model AI yang Digunakan <span class="text-indigo-400">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach (\App\Models\GeneratedCv::AI_MODELS as $key => $info)
                        <label class="relative flex flex-col p-4 bg-slate-950/80 border border-slate-700/80 rounded-2xl cursor-pointer hover:border-indigo-500/50 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-950/20 has-[:checked]:ring-1 has-[:checked]:ring-indigo-500 transition-all">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-bold text-slate-100">{{ $info['label'] }}</span>
                                <input type="radio" name="ai_model" value="{{ $key }}"
                                       {{ old('ai_model', 'gemini-3.1-flash-lite') === $key ? 'checked' : '' }}
                                       class="w-4 h-4 text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 focus:ring-2 focus:ring-offset-slate-900">
                            </div>
                            <p class="text-xs text-slate-400 leading-relaxed">{{ $info['description'] }}</p>
                        </label>
                    @endforeach
                </div>
                @error('ai_model')
                    <p class="text-xs text-rose-400 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label for="job_description" class="block text-sm font-bold text-slate-200">
                        Teks Job Description <span class="text-indigo-400">*</span>
                    </label>
                    <span id="char-counter" class="text-xs font-semibold text-slate-400">
                        0 Karakter (Min. 100)
                    </span>
                </div>

                <textarea
                    id="job_description"
                    name="job_description"
                    rows="12"
                    placeholder="Tempelkan deskripsi pekerjaan, kualifikasi, tanggung jawab, dan skill yang dibutuhkan di sini..."
                    class="w-full p-4 text-sm bg-slate-950/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition-all font-mono leading-relaxed"
                    required
                >{{ old('job_description') }}</textarea>

                <p class="text-xs text-slate-400 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Masukkan minimal 100 karakter agar AI Gemini memiliki cukup konteks untuk tailoring.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800 flex-wrap">
                <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-sm font-semibold text-slate-300 transition-colors">
                    Batal
                </a>

                {{-- Action 2: Tailoring --}}
                <button type="submit" name="action" value="tailor" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-semibold text-sm shadow-xl shadow-indigo-600/30 border border-indigo-400/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <span>Mulai AI Tailoring & Reframing</span>
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
