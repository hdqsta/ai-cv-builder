<?php

namespace App\Console\Commands;

use App\Services\DocEmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class IndexDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan docs/ directory, chunk markdown files by H2 headings, generate embeddings, and save to storage/app/docs_index.json';

    /**
     * Execute the console command.
     *
     * @param DocEmbeddingService $embeddingService
     * @return int
     */
    public function handle(DocEmbeddingService $embeddingService): int
    {
        $docsDir = base_path('docs');

        if (!File::isDirectory($docsDir)) {
            $this->error("Directory {$docsDir} does not exist.");
            return Command::FAILURE;
        }

        $this->info("Scanning markdown files in {$docsDir}...");

        $finder = new Finder();
        $finder->files()
            ->in($docsDir)
            ->name('*.md')
            ->exclude([
                'after_sesion',
                'implementation_plan',
                'prompt_history',
                'walkthrough',
            ]);

        if (!$finder->hasResults()) {
            $this->warn("No .md files found in {$docsDir}.");
            return Command::SUCCESS;
        }

        $chunks = [];
        $totalFiles = 0;

        foreach ($finder as $file) {
            $totalFiles++;
            $relativePath = str_replace('\\', '/', 'docs/' . $file->getRelativePathname());
            $rawContent = $file->getContents();

            $extractedChunks = $this->chunkMarkdownByH2($relativePath, $rawContent);
            foreach ($extractedChunks as $chunk) {
                $chunks[] = $chunk;
            }
        }

        $this->info("Found {$totalFiles} markdown files and extracted " . count($chunks) . " total chunks.");
        $this->info("Generating embeddings via Gemini API...");

        $indexedData = [];
        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $index => $chunk) {
            try {
                $textToEmbed = "Source: {$chunk['source']}\nHeading: {$chunk['heading']}\n\n{$chunk['content']}";
                $embedding = $embeddingService->embed($textToEmbed);

                $indexedData[] = [
                    'source'    => $chunk['source'],
                    'heading'   => $chunk['heading'],
                    'content'   => $chunk['content'],
                    'embedding' => $embedding,
                ];
            } catch (\Exception $e) {
                $this->error("\nFailed embedding for chunk #{$index} [{$chunk['source']} - {$chunk['heading']}]: " . $e->getMessage());
            }

            $bar->advance();
            // Small delay to prevent strict API rate limits if necessary
            usleep(100000); // 100ms
        }

        $bar->finish();
        $this->newLine(2);

        $outputPath = storage_path('app/docs_index.json');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, json_encode($indexedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Successfully created index with " . count($indexedData) . " embedded chunks!");
        $this->info("Index file saved to: {$outputPath}");

        return Command::SUCCESS;
    }

    /**
     * Chunk markdown content split by H2 (##) headings.
     *
     * @param string $source
     * @param string $rawContent
     * @return array
     */
    private function chunkMarkdownByH2(string $source, string $rawContent): array
    {
        $chunks = [];
        // Split content by H2 headers (## Header Title)
        $parts = preg_split('/^(##\s+.+)$/m', $rawContent, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (empty($parts)) {
            return [];
        }

        // Preamble before the first H2 heading
        $preamble = trim($parts[0]);
        if (mb_strlen($preamble) >= 20) {
            // Extract H1 title if available
            $heading = 'Overview';
            if (preg_match('/^#\s+(.+)$/m', $preamble, $matches)) {
                $heading = trim($matches[1]);
            }

            $chunks[] = [
                'source'  => $source,
                'heading' => $heading,
                'content' => $preamble,
            ];
        }

        // Subsequent H2 sections
        for ($i = 1; $i < count($parts); $i += 2) {
            $headingLine = $parts[$i] ?? '';
            $sectionContent = $parts[$i + 1] ?? '';

            $heading = trim(preg_replace('/^##\s+/', '', $headingLine));
            $content = trim($headingLine . "\n\n" . trim($sectionContent));

            if (mb_strlen($content) >= 20) {
                $chunks[] = [
                    'source'  => $source,
                    'heading' => $heading,
                    'content' => $content,
                ];
            }
        }

        return $chunks;
    }
}
