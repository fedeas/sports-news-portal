<?php

namespace App\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A public byline. Articles reference authors, never users, so deleting an account keeps its bylines.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $bio
 * @property int|null $photo_media_id
 * @property string|null $email
 * @property array{twitter?: string, facebook?: string, instagram?: string}|null $social_links
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'name', 'slug', 'bio', 'email', 'social_links', 'is_active'])]
class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory;

    /**
     * The social networks an author can link to.
     */
    public const SOCIAL_NETWORKS = ['twitter', 'facebook', 'instagram'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The staff account behind the byline; null for guest or syndicated bylines.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The writer's social profiles, keyed by network.
     *
     * Only http(s) links are returned, so a link stored outside the admin form (a seeder, an import)
     * can never put a `javascript:` URL in a page.
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return array_filter(
            $this->social_links ?? [],
            fn (mixed $link) => is_string($link) && preg_match('#^https?://#i', $link) === 1,
        );
    }

    /**
     * The public page of this writer.
     */
    public function url(): string
    {
        return route('authors.show', $this->slug);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    /**
     * Articles where this author is the primary byline.
     *
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
