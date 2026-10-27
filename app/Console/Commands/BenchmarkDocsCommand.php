<?php

namespace App\Console\Commands;

use App\Services\DocEmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BenchmarkDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:benchmark';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run benchmark across 15 sample questions to compute Top-1, AvgRest, and Gap scores for RAG off-topic analysis';

    /**
     * Execute the console command.
     *
     * @param DocEmbeddingService $embeddingService
     * @return int
     */
    public function handle(DocEmbeddingService $embeddingService): int
    {
        $indexPath = storage_path('app/docs_index.json');

        if (!File::exists($indexPath)) {
            $this->error("Index file {$indexPath} does not exist. Run php artisan docs:index first.");
            return Command::FAILURE;
        }

        $chunks = json_decode(File::get($indexPath), true) ?? [];
        if (empty($chunks)) {
            $this->error("Docs index is empty.");
            return Command::FAILURE;
        }

        $questions = [
            // Category 1: Jelas On-Topic (5)
            [
                'cat' => 'Jelas On-Topic',
                'q'   => 'Bagaimana alur kerja fitur AI Tailoring CV dari Job Description?',
            ],
            [
                'cat' => 'Jelas On-Topic',
                'q'   => 'Apa saja entitas dan data yang disimpan di dalam Master Profile?',
            ],
            [
                'cat' => 'Jelas On-Topic',
                'q'   => 'Bagaimana sistem menghitung skor ATS dan kriteria apa saja yang dievaluasi?',
            ],
            [
                'cat' => 'Jelas On-Topic',
                'q'   => 'Library atau class apa yang digunakan untuk mengekstrak teks dari file PDF dan DOCX?',
            ],
            [
                'cat' => 'Jelas On-Topic',
                'q'   => 'Apa saja aturan utama terkait migrasi database SQLite di project ini?',
            ],

            // Category 2: Jelas Off-Topic (5)
            [
                'cat' => 'Jelas Off-Topic',
                'q'   => 'Bagaimana resep dan alur membuat burger daging sapi yang enak?',
            ],
            [
                'cat' => 'Jelas Off-Topic',
                'q'   => 'Siapa presiden pertama Amerika Serikat dan kapan beliau menjabat?',
            ],
            [
                'cat' => 'Jelas Off-Topic',
                'q'   => 'Bagaimana cara membuat desain resume yang menarik menggunakan template Canva?',
            ],
            [
                'cat' => 'Jelas Off-Topic',
                'q'   => 'Berapa harga estimasi tiket pesawat dari Jakarta ke Bali bulan depan?',
            ],
            [
                'cat' => 'Jelas Off-Topic',
                'q'   => 'Bagaimana cara kerja baterai dan motor listrik pada mobil Tesla?',
            ],

            // Category 3: Zona Abu-Abu (5)
            [
                'cat' => 'Zona Abu-Abu',
                'q'   => 'Bagaimana cara mengunggah dan memverifikasi transkrip nilai akademik perkuliahan?',
            ],
            [
                'cat' => 'Zona Abu-Abu',
                'q'   => 'Apakah ada fitur untuk langsung mengirimkan CV hasil tailoring ke email recruiter?',
            ],
            [
                'cat' => 'Zona Abu-Abu',
                'q'   => 'Gimana cara kerja fitur ATS scoring dan gimana caranya agar lolos wawancara kerja?',
            ],
            [
                'cat' => 'Zona Abu-Abu',
                'q'   => 'Bagaimana cara membuat cover letter otomatis dan melacak status lamaran pekerjaan?',
            ],
            [
                'cat' => 'Zona Abu-Abu',
                'q'   => 'Apakah sistem ini mendukung ekspor CV ke format SVG atau file Photoshop PSD?',
            ],
        ];

        $this->info("Running RAG similarity benchmark for 15 test questions...");

        $results = [];
        $csvRows = [];
        // CSV Header
        $csvRows[] = [
            'No',
            'Category',
            'Question',
            'Top1_Score',
            'Top2_Score',
            'Top3_Score',
            'Top4_Score',
            'Top5_Score',
            'AvgRest_Score',
            'Gap_Score',
            'Top1_Source',
            'Top1_Heading',
        ];

        $bar = $this->output->createProgressBar(count($questions));
        $bar->start();

        foreach ($questions as $index => $item) {
            $qNum = $index + 1;
            $cat = $item['cat'];
            $qText = $item['q'];

            try {
                $queryEmbedding = $embeddingService->embed($qText);

                $scored = [];
                foreach ($chunks as $chunk) {
                    $score = $embeddingService->cosineSimilarity($queryEmbedding, $chunk['embedding'] ?? []);
                    $scored[] = array_merge($chunk, ['score' => $score]);
                }

                usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
                $top5 = array_slice($scored, 0, 5);

                $s1 = round($top5[0]['score'] ?? 0, 4);
                $s2 = round($top5[1]['score'] ?? 0, 4);
                $s3 = round($top5[2]['score'] ?? 0, 4);
                $s4 = round($top5[3]['score'] ?? 0, 4);
                $s5 = round($top5[4]['score'] ?? 0, 4);

                $avgRest = round(($s2 + $s3 + $s4 + $s5) / 4, 4);
                $gap = round($s1 - $avgRest, 4);

                $topSource = $top5[0]['source'] ?? '-';
                $topHeading = $top5[0]['heading'] ?? '-';

                $results[] = [
                    'num'       => $qNum,
                    'cat'       => $cat,
                    'q'         => mb_strimwidth($qText, 0, 45, '...'),
                    'top1'      => number_format($s1, 4),
                    'avg_rest'  => number_format($avgRest, 4),
                    'gap'       => number_format($gap, 4),
                    'source'    => $topSource,
                ];

                $csvRows[] = [
                    $qNum,
                    $cat,
                    $qText,
                    $s1,
                    $s2,
                    $s3,
                    $s4,
                    $s5,
                    $avgRest,
                    $gap,
                    $topSource,
                    $topHeading,
                ];
            } catch (\Exception $e) {
                $this->error("\nFailed processing question #{$qNum}: " . $e->getMessage());
            }

            $bar->advance();
            usleep(100000); // 100ms API rate limit buffer
        }

        $bar->finish();
        $this->newLine(2);

        // Display console summary table
        $this->table(
            ['No', 'Category', 'Question', 'Top-1', 'Avg Rest (2-5)', 'Gap (Top1-Avg)', 'Top Source'],
            $results
        );

        // Save CSV file
        $csvPath = storage_path('app/rag_benchmark_results.csv');
        File::ensureDirectoryExists(dirname($csvPath));

        $handle = fopen($csvPath, 'w');
        // Write UTF-8 BOM for Microsoft Excel compatibility
        fputs($handle, "\xEF\xBB\xBF");
        foreach ($csvRows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        $this->info("Benchmark results saved successfully to Excel/CSV file:");
        $this->info($csvPath);

        return Command::SUCCESS;
    }
}
