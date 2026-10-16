<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class GenerateMediaVariantsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Responsive widths generated for every image, in pixels.
     */
    public const WIDTHS = [480, 800, 1200, 2000];

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public Media $media) {}

    /**
     * Write a WebP copy of the image at each responsive width and record them on the media.
     *
     * Variant paths are derived from the original path, so running the job again overwrites
     * the same files instead of creating new ones.
     */
    public function handle(ImageManager $images): void
    {
        $disk = Storage::disk($this->media->disk);
        $source = $disk->get($this->media->path);

        if ($source === null) {
            return;
        }

        $basePath = Str::beforeLast($this->media->path, '.');
        $variants = [];

        foreach ($this->widthsFor($this->media->width) as $width) {
            $path = "{$basePath}-{$width}.webp";

            $encoded = $images->decodeBinary($source)
                ->scaleDown(width: $width)
                ->encode(new WebpEncoder(quality: 80));

            $disk->put($path, $encoded->toString(), 'public');

            $variants[$width] = ['path' => $path, 'format' => 'webp'];
        }

        $this->media->forceFill(['variants' => $variants])->save();
    }

    /**
     * The responsive widths below the original, plus the largest useful width, never upscaled.
     *
     * @return array<int, int>
     */
    private function widthsFor(?int $originalWidth): array
    {
        if ($originalWidth === null) {
            return self::WIDTHS;
        }

        $widths = array_filter(self::WIDTHS, fn (int $width) => $width < $originalWidth);
        $widths[] = min($originalWidth, max(self::WIDTHS));

        return array_values(array_unique($widths));
    }
}
