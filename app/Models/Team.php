<?php

namespace App\Models;

use App\Concerns\FlushesContentCache;
use App\Concerns\HasCountry;
use App\Concerns\RecordsSlugHistory;
use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property int $sport_id
 * @property string $name
 * @property string $slug
 * @property string|null $short_name
 * @property string|null $country ISO 3166-1 alpha-2
 * @property int|null $founded_year
 * @property int|null $logo_media_id
 * @property string|null $external_source
 * @property string|null $external_id
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['sport_id', 'name', 'slug', 'short_name', 'country', 'founded_year', 'external_source', 'external_id', 'is_active'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use FlushesContentCache, HasCountry, HasFactory, RecordsSlugHistory;

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
            'founded_year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Teams visible on the public site.
     *
     * @param  Builder<Team>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function url(): string
    {
        return route('teams.show', $this->slug);
    }

    /**
     * @return BelongsTo<Sport, $this>
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /**
     * Articles about this team.
     *
     * @return MorphToMany<Article, $this>
     */
    public function articles(): MorphToMany
    {
        return $this->morphToMany(Article::class, 'subject', 'article_subjects')->withTimestamps();
    }

    /**
     * The polymorphic article links have no foreign key, so remove them with the team.
     */
    protected static function booted(): void
    {
        static::deleting(fn (Team $team) => $team->articles()->detach());
    }
}
