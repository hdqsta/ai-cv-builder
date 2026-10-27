<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CV Assistant Agent — Gemini Function Calling</title>

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
                        agent: {
                            50: '#f0fdf4',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #080c14;
            color: #f3f4f6;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .prose pre {
            background-color: #0f172a;
            border-radius: 0.5rem;
            padding: 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
        }
        .prose code {
            color: #4ade80;
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">
    <!-- Header Navigation -->
    <header class="border-b border-gray-800 bg-gray-950/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('home') }}" class="flex items-center space-x-2 text-emerald-400 hover:text-emerald-300 font-bold transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <span class="text-gray-600">/</span>
                <a href="{{ route('docs-qa.index') }}" class="text-gray-400 hover:text-gray-200 transition text-sm">
                    Docs RAG
                </a>
                <span class="text-gray-600">/</span>
                <span class="text-gray-200 font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    CV Assistant Agent
                </span>
            </div>

            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse mr-2"></span>
                    Gemini Tool-Calling Active
                </span>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto w-full px-4 py-8 flex-grow space-y-6">

        <!-- Hero Intro -->
        <div class="glass-card rounded-2xl p-6 sm:p-8 relative overflow-hidden">
            <div class="absolute -top-12 -right-12 w-48 h-48 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                    CV Assistant Agent 🤖
                </h1>
                <p class="mt-2 text-gray-400 text-sm sm:text-base leading-relaxed">
                    Agentik AI berbasis <span class="text-emerald-400 font-semibold">Gemini Function Calling</span> yang secara mandiri dapat memutuskan pemanggilan tools:
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 text-xs font-mono">
                    <div class="p-3 bg-gray-900/80 rounded-xl border border-gray-800">
                        <span class="text-emerald-400 font-bold">🛠️ query_docs(question)</span>
                        <p class="text-gray-400 font-sans mt-1">Mencari spesifikasi teknis di RAG Indexing (<code class="text-gray-300">PRD, ERD, Nodes</code>).</p>
                    </div>
                    <div class="p-3 bg-gray-900/80 rounded-xl border border-gray-800">
                        <span class="text-emerald-400 font-bold">📊 get_master_profile_summary()</span>
                        <p class="text-gray-400 font-sans mt-1">Mengambil data &amp; kelengkapan profil aktual user di database.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Form Card -->
        <div class="glass-card rounded-2xl p-6 shadow-xl">
            <form id="agentForm" action="{{ route('agent-qa.ask') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="question" class="block text-sm font-semibold text-gray-200 mb-2">
                        Pertanyaan untuk Agent:
                    </label>
                    <textarea
                        id="question"
                        name="question"
                        rows="3"
                        required
                        placeholder="Contoh: Jelasin skema database Master Profile berdasarkan dokumentasi, terus kasih tau juga apakah data master profile gue sekarang udah lengkap sesuai skema itu."
                        class="w-full bg-gray-950/90 border border-gray-700/80 rounded-xl p-4 text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition text-sm leading-relaxed"
                    >{{ old('question', $question ?? '') }}</textarea>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="setPreset('Jelasin skema database Master Profile berdasarkan dokumentasi, terus kasih tau juga apakah data master profile gue sekarang udah lengkap sesuai skema itu.')" class="text-xs bg-emerald-950/40 hover:bg-emerald-900/50 text-emerald-300 px-3 py-1.5 rounded-lg border border-emerald-800/50 transition">
                            🔥 Test Case: Butuh 2 Tools Sekaligus
                        </button>
                        <button type="button" onclick="setPreset('Apakah data Master Profile saya sudah lengkap untuk digunakan tailoring CV?')" class="text-xs bg-gray-800/80 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-lg border border-gray-700 transition">
                            📊 Cek Master Profile User
                        </button>
                        <button type="button" onclick="setPreset('Bagaimana aturan migrasi database SQLite di project ini?')" class="text-xs bg-gray-800/80 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-lg border border-gray-700 transition">
                            📚 Cek Dokumentasi Docs
                        </button>
                    </div>

                    <button
                        type="submit"
                        id="submitBtn"
                        class="inline-flex items-center justify-center px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl shadow-lg shadow-emerald-600/30 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm shrink-0"
                    >
                        <svg id="spinner" class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="btnText">Jalankan Agent</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Result Container -->
        <div id="resultContainer" class="{{ isset($answer) ? '' : 'hidden' }} space-y-6">

            <!-- Tool Execution Trace Timeline -->
            <div class="glass-card rounded-2xl p-6 border-emerald-500/30">
                <div class="flex items-center justify-between border-b border-gray-800 pb-3 mb-4">
                    <h3 class="text-sm font-bold text-emerald-400 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Agent Execution Trace (Tool Calls Timeline)
                    </h3>
                    <span id="iterationsBadge" class="text-xs font-mono px-2 py-0.5 rounded bg-gray-800 text-gray-300">
                        @if(isset($iterations)) Iterasi: {{ $iterations }} @endif
                    </span>
                </div>

                <div id="tracesList" class="space-y-3 font-mono text-xs">
                    @if(isset($traces))
                        @foreach($traces as $t)
                            <div class="p-3 bg-gray-950/80 rounded-xl border border-gray-800/80 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-emerald-400 font-bold">
                                        Step #{{ $t['iteration'] }}: {{ $t['tool'] }}()
                                    </span>
                                    <span class="text-gray-500 text-[10px]">EXECUTED</span>
                                </div>
                                <div class="text-gray-400">
                                    <span class="text-gray-500">Args:</span> {{ json_encode($t['args'], JSON_UNESCAPED_UNICODE) }}
                                </div>
                                <div class="text-gray-300 bg-gray-900/60 p-2 rounded text-[11px] leading-relaxed border border-gray-800">
                                    <span class="text-gray-500 block text-[10px] mb-0.5">Result Summary:</span>
                                    {{ $t['result'] }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Answer Card -->
            <div class="glass-card rounded-2xl p-6 sm:p-8 space-y-4 border-gray-800">
                <div class="flex items-center justify-between border-b border-gray-800 pb-4">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span class="p-1.5 bg-emerald-500/20 text-emerald-400 rounded-lg">💬</span>
                        Jawaban Final Agent
                    </h2>
                    <span class="text-xs text-gray-400 font-mono">Gemini Tool-Calling Engine</span>
                </div>

                <div id="answerText" class="prose prose-invert max-w-none text-gray-200 leading-relaxed text-sm sm:text-base">
                    @if(isset($answer))
                        {!! Str::markdown($answer) !!}
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
        AI CV Builder • CV Assistant Agent Internal Tool
    </footer>

    <script>
        function setPreset(text) {
            document.getElementById('question').value = text;
        }

        document.getElementById('agentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');
            const resultContainer = document.getElementById('resultContainer');
            const errorContainer = document.getElementById('errorContainer');
            const answerText = document.getElementById('answerText');
            const tracesList = document.getElementById('tracesList');
            const iterationsBadge = document.getElementById('iterationsBadge');
            const errorText = document.getElementById('errorText');

            const question = document.getElementById('question').value.trim();
            if (!question) return;

            // Loading state
            submitBtn.disabled = true;
            spinner.classList.remove('hidden');
            btnText.innerText = 'Agent Berpikir & Memanggil Tools...';
            errorContainer.classList.add('hidden');

            try {
                const response = await fetch("{{ route('agent-qa.ask') }}", {
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

                // Render iterations badge
                iterationsBadge.innerText = `Iterasi: ${data.iterations}`;

                // Render traces
                if (data.traces && data.traces.length > 0) {
                    tracesList.innerHTML = data.traces.map(t => `
                        <div class="p-3 bg-gray-950/80 rounded-xl border border-gray-800/80 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-emerald-400 font-bold">
                                    Step #${t.iteration}: ${t.tool}()
                                </span>
                                <span class="text-gray-500 text-[10px]">EXECUTED</span>
                            </div>
                            <div class="text-gray-400">
                                <span class="text-gray-500">Args:</span> ${JSON.stringify(t.args)}
                            </div>
                            <div class="text-gray-300 bg-gray-900/60 p-2 rounded text-[11px] leading-relaxed border border-gray-800">
                                <span class="text-gray-500 block text-[10px] mb-0.5">Result Summary:</span>
                                ${t.result}
                            </div>
                        </div>
                    `).join('');
                } else {
                    tracesList.innerHTML = `<div class="text-gray-500 italic">Agent langsung memberikan jawaban tanpa memanggil tools tambahan.</div>`;
                }

                resultContainer.classList.remove('hidden');
                resultContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

            } catch (err) {
                errorText.innerText = err.message;
                errorContainer.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
                btnText.innerText = 'Jalankan Agent';
            }
        });
    </script>
</body>
</html>
