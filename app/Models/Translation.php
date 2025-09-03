<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'locale',
        'content',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tags associated with this translation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Scope to filter by locale.
     */
    public function scopeByLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope to filter by key.
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', 'like', "%{$key}%");
    }

    /**
     * Scope to search in content using database-appropriate search method.
     */
    public function scopeSearchContent(Builder $query, string $content): Builder
    {
        // Use full-text search for MySQL, LIKE search for others
        if (DB::getDriverName() === 'mysql') {
            return $query->whereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$content]);
        }

        return $query->where('content', 'like', "%{$content}%");
    }

    /**
     * Scope to filter by tags.
     */
    public function scopeByTags(Builder $query, array $tagNames): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        });
    }

    /**
     * Scope to optimize queries by selecting only necessary columns.
     */
    public function scopeSelectOptimized(Builder $query): Builder
    {
        return $query->select([
            'id',
            'key',
            'locale',
            'content',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Get unique locales from all translations.
     */
    public static function getAvailableLocales(): Collection
    {
        return static::distinct('locale')
            ->pluck('locale')
            ->sort();
    }

    /**
     * Sync tags for this translation.
     */
    public function syncTags(array $tagNames): void
    {
        $tagIds = collect($tagNames)->map(function ($name) {
            return Tag::firstOrCreate(['name' => $name])->id;
        });

        $this->tags()->sync($tagIds);
    }
}
