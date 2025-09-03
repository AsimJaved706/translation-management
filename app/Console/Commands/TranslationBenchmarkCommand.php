<?php

namespace App\Console\Commands;

use App\Services\TranslationService;
use App\Services\TranslationExportService;
use App\Repositories\TranslationRepository;
use Illuminate\Console\Command;

class TranslationBenchmarkCommand extends Command
{
    protected $signature = 'translations:benchmark';
    protected $description = 'Run performance benchmarks on translation operations';

    public function handle(): int
    {
        $translationService = new TranslationService(new TranslationRepository());
        $exportService = new TranslationExportService(new TranslationRepository());

        $this->info('Running Translation Service Benchmarks...');
        $this->newLine();

        // Benchmark list operation
        $this->benchmark('List Translations (50 per page)', function () use ($translationService) {
            return $translationService->getPaginated(perPage: 50);
        });

        // Benchmark search operation
        $this->benchmark('Search Translations', function () use ($translationService) {
            return $translationService->search(locale: 'en', content: 'welcome');
        });

        // Benchmark export operation
        $this->benchmark('Export Translations (flat)', function () use ($exportService) {
            return $exportService->export(locale: 'en', format: 'flat');
        });

        // Benchmark export operation (nested)
        $this->benchmark('Export Translations (nested)', function () use ($exportService) {
            return $exportService->export(locale: 'en', format: 'nested');
        });

        $this->newLine();
        $this->info('Benchmarks completed!');
        return 0;
    }

    private function benchmark(string $operation, callable $callback): void
    {
        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $result = $callback();
            $end = microtime(true);
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);

        $status = $avgTime < 200 ? 'PASS' : 'SLOW';
        $color = $avgTime < 200 ? 'green' : 'yellow';

        $this->line(sprintf(
            '<fg=%s>[%s]</> %s: Avg: %.2fms, Min: %.2fms, Max: %.2fms',
            $color,
            $status,
            $operation,
            $avgTime,
            $minTime,
            $maxTime
        ));
    }
}
