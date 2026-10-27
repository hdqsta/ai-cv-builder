@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <div class="glass-panel p-8 sm:p-10 rounded-3xl border border-slate-800 shadow-2xl space-y-6 relative overflow-hidden">
        
        <!-- Glow accent -->
        <div class="absolute -top-24 -right-24 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header Title -->
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                AI CV Parser & Auto-fill
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Upload CV Lama</h2>
            <p class="text-sm text-slate-400">Unggah file CV Anda (<span class="text-slate-200 font-semibold">.pdf</span> atau <span class="text-slate-200 font-semibold">.docx</span>, maks. 5MB). AI Gemini akan membaca dan memilah seluruh riwayat karir Anda secara otomatis ke Master Profile.</p>
        </div>

        <form id="upload-form" action="{{ route('cv.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Interactive File Upload Area -->
            <div class="relative group cursor-pointer border-2 border-dashed border-slate-700/80 hover:border-indigo-500/80 rounded-2xl p-8 text-center transition-all bg-slate-900/40 hover:bg-indigo-950/20">
                <input id="cv_file" name="cv_file" type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required accept=".pdf,.docx">
                
                <div class="space-y-4 pointer-events-none">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 group-hover:scale-110 group-hover:bg-indigo-500 group-hover:text-white transition-all shadow-lg shadow-indigo-500/10">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>

                    <div>
                        <p class="text-base font-bold text-slate-200 group-hover:text-indigo-300 transition-colors">
                            Tarik & lepas file CV di sini, atau <span class="text-indigo-400 underline underline-offset-4">Pilih File</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">Mendukung format PDF atau DOCX hingga 5MB</p>
                    </div>

                    <div id="file-name" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-500/20 border border-indigo-500/40 text-indigo-200 text-xs font-semibold hidden">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span id="file-name-text"></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('home') }}" class="px-5 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-sm font-semibold text-slate-300 transition-colors">
                    Batal
                </a>
                <button type="submit" id="submit-btn" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 border border-indigo-400/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <span>Unggah & Parse AI</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>

        <!-- Animated Loading Screen -->
        <div id="loading-screen" class="hidden py-8 text-center space-y-6">
            <div class="relative w-20 h-20 mx-auto flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20 border-t-indigo-500 animate-spin"></div>
                <div class="w-12 h-12 rounded-full bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400">
                    <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-2">
                <h3 class="text-lg font-bold text-white tracking-tight">AI Gemini Sedang Membaca CV...</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                    Sedang mengurai teks dokumen, mengekstrak riwayat pengalaman, pendidikan, dan skill ke bentuk terstruktur.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('cv_file').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        const fileNameContainer = document.getElementById('file-name');
        const fileNameText = document.getElementById('file-name-text');
        
        if (fileName) {
            fileNameText.textContent = fileName;
            fileNameContainer.classList.remove('hidden');
        } else {
            fileNameContainer.classList.add('hidden');
        }
    });

    document.getElementById('upload-form').addEventListener('submit', function() {
        document.getElementById('submit-btn').disabled = true;
        document.getElementById('upload-form').classList.add('hidden');
        document.getElementById('loading-screen').classList.remove('hidden');
    });
</script>
@endsection

