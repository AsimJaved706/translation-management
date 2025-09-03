<?php

namespace App\Services;

use App\Repositories\TranslationRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TranslationExportService
{
    public function __construct(
        private TranslationRepository $repository
    ) {}

    /**
     * Export translations with caching for performance.
     */
    public function export(
        ?string $locale = null,
        ?string $tags = null,
        string $format = 'flat'
    ): array {
        $startTime = microtime(true);

        $cacheKey = $this->generateCacheKey($locale, $tags, $format);

        $exportData = Cache::remember($cacheKey, 3600, function () use ($locale, $tags, $format) {
            $tagArray = $tags ? array_map('trim', explode(',', $tags)) : null;

            $translations = $this->repository->getForExport($locale, $tagArray);

            return $this->formatExportData($translations, $format);
        });

        $this->logPerformance('export', $startTime, count($exportData));

        return [
            'data' => $exportData,
            'meta' => [
                'total' => count($exportData),
                'locale' => $locale,
                'tags' => $tags ? explode(',', $tags) : null,
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Format export data based on the specified format.
     */
    private function formatExportData(Collection $translations, string $format): array
    {
        if ($format === 'nested') {
            return $this->formatNested($translations);
        }

        return $this->formatFlat($translations);
    }

    /**
     * Format translations as flat key-value pairs.
     */
    private function formatFlat(Collection $translations): array
    {
        $result = [];

        foreach ($translations as $translation) {
            $result[$translation->key] = $translation->content;
        }

        return $result;
    }

    /**
     * Format translations as nested objects based on key structure.
     */
    private function formatNested(Collection $translations): array
    {
        $result = [];

        foreach ($translations as $translation) {
            $this->setNestedValue($result, $translation->key, $translation->content);
        }

        return $result;
    }

    /**
     * Set a nested value using dot notation.
     */
    private function setNestedValue(array &$array, string $key, string $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Generate a cache key for the export.
     */
    private function generateCacheKey(?string $locale, ?string $tags, string $format): string
    {
        $parts = ['translations', 'export'];

        if ($locale) {
            $parts[] = $locale;
        }

        if ($tags) {
            $tagString = str_replace(',', '-', $tags);
            $parts[] = "tags:{$tagString}";
        }

        $parts[] = $format;

        return implode(':', $parts);
    }

    /**
     * Log performance metrics for export operations.
     */
    private function logPerformance(string $operation, float $startTime, int $recordCount): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000;

        Log::info("TranslationExportService::{$operation} executed", [
            'execution_time_ms' => round($executionTime, 2),
            'record_count' => $recordCount,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }
}
