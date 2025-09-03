<?php

namespace Tests\Performance;

use App\Models\Translation;
use App\Models\Tag;
use App\Services\TranslationService;
use App\Services\TranslationExportService;
use App\Repositories\TranslationRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group performance
 */
class TranslationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private TranslationService $translationService;
    private TranslationExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translationService = new TranslationService(new TranslationRepository());
        $this->exportService = new TranslationExportService(new TranslationRepository());
    }

    public function test_list_translations_performance_with_large_dataset(): void
    {
        // Create 10,000 translations with tags
        $this->createLargeDataset(10000);

        $startTime = microtime(true);
        $result = $this->translationService->getPaginated(perPage: 50);
        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(200, $executionTime,
            "List endpoint should respond in < 200ms, took {$executionTime}ms");
        $this->assertEquals(50, count($result->items()));
    }

    public function test_search_performance_with_large_dataset(): void
    {
        $this->createLargeDataset(5000);

        $startTime = microtime(true);
        $result = $this->translationService->search(
            locale: 'en',
            content: 'search',
            perPage: 50
        );
        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(300, $executionTime,
            "Search endpoint should respond in < 300ms, took {$executionTime}ms");
    }

    public function test_export_performance_with_large_dataset(): void
    {
        $this->createLargeDataset(50000);

        $startTime = microtime(true);
        $result = $this->exportService->export(locale: 'en', format: 'flat');
        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $executionTime,
            "Export endpoint should respond in < 500ms, took {$executionTime}ms");
        $this->assertArrayHasKey('data', $result);
        $this->assertGreaterThan(1000, count($result['data']));
    }

    public function test_create_performance(): void
    {
        $iterations = 100;
        $totalTime = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $this->translationService->create(
                key: "performance.test.{$i}",
                locale: 'en',
                content: "Performance test content {$i}",
                tags: ['performance', 'test']
            );

            $totalTime += (microtime(true) - $startTime) * 1000;
        }

        $averageTime = $totalTime / $iterations;
        $this->assertLessThan(50, $averageTime,
            "Average create time should be < 50ms, was {$averageTime}ms");
    }

    public function test_memory_usage_stays_reasonable(): void
    {
        $initialMemory = memory_get_usage(true);

        $this->createLargeDataset(1000);

        $this->translationService->getPaginated(perPage: 100);
        $this->exportService->export(format: 'flat');

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        $this->assertLessThan(100, $memoryIncrease,
            "Memory increase should be < 100MB, was {$memoryIncrease}MB");
    }

    private function createLargeDataset(int $count): void
    {
        $tags = Tag::factory()->count(20)->create();
        $locales = ['en', 'fr', 'es', 'de', 'it'];

        $translations = [];
        for ($i = 0; $i < $count; $i++) {
            $translations[] = [
                'key' => "key.{$i}",
                'locale' => $locales[$i % count($locales)],
                'content' => "Content for translation {$i} with search terms",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert for better performance in tests
        collect($translations)->chunk(1000)->each(function ($chunk) {
            Translation::insert($chunk->toArray());
        });

        // Attach random tags to some translations
        Translation::inRandomOrder()->limit($count / 4)->get()->each(function ($translation) use ($tags) {
            $translation->tags()->attach($tags->random(rand(1, 3)));
        });
    }
}
