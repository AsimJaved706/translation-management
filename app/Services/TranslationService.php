<?php

namespace App\Services;

use App\Models\Translation;
use App\Repositories\TranslationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    public function __construct(
        private TranslationRepository $repository
    ) {}

    /**
     * Get paginated translations with optional filters.
     */
    public function getPaginated(
        ?string $locale = null,
        ?string $tags = null,
        int $perPage = 50
    ): LengthAwarePaginator {
        $startTime = microtime(true);

        $tagArray = $tags ? array_map('trim', explode(',', $tags)) : null;

        $translations = $this->repository->getPaginatedWithFilters(
            locale: $locale,
            tags: $tagArray,
            perPage: $perPage
        );

        $this->logPerformance('getPaginated', $startTime);

        return $translations;
    }

    /**
     * Create a new translation.
     */
    public function create(
        string $key,
        string $locale,
        string $content,
        array $tags = []
    ): Translation {
        $startTime = microtime(true);

        $translation = $this->repository->create([
            'key' => $key,
            'locale' => $locale,
            'content' => $content,
        ]);

        if (!empty($tags)) {
            $translation->syncTags($tags);
            $translation->load('tags');
        }

        // Clear relevant caches
        $this->clearTranslationCaches($locale, $tags);

        $this->logPerformance('create', $startTime);

        return $translation;
    }

    /**
     * Find a translation by ID.
     */
    public function findById(int $id): Translation
    {
        $translation = $this->repository->findWithTags($id);

        if (!$translation) {
            throw new ModelNotFoundException('Translation not found');
        }

        return $translation;
    }

    /**
     * Update an existing translation.
     */
    public function update(
        int $id,
        ?string $key = null,
        ?string $locale = null,
        ?string $content = null,
        ?array $tags = null
    ): Translation {
        $startTime = microtime(true);

        $translation = $this->findById($id);
        $oldLocale = $translation->locale;
        $oldTags = $translation->tags->pluck('name')->toArray();

        $updateData = array_filter([
            'key' => $key,
            'locale' => $locale,
            'content' => $content,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $translation = $this->repository->update($id, $updateData);
        }

        if ($tags !== null) {
            $translation->syncTags($tags);
            $translation->load('tags');
        }

        // Clear caches for both old and new values
        $this->clearTranslationCaches($oldLocale, $oldTags);
        $this->clearTranslationCaches($locale ?? $oldLocale, $tags ?? $oldTags);

        $this->logPerformance('update', $startTime);

        return $translation;
    }

    /**
     * Delete a translation.
     */
    public function delete(int $id): bool
    {
        $translation = $this->findById($id);
        $locale = $translation->locale;
        $tags = $translation->tags->pluck('name')->toArray();

        $result = $this->repository->delete($id);

        if ($result) {
            $this->clearTranslationCaches($locale, $tags);
        }

        return $result;
    }

    /**
     * Search translations with multiple criteria.
     */
    public function search(
        ?string $locale = null,
        ?string $tags = null,
        ?string $key = null,
        ?string $content = null,
        int $perPage = 50
    ): LengthAwarePaginator {
        $startTime = microtime(true);

        $tagArray = $tags ? array_map('trim', explode(',', $tags)) : null;

        $translations = $this->repository->search(
            locale: $locale,
            tags: $tagArray,
            key: $key,
            content: $content,
            perPage: $perPage
        );

        $this->logPerformance('search', $startTime);

        return $translations;
    }

    /**
     * Clear translation-related caches.
     */
    private function clearTranslationCaches(?string $locale, array $tags): void
    {
        $cacheKeys = [
            "translations:export:all",
            "translations:export:{$locale}",
        ];

        foreach ($tags as $tag) {
            $cacheKeys[] = "translations:export:{$locale}:tag:{$tag}";
            $cacheKeys[] = "translations:export:tag:{$tag}";
        }

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Log performance metrics.
     */
    private function logPerformance(string $operation, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        Log::info("TranslationService::{$operation} executed", [
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        ]);
    }
}
