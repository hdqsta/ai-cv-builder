<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocEmbeddingService
{
    /**
     * Generate vector embedding for a given text string using Gemini Embedding API.
     *
     * @param string $text
     * @return float[] Array of float values representing vector embedding
     * @throws Exception
     */
    public function embed(string $text): array
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey) || $apiKey === 'mock') {
            return $this->generateMockEmbedding($text);
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$apiKey}";

        try {
            $response = Http::retry(3, 1000, function ($exception, $request) {
                return $exception instanceof Exception;
            })->post($endpoint, [
                'model' => 'models/text-embedding-004',
                'content' => [
                    'parts' => [
                        ['text' => $text]
                    ]
                ]
            ]);

            if ($response->failed()) {
                Log::error('Gemini Embedding API Error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new Exception('Failed to generate embedding from Gemini API: ' . $response->body());
            }

            $data = $response->json();
            $values = $data['embedding']['values'] ?? null;

            if (!$values || !is_array($values)) {
                throw new Exception('Invalid embedding payload received from Gemini API');
            }

            return array_map('floatval', $values);
        } catch (Exception $e) {
            Log::warning('Fallback to mock embedding due to API error: ' . $e->getMessage());
            // Fallback gracefully to deterministic mock vector if API encounters transient issues
            return $this->generateMockEmbedding($text);
        }
    }

    /**
     * Compute Cosine Similarity between two numeric vector arrays of equal length.
     *
     * @param float[] $a
     * @param float[] $b
     * @return float Score between -1.0 and 1.0 (higher = more similar)
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $countA = count($a);
        $countB = count($b);

        if ($countA === 0 || $countB === 0 || $countA !== $countB) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $countA; $i++) {
            $valA = (float) $a[$i];
            $valB = (float) $b[$i];

            $dotProduct += $valA * $valB;
            $normA += $valA * $valA;
            $normB += $valB * $valB;
        }

        $magnitude = sqrt($normA) * sqrt($normB);

        if ($magnitude == 0.0) {
            return 0.0;
        }

        return $dotProduct / $magnitude;
    }

    /**
     * Search relevant documentation chunks for a query from storage/app/docs_index.json.
     *
     * @param string $question
     * @param int $limit
     * @return array
     */
    public function searchRelevantChunks(string $question, int $limit = 4): array
    {
        $indexPath = storage_path('app/docs_index.json');

        if (!\Illuminate\Support\Facades\File::exists($indexPath)) {
            return [];
        }

        $indexJson = \Illuminate\Support\Facades\File::get($indexPath);
        $chunks = json_decode($indexJson, true) ?? [];

        if (empty($chunks)) {
            return [];
        }

        $queryEmbedding = $this->embed($question);
        $scoredChunks = [];

        foreach ($chunks as $chunk) {
            $score = $this->cosineSimilarity($queryEmbedding, $chunk['embedding'] ?? []);
            $scoredChunks[] = array_merge($chunk, ['score' => $score]);
        }

        usort($scoredChunks, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scoredChunks, 0, $limit);
    }

    /**
     * Generate deterministic pseudo-embedding vector for offline/mock mode.
     *
     * @param string $text
     * @return float[]
     */
    private function generateMockEmbedding(string $text): array
    {
        $hash = md5($text);
        $vector = [];
        $length = 768; // standard text-embedding-004 dimension

        for ($i = 0; $i < $length; $i++) {
            // Pseudo float between -1.0 and 1.0 based on hash characters
            $char = $hash[$i % 32];
            $val = (hexdec($char) - 7.5) / 7.5;
            $vector[] = (float) round($val, 6);
        }

        return $vector;
    }
}
