<?php

namespace App\Models;

use App\Concerns\FlushesContentCache;
use App\Services\ContentCache;
use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $league_id
 * @property string $name e.g. "2025/2026"
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property bool $is_current
 * @property string|null $external_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['league_id', 'name', 'starts_on', 'ends_on', 'is_current', 'external_id'])]
class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use FlushesContentCache, HasFactory;

    /**
     * @return array<int, string>
     */
    protected static function contentCacheTags(): array
    {
        return [ContentCache::FIXTURES];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<League, $this>
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * The teams taking part in this season.
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('joined_at');
    }

    /**
     * @return HasMany<Fixture, $this>
     */
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    /**
     * The league table, top first.
     *
     * @return HasMany<Standing, $this>
     */
    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class)->orderBy('position');
    }
}
