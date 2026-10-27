<!DOCTYPE html>
<html lang="id" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'AI CV Builder' }} - Master Profile & AI Tailoring</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-full flex flex-col antialiased selection:bg-indigo-500 selection:text-white relative">

    <!-- Ambient Glow background -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-indigo-600/10 rounded-full animate-pulse-glow pointer-events-none -z-10"></div>
    <div class="fixed top-1/3 left-1/4 w-80 h-80 bg-purple-600/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

    <!-- Header Navigation -->
    <header class="glass-panel sticky top-0 z-50 border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-sky-400 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-lg font-bold tracking-tight text-white flex items-center gap-1.5">
                                AI CV Builder <span class="px-2 py-0.5 text-[10px] font-semibold tracking-wider uppercase bg-indigo-500/20 text-indigo-300 rounded-full border border-indigo-500/30">Pro</span>
                            </span>
                            <span class="text-xs text-slate-400 hidden sm:inline">Tailor & Optimize for ATS</span>
                        </div>
                    </a>

                    <!-- Primary Nav Links -->
                    <nav class="hidden lg:flex items-center gap-1">
                        <a href="{{ route('home') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 {{ request()->routeIs('home') ? 'bg-slate-800 text-white border border-slate-700/60 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </a>

                        <a href="{{ route('tailoring.create') }}" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 {{ request()->routeIs('tailoring.*') ? 'bg-gradient-to-r from-indigo-600/30 to-purple-600/30 text-indigo-200 border border-indigo-500/40 shadow-sm' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Tailor CV (JD)
                        </a>

                        <!-- Master Profile Dropdown or Items -->
                        <div class="h-4 w-px bg-slate-800 mx-1"></div>

                        <a href="{{ route('profile.edit') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('profile.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Biodata
                        </a>
                        <a href="{{ route('experiences.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('experiences.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Pengalaman
                        </a>
                        <a href="{{ route('projects.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('projects.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Proyek
                        </a>
                        <a href="{{ route('education.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('education.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Pendidikan
                        </a>
                        <a href="{{ route('skills.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('skills.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Skills
                        </a>
                        <a href="{{ route('certifications.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ request()->routeIs('certifications.*') ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/50' }}">
                            Sertifikasi
                        </a>
                    </nav>
                </div>

                <!-- Action Button -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('cv.upload.form') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 rounded-xl shadow-lg shadow-indigo-600/25 border border-indigo-400/30 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Upload CV Lama (AI)
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile Subnav Bar -->
        <div class="lg:hidden border-t border-slate-800/80 bg-slate-900/90 px-4 py-2.5 overflow-x-auto flex items-center gap-2 text-xs scrollbar-none">
            <a href="{{ route('home') }}" class="px-2.5 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('home') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">Dashboard</a>
            <a href="{{ route('profile.edit') }}" class="px-2.5 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('profile.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">Biodata</a>
            <a href="{{ route('experiences.index') }}" class="px-2.5 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('experiences.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">Pengalaman</a>
            <a href="{{ route('projects.index') }}" class="px-2.5 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('projects.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">Proyek</a>
            <a href="{{ route('education.index') }}" class="px-2.5 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('education.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">Pendidikan</a>
            <a href="{{ route('skills.index') }}" class="px-2.5 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('skills.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">Skills</a>
            <a href="{{ route('certifications.index') }}" class="px-2.5 py-1.5 rounded-md whitespace-nowrap {{ request()->routeIs('certifications.*') ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">Sertifikasi</a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        <!-- Flash Alert Messages -->
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 flex items-start gap-3 shadow-lg shadow-emerald-950/20 backdrop-blur-md">
                <svg class="w-5 h-5 text-emerald-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 text-sm font-medium">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-200 flex items-start gap-3 shadow-lg shadow-rose-950/20 backdrop-blur-md">
                <svg class="w-5 h-5 text-rose-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 text-sm font-medium">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-200 shadow-lg shadow-rose-950/20 backdrop-blur-md">
                <div class="flex items-center gap-2 font-semibold text-sm mb-2 text-rose-300">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Terdapat kesalahan input:
                </div>
                <ul class="list-disc pl-9 text-xs space-y-1 text-rose-200/90">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950/80 py-8 mt-16 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-slate-300 font-medium">Gemini 2.5 Flash Engine Active</span>
            </div>
            <div>
                &copy; {{ date('Y') }} <span class="text-slate-200 font-semibold">AI CV Builder</span>. Dioptimalkan untuk Tailoring ATS.
            </div>
        </div>
    </footer>
</body>
</html>

