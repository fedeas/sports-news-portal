<?php

namespace App\Models;

use App\Concerns\FlushesContentCache;
use App\Concerns\RecordsSlugHistory;
use App\Enums\SectionIcon;
use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $sport_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property SectionIcon|null $icon
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $sort_order
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['sport_id', 'parent_id', 'name', 'slug', 'icon', 'description', 'meta_title', 'meta_description', 'sort_order', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use FlushesContentCache, HasFactory, RecordsSlugHistory;

    /**
     * @return array<int, string>
     */
    protected static function contentCacheTags(): array
    {
        return [ContentCache::CATEGORIES, ContentCache::ARTICLES];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'icon' => SectionIcon::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Categories visible on the public site.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The public URL: /{slug} for top-level categories, /{parent}/{slug} for subcategories.
     */
    public function url(): string
    {
        return $this->parent_id === null
            ? route('categories.show', $this->slug)
            : route('categories.path', [$this->parent->slug, $this->slug]);
    }

    /**
     * The sport this category belongs to; null for cross-sport sections (e.g. Opinion).
     *
     * @return BelongsTo<Sport, $this>
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * The subsections readers can see, in menu order.
     *
     * @return HasMany<Category, $this>
     */
    public function activeChildren(): HasMany
    {
        return $this->children()->active()->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
