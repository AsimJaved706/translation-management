<?php

namespace App\Repositories;

use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TranslationRepositoryInterface
{
    public function getPaginatedWithFilters(
        ?string $locale = null,
        ?array $tags = null,
        int $perPage = 50
    ): LengthAwarePaginator;

    public function create(array $data): Translation;

    public function findWithTags(int $id): ?Translation;

    public function update(int $id, array $data): Translation;

    public function delete(int $id): bool;

    public function search(
        ?string $locale = null,
        ?array $tags = null,
        ?string $key = null,
        ?string $content = null,
        int $perPage = 50
    ): LengthAwarePaginator;

    public function getForExport(?string $locale = null, ?array $tags = null): Collection;

    public function getStatistics(): array;

    public function bulkInsert(array $translations): bool;

    public function exists(string $key, string $locale): bool;

    public function getStaleTranslations(int $hoursOld = 24): Collection;
}
