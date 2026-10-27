<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Docs RAG Chatbot — AI CV Builder</title>
    
    <!-- Google Fonts & Tailwind CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b0f19;
            color: #f3f4f6;
        }
        .glass-card {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .prose pre {
            background-color: #111827;
            border-radius: 0.5rem;
            padding: 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
        }
        .prose code {
            color: #818cf8;
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">
    <!-- Header Navigation -->
    <header class="border-b border-gray-800 bg-gray-950/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('home') }}" class="flex items-center space-x-2 text-indigo-400 hover:text-indigo-300 font-bold transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <span class="text-gray-600">/</span>
                <span class="text-gray-200 font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                    </svg>
                    Vault Docs RAG Chatbot
                </span>
            </div>
            
            <div class="flex items-center gap-3">
                @if($isIndexed)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse mr-2"></span>
                        Index Ready ({{ $totalChunks }} chunks)
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <span class="w-2 h-2 rounded-full bg-amber-400 mr-2"></span>
                        Belum Di-index
                    </span>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto w-full px-4 py-8 flex-grow space-y-6">
        
        <!-- Hero Intro -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 relative overflow-hidden">
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    Tanya Vault Dokumentasi Project 📚
                </h1>
                <p class="mt-2 text-gray-400 text-sm sm:text-base leading-relaxed">
                    Fitur Mini-RAG ini menjawab pertanyaan secara otomatis dari file-file markdown di folder <code class="text-indigo-300 font-mono text-xs bg-gray-800 px-1.5 py-0.5 rounded">docs/</code> (PRD, ERD, Task Breakdown, dll) menggunakan <span class="text-indigo-400 font-semibold">Gemini API</span> &amp; <span class="text-indigo-400 font-semibold">Cosine Similarity</span>.
                </p>
                
                @if($isIndexed)
                    <p class="mt-3 text-xs text-gray-500 font-mono">
                        Terakhir di-index: <span class="text-gray-300">{{ $indexedAt }}</span> • Total: <span class="text-gray-300">{{ $totalChunks }} section chunks</span>
                    </p>
                @else
                    <div class="mt-4 p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-300 text-xs flex items-center justify-between">
                        <span>⚠️ Index belum dibuat. Jalankan perintah di bawah ini pada terminal:</span>
                        <code class="bg-black/40 px-2 py-1 rounded text-amber-200 font-mono">php artisan docs:index</code>
                    </div>
                @endif
            </div>
        </div>

        <!-- Question Form Card -->
        <div class="glass-card rounded-2xl p-6 shadow-xl">
            <form id="qaForm" action="{{ route('docs-qa.ask') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="question" class="block text-sm font-semibold text-gray-200 mb-2">
                        Pertanyaan Anda seputar project:
                    </label>
                    <textarea 
                        id="question" 
                        name="question" 
                        rows="3" 
                        required
                        placeholder="Contoh: Bagaimana flow ATS scoring bekerja? Atau database apa yang digunakan?"
                        class="w-full bg-gray-950/80 border border-gray-700/80 rounded-xl p-4 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-sm leading-relaxed"
                    >{{ old('question', $question ?? '') }}</textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex gap-2">
                        <button type="button" onclick="setPreset('Bagaimana flow tailoring CV bekerja?')" class="text-xs bg-gray-800/80 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-lg border border-gray-700 transition">
                            💡 Tailoring Flow
                        </button>
                        <button type="button" onclick="setPreset('Apa saja kriteria ATS scoring?')" class="text-xs bg-gray-800/80 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-lg border border-gray-700 transition">
                            💡 ATS Scoring
                        </button>
                        <button type="button" onclick="setPreset('Model AI apa saja yang didukung?')" class="text-xs bg-gray-800/80 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-lg border border-gray-700 transition">
                            💡 AI Models
                        </button>
                    </div>

                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="inline-flex items-center justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <svg id="spinner" class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="btnText">Kirim Pertanyaan</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Result Container -->
        <div id="resultContainer" class="{{ isset($answer) ? '' : 'hidden' }} space-y-6">
            
            <!-- Answer Card -->
            <div class="glass-card rounded-2xl p-6 sm:p-8 space-y-4 border-indigo-500/20">
                <div class="flex items-center justify-between border-b border-gray-800 pb-4">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span class="p-1.5 bg-indigo-500/20 text-indigo-400 rounded-lg">✨</span>
                        Jawaban Doc Chatbot
                    </h2>
                    <span class="text-xs text-gray-400 font-mono">Gemini RAG Engine</span>
                </div>
                
                <div id="answerText" class="prose prose-invert max-w-none text-gray-200 leading-relaxed text-sm sm:text-base">
                    @if(isset($answer))
                        {!! Str::markdown($answer) !!}
                    @endif
                </div>
            </div>

            <!-- Sources Card -->
            <div class="glass-card rounded-2xl p-6 border-gray-800">
                <h3 class="text-sm font-semibold text-gray-300 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    Sumber Dokumentasi Terkait (Top Similar Chunks):
                </h3>
                <div id="sourcesList" class="grid gap-3 sm:grid-cols-2">
                    @if(isset($sources))
                        @foreach($sources as $src)
                            <div class="p-3 bg-gray-950/60 rounded-xl border border-gray-800/80 text-xs flex justify-between items-start">
                                <div>
                                    <div class="font-semibold text-indigo-300 font-mono">{{ $src['file'] }}</div>
                                    <div class="text-gray-400 mt-1 font-medium">📍 Section: {{ $src['heading'] }}</div>
                                </div>
                                <span class="px-2 py-0.5 bg-gray-800 text-gray-300 rounded font-mono text-[10px]">
                                    sim: {{ round($src['score'] * 100, 1) }}%
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Error Card -->
        <div id="errorContainer" class="hidden glass-card rounded-2xl p-6 border-red-500/30 bg-red-950/20 text-red-300 text-sm">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div id="errorText"></div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-900 py-6 text-center text-xs text-gray-600">
        AI CV Builder • Vault Dokumentasi Mini-RAG Internal Tool
    </footer>

    <script>
        function setPreset(text) {
            document.getElementById('question').value = text;
        }

        document.getElementById('qaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');
            const resultContainer = document.getElementById('resultContainer');
            const errorContainer = document.getElementById('errorContainer');
            const answerText = document.getElementById('answerText');
            const sourcesList = document.getElementById('sourcesList');
            const errorText = document.getElementById('errorText');

            const question = document.getElementById('question').value.trim();
            if (!question) return;

            // Loading state
            submitBtn.disabled = true;
            spinner.classList.remove('hidden');
            btnText.innerText = 'Mencari Di Dokumentasi...';
            errorContainer.classList.add('hidden');

            try {
                const response = await fetch("{{ route('docs-qa.ask') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ question })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Terjadi kesalahan pada server');
                }

                // Render answer with Marked.js
                answerText.innerHTML = marked.parse(data.answer);

                // Render sources
                sourcesList.innerHTML = data.sources.map(src => `
                    <div class="p-3 bg-gray-950/60 rounded-xl border border-gray-800/80 text-xs flex justify-between items-start">
                        <div>
                            <div class="font-semibold text-indigo-300 font-mono">${src.file}</div>
                            <div class="text-gray-400 mt-1 font-medium">📍 Section: ${src.heading}</div>
                        </div>
                        <span class="px-2 py-0.5 bg-gray-800 text-gray-300 rounded font-mono text-[10px]">
                            sim: ${(src.score * 100).toFixed(1)}%
                        </span>
                    </div>
                `).join('');

                resultContainer.classList.remove('hidden');
                resultContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

            } catch (err) {
                errorText.innerText = err.message;
                errorContainer.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
                btnText.innerText = 'Kirim Pertanyaan';
            }
        });
    </script>
</body>
</html>
