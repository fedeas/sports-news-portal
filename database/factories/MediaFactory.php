<?php

namespace Database\Factories;

use App\Enums\MediaCollection;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'path' => 'media/'.now()->format('Y/m').'/'.Str::uuid().'.jpg',
            'original_filename' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 500_000),
            'width' => 1600,
            'height' => 900,
            'alt_text' => fake()->sentence(4),
            'collection' => MediaCollection::Default,
            'variants' => null,
            'uploaded_by' => null,
        ];
    }

    /**
     * Indicate that the responsive WebP variants have been generated.
     */
    public function withVariants(): static
    {
        return $this->afterMaking(function (Media $media) {
            $basePath = Str::beforeLast($media->path, '.');

            $media->variants = collect([480, 800, 1200])
                ->mapWithKeys(fn (int $width) => [$width => ['path' => "{$basePath}-{$width}.webp", 'format' => 'webp']])
                ->all();
        });
    }
}
