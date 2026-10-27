@extends('layouts.app')

@section('content')
<div class="space-y-10 py-4">
    <!-- Hero Section with AI Accent -->
    <div class="relative overflow-hidden rounded-3xl p-8 sm:p-12 glass-panel border border-slate-800/80 shadow-2xl ai-sparkle-bg">
        <div class="relative z-10 max-w-3xl space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-semibold tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                Sistem AI CV Tailoring & ATS Optimization
            </div>
            
            <h1 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                Kelola <span class="gradient-text">Master Profile</span> & Tailor CV Impianmu
            </h1>
            
            <p class="text-slate-300 text-base sm:text-lg font-normal leading-relaxed">
                Simpan seluruh riwayat karir, skill, dan pencapaian Anda secara terstruktur. AI Gemini akan membantu melakukan <strong class="text-indigo-300 font-semibold">reframing otomatis</strong> yang disesuaikan dengan setiap Job Description tanpa fabrikasi data.
            </p>

            <!-- Quick Action Buttons -->
            <div class="pt-4 flex flex-wrap items-center gap-4">
                <a href="{{ route('cv.upload.form') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 text-white font-semibold text-sm shadow-xl shadow-indigo-600/30 border border-indigo-400/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Upload CV Lama dengan AI
                </a>

                <a href="{{ route('pdf-checker.create') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-500 hover:to-pink-500 text-white font-semibold text-sm shadow-xl shadow-fuchsia-600/20 border border-fuchsia-400/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="w-5 h-5 text-fuchsia-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Cek ATS PDF Instan
                </a>

                <a href="{{ route('master-check.create') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-650 to-indigo-850 hover:from-indigo-550 hover:to-indigo-750 text-white font-semibold text-sm shadow-xl shadow-indigo-650/20 border border-indigo-500/30 transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Cek ATS Master Profile
                </a>

                <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl glass-panel hover:bg-slate-800/80 text-slate-200 font-semibold text-sm border border-slate-700 transition-all">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Master Profile
                </a>
            </div>
        </div>

        <!-- Ambient Glow Decorative Sphere -->
        <div class="absolute -right-12 -bottom-12 w-80 h-80 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Biodata Stat -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="text-xs font-medium text-slate-400">Biodata</span>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-lg font-bold {{ $stats['profile_filled'] ? 'text-emerald-400' : 'text-slate-500' }}">
                    {{ $stats['profile_filled'] ? 'Lengkap' : 'Kosong' }}
                </span>
                <span class="w-2.5 h-2.5 rounded-full {{ $stats['profile_filled'] ? 'bg-emerald-400' : 'bg-slate-600' }}"></span>
            </div>
        </div>

        <!-- Experiences Stat -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="text-xs font-medium text-slate-400">Pengalaman Kerja</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-indigo-400">{{ $stats['experiences_count'] }}</span>
                <span class="text-xs text-slate-500">entri</span>
            </div>
        </div>

        <!-- Projects Stat -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="text-xs font-medium text-slate-400">Proyek Portofolio</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-purple-400">{{ $stats['projects_count'] }}</span>
                <span class="text-xs text-slate-500">proyek</span>
            </div>
        </div>

        <!-- Education Stat -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="text-xs font-medium text-slate-400">Pendidikan</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-sky-400">{{ $stats['education_count'] }}</span>
                <span class="text-xs text-slate-500">riwayat</span>
            </div>
        </div>

        <!-- Skills Stat -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="text-xs font-medium text-slate-400">Skills & Tools</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-teal-400">{{ $stats['skills_count'] }}</span>
                <span class="text-xs text-slate-500">keahlian</span>
            </div>
        </div>

        <!-- Certifications Stat -->
        <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="text-xs font-medium text-slate-400">Sertifikasi</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-amber-400">{{ $stats['certifications_count'] }}</span>
                <span class="text-xs text-slate-500">sertifikat</span>
            </div>
        </div>
    </div>

    <!-- Master Profile Modules Grid -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Modul Master Profile
            </h2>
            <span class="text-xs text-slate-400">Klik modul untuk menambah atau mengedit data</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Profile Details Card -->
            <a href="{{ route('profile.edit') }}" class="glass-panel-interactive p-6 rounded-2xl group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 group-hover:scale-110 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-indigo-300 transition-colors">Personal Details & Bio</h3>
                        <p class="text-xs text-slate-400 leading-relaxed mt-1">Nama lengkap, kontak, lokasi, link LinkedIn/GitHub, dan ringkasan eksekutif.</p>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-indigo-400 font-semibold group-hover:translate-x-1 transition-transform">
                    <span>Kelola Profil &rarr;</span>
                    <span class="text-slate-500 font-normal">{{ $profile ? $profile->full_name : 'Belum diisi' }}</span>
                </div>
            </a>

            <!-- Work Experiences Card -->
            <a href="{{ route('experiences.index') }}" class="glass-panel-interactive p-6 rounded-2xl group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 group-hover:scale-110 group-hover:bg-purple-500 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-purple-300 transition-colors">Pengalaman Kerja</h3>
                        <p class="text-xs text-slate-400 leading-relaxed mt-1">Perusahaan, posisi/jabatan, tanggal, serta poin-poin pencapaian kerja.</p>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-purple-400 font-semibold group-hover:translate-x-1 transition-transform">
                    <span>Kelola Pengalaman &rarr;</span>
                    <span class="text-slate-500 font-normal">{{ $stats['experiences_count'] }} Entri</span>
                </div>
            </a>

            <!-- Projects Card -->
            <a href="{{ route('projects.index') }}" class="glass-panel-interactive p-6 rounded-2xl group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 group-hover:scale-110 group-hover:bg-sky-500 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-sky-300 transition-colors">Proyek & Portofolio</h3>
                        <p class="text-xs text-slate-400 leading-relaxed mt-1">Proyek sampingan/utama, teknologi yang dipakai, dan deskripsi kontribusi.</p>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-sky-400 font-semibold group-hover:translate-x-1 transition-transform">
                    <span>Kelola Proyek &rarr;</span>
                    <span class="text-slate-500 font-normal">{{ $stats['projects_count'] }} Proyek</span>
                </div>
            </a>

            <!-- Education Card -->
            <a href="{{ route('education.index') }}" class="glass-panel-interactive p-6 rounded-2xl group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-110 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-emerald-300 transition-colors">Pendidikan</h3>
                        <p class="text-xs text-slate-400 leading-relaxed mt-1">Institusi pendidikan, gelar akademik, jurusan, IPK, dan tahun studi.</p>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-emerald-400 font-semibold group-hover:translate-x-1 transition-transform">
                    <span>Kelola Pendidikan &rarr;</span>
                    <span class="text-slate-500 font-normal">{{ $stats['education_count'] }} Riwayat</span>
                </div>
            </a>

            <!-- Skills Card -->
            <a href="{{ route('skills.index') }}" class="glass-panel-interactive p-6 rounded-2xl group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-400 group-hover:scale-110 group-hover:bg-teal-500 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-teal-300 transition-colors">Skill Inventory</h3>
                        <p class="text-xs text-slate-400 leading-relaxed mt-1">Daftar keahlian teknis, pemrosesan tools, kategori, dan level kompetensi.</p>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-teal-400 font-semibold group-hover:translate-x-1 transition-transform">
                    <span>Kelola Skills &rarr;</span>
                    <span class="text-slate-500 font-normal">{{ $stats['skills_count'] }} Skill</span>
                </div>
            </a>

            <!-- Certifications Card -->
            <a href="{{ route('certifications.index') }}" class="glass-panel-interactive p-6 rounded-2xl group flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 group-hover:scale-110 group-hover:bg-amber-500 group-hover:text-white transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-amber-300 transition-colors">Sertifikasi & Lisensi</h3>
                        <p class="text-xs text-slate-400 leading-relaxed mt-1">Sertifikat profesional, lembaga penerbit, lisensi, dan tahun perolehan.</p>
                    </div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-amber-400 font-semibold group-hover:translate-x-1 transition-transform">
                    <span>Kelola Sertifikasi &rarr;</span>
                    <span class="text-slate-500 font-normal">{{ $stats['certifications_count'] }} Sertifikat</span>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

