<?php

namespace App\Models;

use App\Concerns\FlushesContentCache;
use App\Enums\MediaCollection;
use App\Services\ContentCache;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string|null $original_filename
 * @property string $mime_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $alt_text
 * @property MediaCollection $collection
 * @property array<int, array{path: string, format: string}>|null $variants
 * @property string|null $mediable_type
 * @property int|null $mediable_id
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['alt_text'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use FlushesContentCache, HasFactory;

    /**
     * @return array<int, string>
     */
    protected static function contentCacheTags(): array
    {
        return [ContentCache::ARTICLES, ContentCache::FIXTURES];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'collection' => MediaCollection::class,
            'variants' => 'array',
        ];
    }

    /**
     * Remove the stored files together with the record.
     */
    protected static function booted(): void
    {
        static::deleted(function (Media $media) {
            Storage::disk($media->disk)->delete($media->storedPaths());
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Paths of the original file and every generated variant.
     *
     * @return array<int, string>
     */
    public function storedPaths(): array
    {
        return [$this->path, ...array_column($this->variants ?? [], 'path')];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * The URL of the widest generated variant, or of the original while variants are pending.
     * Used for social previews, which want one reasonably sized image.
     */
    public function largestUrl(): string
    {
        $largestVariant = collect($this->variants ?? [])->sortKeys()->last();

        return Storage::disk($this->disk)->url($largestVariant['path'] ?? $this->path);
    }

    /**
     * The URL of the narrowest generated variant, or of the original while variants are pending.
     * Used for thumbnails drawn outside an `<img srcset>`, such as search suggestions.
     */
    public function smallestUrl(): string
    {
        $smallestVariant = collect($this->variants ?? [])->sortKeys()->first();

        return Storage::disk($this->disk)->url($smallestVariant['path'] ?? $this->path);
    }

    /**
     * The `srcset` attribute value built from the responsive variants; empty until they are generated.
     */
    public function srcset(): string
    {
        return collect($this->variants ?? [])
            ->sortKeys()
            ->map(fn (array $variant, int $width) => Storage::disk($this->disk)->url($variant['path'])." {$width}w")
            ->implode(', ');
    }
}
