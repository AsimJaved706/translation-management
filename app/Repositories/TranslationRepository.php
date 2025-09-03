<?php

namespace App\Repositories;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TranslationRepository implements TranslationRepositoryInterface
{
    /**
     * Get paginated translations with optional filters.
     */
    public function getPaginatedWithFilters(
        ?string $locale = null,
        ?array $tags = null,
        int $perPage = 50
    ): LengthAwarePaginator {
        $query = $this->buildBaseQuery();

        $this->applyFilters($query, $locale, $tags);

        return $query->with('tags:id,name')
            ->selectOptimized()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new translation.
     */
    public function create(array $data): Translation
    {
        return Translation::create($data);
    }

    /**
     * Find translation by ID with tags loaded.
     */
    public function findWithTags(int $id): ?Translation
    {
        return Translation::with('tags:id,name')->find($id);
    }

    /**
     * Update translation by ID.
     */
    public function update(int $id, array $data): Translation
    {
        $translation = Translation::findOrFail($id);
        $translation->update($data);
        $translation->load('tags:id,name');

        return $translation;
    }

    /**
     * Delete translation by ID.
     */
    public function delete(int $id): bool
    {
        $translation = Translation::findOrFail($id);
        return $translation->delete();
    }

    /**
     * Search translations with multiple criteria.
     */
   /**
     * Search translations with multiple criteria.
     */
    public function search(
        ?string $locale = null,
        ?array $tags = null,
        ?string $key = null,
        ?string $content = null,
        int $perPage = 50
    ): LengthAwarePaginator {
        $query = $this->buildBaseQuery();

        $this->applyFilters($query, $locale, $tags);

        if ($key) {
            $query->byKey($key);
        }

        if ($content) {
            $query->searchContent($content);
        }

        // Order by relevance for MySQL, by created_at for others
        if ($content && \Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            $orderBy = 'MATCH(content) AGAINST(?) DESC';
            $orderParams = [$content];
        } else {
            $orderBy = 'created_at DESC';
            $orderParams = [];
        }

        return $query->with('tags:id,name')
            ->selectOptimized()
            ->orderByRaw($orderBy, $orderParams)
            ->paginate($perPage);
    }

    /**
     * Get translations optimized for export.
     */
    public function getForExport(?string $locale = null, ?array $tags = null): Collection
    {
        $query = Translation::select(['key', 'content', 'locale']);

        if ($locale) {
            $query->byLocale($locale);
        }

        if ($tags) {
            $query->byTags($tags);
        }

        return $query->orderBy('key')->get();
    }

    /**
     * Get translation statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_translations' => Translation::count(),
            'unique_keys' => Translation::distinct('key')->count('key'),
            'locales' => Translation::distinct('locale')->pluck('locale')->sort()->values(),
            'translations_by_locale' => Translation::selectRaw('locale, COUNT(*) as count')
                ->groupBy('locale')
                ->pluck('count', 'locale'),
        ];
    }

    /**
     * Bulk insert translations for better performance.
     */
    public function bulkInsert(array $translations): bool
    {
        $chunks = array_chunk($translations, 1000);

        foreach ($chunks as $chunk) {
            Translation::insert($chunk);
        }

        return true;
    }

    /**
     * Check if translation exists for given key and locale.
     */
    public function exists(string $key, string $locale): bool
    {
        return Translation::where('key', $key)
            ->where('locale', $locale)
            ->exists();
    }

    /**
     * Get translations that need updates (older than specified time).
     */
    public function getStaleTranslations(int $hoursOld = 24): Collection
    {
        return Translation::where('updated_at', '<', now()->subHours($hoursOld))
            ->with('tags:id,name')
            ->get();
    }

    /**
     * Build the base query with common optimizations.
     */
    private function buildBaseQuery(): Builder
    {
        return Translation::query();
    }

    /**
     * Apply common filters to the query.
     */
    private function applyFilters(Builder $query, ?string $locale, ?array $tags): void
    {
        if ($locale) {
            $query->byLocale($locale);
        }

        if ($tags) {
            $query->byTags($tags);
        }
    }
}
