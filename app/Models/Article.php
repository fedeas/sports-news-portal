<?php

namespace App\Models;

use App\Concerns\FlushesContentCache;
use App\Concerns\RecordsSlugHistory;
use App\Enums\ArticleStatus;
use App\Services\ContentCache;
use Carbon\CarbonImmutable;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $category_id
 * @property int $author_id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body Purified HTML
 * @property int|null $featured_image_id
 * @property int|null $og_image_id
 * @property ArticleStatus $status
 * @property CarbonImmutable|null $published_at
 * @property bool $is_breaking
 * @property bool $is_featured
 * @property int $view_count
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $canonical_url
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'category_id', 'author_id', 'title', 'slug', 'excerpt', 'body',
    'is_breaking', 'is_featured', 'meta_title', 'meta_description', 'canonical_url',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use FlushesContentCache, HasFactory, RecordsSlugHistory, SoftDeletes;

    /**
     * How long a shared preview link keeps working.
     */
    public const PREVIEW_LINK_HOURS = 24;

    /**
     * What an article card shows besides the article itself: its section, its photograph and its writer.
     *
     * @var list<string>
     */
    public const CARD_RELATIONS = ['category.parent', 'featuredImage', 'author'];

    /**
     * @return array<int, string>
     */
    protected static function contentCacheTags(): array
    {
        return [ContentCache::ARTICLES];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
            'is_breaking' => 'boolean',
            'is_featured' => 'boolean',
            'view_count' => 'integer',
        ];
    }

    /**
     * Articles visible on the public site.
     *
     * @param  Builder<Article>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)->where('published_at', '<=', now());
    }

    /**
     * Articles the user wrote: created by them in the CMS or bylined to their author profile.
     *
     * @param  Builder<Article>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('created_by', $user->id)
            ->orWhereHas('author', fn (Builder $query) => $query->where('user_id', $user->id)));
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->created_by === $user->id || $this->author?->user_id === $user->id;
    }

    /**
     * The public URL: /{category}/{slug}, using the article's own (sub)category slug.
     */
    public function url(): string
    {
        return route('categories.path', [$this->category->slug, $this->slug]);
    }

    /**
     * A signed, expiring link that shows the article whatever its status, for sharing drafts before publishing.
     */
    public function previewUrl(): string
    {
        return URL::temporarySignedRoute('articles.preview', now()->addHours(self::PREVIEW_LINK_HOURS), ['article' => $this->id]);
    }

    /**
     * The meta description, falling back to the excerpt and then the start of the body text.
     */
    public function metaDescription(): string
    {
        return $this->meta_description
            ?? $this->excerpt
            ?? Str::limit(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->body)))), 160);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The primary byline.
     *
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * @return BelongsToMany<Author, $this>
     */
    public function coAuthors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'article_author')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Teams the article is about.
     *
     * @return MorphToMany<Team, $this>
     */
    public function teams(): MorphToMany
    {
        return $this->morphedByMany(Team::class, 'subject', 'article_subjects')->withTimestamps();
    }

    /**
     * Leagues the article is about.
     *
     * @return MorphToMany<League, $this>
     */
    public function leagues(): MorphToMany
    {
        return $this->morphedByMany(League::class, 'subject', 'article_subjects')->withTimestamps();
    }

    /**
     * The article's teams that have a public page, by name.
     *
     * @return MorphToMany<Team, $this>
     */
    public function activeTeams(): MorphToMany
    {
        return $this->teams()->active()->orderBy('name');
    }

    /**
     * The article's leagues that have a public page, by name.
     *
     * @return MorphToMany<League, $this>
     */
    public function activeLeagues(): MorphToMany
    {
        return $this->leagues()->active()->orderBy('name');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
