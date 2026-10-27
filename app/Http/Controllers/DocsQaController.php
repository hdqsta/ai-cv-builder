<?php

namespace App\Http\Controllers;

use App\Services\DocEmbeddingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DocsQaController extends Controller
{
    public function __construct(
        protected DocEmbeddingService $embeddingService
    ) {}

    /**
     * Display the Docs QA UI.
     *
     * @return View
     */
    public function index(): View
    {
        $indexPath = storage_path('app/docs_index.json');
        $isIndexed = File::exists($indexPath);
        $totalChunks = 0;
        $indexedAt = null;

        if ($isIndexed) {
            $content = File::get($indexPath);
            $data = json_decode($content, true) ?? [];
            $totalChunks = count($data);
            $indexedAt = date('Y-m-d H:i:s', File::lastModified($indexPath));
        }

        return view('docs-qa', [
            'isIndexed'   => $isIndexed,
            'totalChunks' => $totalChunks,
            'indexedAt'   => $indexedAt,
        ]);
    }

    /**
     * Process Q&A request using RAG workflow.
     *
     * @param Request $request
     * @return JsonResponse|View
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $question = trim($request->input('question'));
        $indexPath = storage_path('app/docs_index.json');

        if (!File::exists($indexPath)) {
            $errorMsg = 'Dokumentasi belum di-index! Silakan jalankan command "php artisan docs:index" di terminal terlebih dahulu.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 400);
            }
            return back()->withErrors(['question' => $errorMsg]);
        }

        $indexJson = File::get($indexPath);
        $chunks = json_decode($indexJson, true) ?? [];

        if (empty($chunks)) {
            $errorMsg = 'Index dokumen kosong. Silakan jalankan "php artisan docs:index".';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 400);
            }
            return back()->withErrors(['question' => $errorMsg]);
        }

        try {
            // 1. Generate embedding for user question
            $queryEmbedding = $this->embeddingService->embed($question);

            // 2. Compute similarity for all chunks
            $scoredChunks = [];
            foreach ($chunks as $chunk) {
                $score = $this->embeddingService->cosineSimilarity($queryEmbedding, $chunk['embedding'] ?? []);
                $scoredChunks[] = array_merge($chunk, ['score' => $score]);
            }

            // 3. Sort by score descending and take Top 4
            usort($scoredChunks, fn($a, $b) => $b['score'] <=> $a['score']);
            $topChunks = array_slice($scoredChunks, 0, 4);

            // Filter out sources list for transparency
            $sources = array_map(function ($c) {
                return [
                    'file'    => $c['source'],
                    'heading' => $c['heading'],
                    'score'   => round($c['score'], 4),
                ];
            }, $topChunks);

            // 4. Generate answer using Gemini Chat
            $answer = $this->generateAnswerFromContext($question, $topChunks);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'question' => $question,
                    'answer'   => $answer,
                    'sources'  => $sources,
                ]);
            }

            return view('docs-qa', [
                'isIndexed'   => true,
                'totalChunks' => count($chunks),
                'indexedAt'   => date('Y-m-d H:i:s', File::lastModified($indexPath)),
                'question'    => $question,
                'answer'      => $answer,
                'sources'     => $sources,
            ]);
        } catch (Exception $e) {
            Log::error('Docs QA Controller Error', ['exception' => $e->getMessage()]);
            $errorMsg = 'Terjadi kesalahan saat memproses pertanyaan: ' . $e->getMessage();
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 500);
            }
            return back()->withErrors(['question' => $errorMsg]);
        }
    }

    /**
     * Call Gemini Chat model to generate answer based strictly on provided chunks context.
     *
     * @param string $question
     * @param array $topChunks
     * @return string
     */
    private function generateAnswerFromContext(string $question, array $topChunks): string
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model', 'gemini-3.1-flash-lite');

        // Handle offline / mock mode
        if (empty($apiKey) || $apiKey === 'mock') {
            return $this->generateMockAnswer($question, $topChunks);
        }

        $contextText = "";
        foreach ($topChunks as $index => $chunk) {
            $num = $index + 1;
            $contextText .= "--- CHUNK #{$num} ---\n";
            $contextText .= "Source File: {$chunk['source']}\n";
            $contextText .= "Section: {$chunk['heading']}\n";
            $contextText .= "Content:\n{$chunk['content']}\n\n";
        }

        $prompt = <<<PROMPT
Anda adalah Assistant RAG khusus untuk Vault Dokumentasi Project AI CV Builder.
Tugas Anda adalah menjawab pertanyaan user berdasarkan Kumpulan Konteks Dokumentasi yang disediakan di bawah.

ATURAN SANGAT KETAT:
1. Jawablah secara rinci, lugas, dan terstruktur menggunakan Markdown.
2. Jawab HANYA berdasarkan konteks dokumentasi di bawah ini.
3. Apabila informasi yang ditanyakan TIDAK ADA atau TIDAK DIBAHAS dalam konteks dokumentasi di bawah, Anda MUST/WAJIB menyatakan secara jujur: "Maaf, informasi mengenai hal tersebut belum dibahas dalam dokumentasi project ini."
4. DILARANG MENGARANG JAWABAN atau menggunakan pengetahuan di luar konteks dokumentasi yang disediakan.

KONTEKS DOKUMENTASI TERSEDIA:
{$contextText}

PERTANYAAN USER:
{$question}
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        try {
            $response = Http::timeout(30)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $answerText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!empty($answerText)) {
                    return trim($answerText);
                }
            }

            Log::warning('Gemini generateContent returned fallback or empty response', ['body' => $response->body()]);
            return $this->generateMockAnswer($question, $topChunks);
        } catch (Exception $e) {
            Log::error('Gemini API call failed in DocsQaController', ['message' => $e->getMessage()]);
            return $this->generateMockAnswer($question, $topChunks);
        }
    }

    /**
     * Fallback mock answer if API key is missing or API call fails.
     */
    private function generateMockAnswer(string $question, array $topChunks): string
    {
        if (empty($topChunks)) {
            return "Maaf, tidak ada dokumentasi yang relevan dengan pertanyaan Anda.";
        }

        $top = $topChunks[0];
        return "*(Mode Demo/Offline)* Berdasarkan dokumen **{$top['source']}** pada bagian **{$top['heading']}**:\n\n" .
            mb_substr($top['content'], 0, 300) . "...\n\n" .
            "*(Catatan: Untuk jawaban AI yang presisi, pastikan GEMINI_API_KEY valid terpasang di file .env)*";
    }
}
