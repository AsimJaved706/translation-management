<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the translations associated with this tag.
     */
    public function translations(): BelongsToMany
    {
        return $this->belongsToMany(Translation::class)->withTimestamps();
    }

    /**
     * Scope to search tags by name.
     */
    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Get tags with translation count.
     */
    public function scopeWithTranslationCount(Builder $query): Builder
    {
        return $query->withCount('translations');
    }
}
