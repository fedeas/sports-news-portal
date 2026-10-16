<?php

use App\Jobs\GenerateMediaVariantsJob;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

function storedMedia(int $width, int $height): Media
{
    $path = 'media/2026/09/photo.jpg';
    $image = UploadedFile::fake()->image('photo.jpg', $width, $height);

    Storage::disk('public')->put($path, file_get_contents($image->getRealPath()));

    return Media::factory()->create(['path' => $path, 'width' => $width, 'height' => $height]);
}

it('creates a WebP variant at each responsive width', function () {
    $media = storedMedia(2400, 1350);

    GenerateMediaVariantsJob::dispatchSync($media);

    $variants = $media->refresh()->variants;

    expect(array_keys($variants))->toBe([480, 800, 1200, 2000])
        ->and($variants[480])->toBe(['path' => 'media/2026/09/photo-480.webp', 'format' => 'webp']);

    $size = getimagesizefromstring(Storage::disk('public')->get('media/2026/09/photo-480.webp'));

    expect($size[0])->toBe(480)
        ->and($size[1])->toBe(270)
        ->and($size['mime'])->toBe('image/webp');
});

it('stops at the original width when it is below the widest size', function () {
    $media = storedMedia(1600, 900);

    GenerateMediaVariantsJob::dispatchSync($media);

    expect(array_keys($media->refresh()->variants))->toBe([480, 800, 1200, 1600]);
});

it('does not upscale images narrower than the responsive widths', function () {
    $media = storedMedia(600, 400);

    GenerateMediaVariantsJob::dispatchSync($media);

    expect(array_keys($media->refresh()->variants))->toBe([480, 600]);

    $size = getimagesizefromstring(Storage::disk('public')->get('media/2026/09/photo-600.webp'));

    expect($size[0])->toBe(600);
});

it('produces the same files when run again', function () {
    $media = storedMedia(1600, 900);

    GenerateMediaVariantsJob::dispatchSync($media);
    $firstVariants = $media->refresh()->variants;

    GenerateMediaVariantsJob::dispatchSync($media);

    expect($media->refresh()->variants)->toBe($firstVariants)
        ->and(Storage::disk('public')->allFiles())->toHaveCount(1 + count(GenerateMediaVariantsJob::WIDTHS));
});

it('leaves the media untouched when the original file is missing', function () {
    $media = Media::factory()->create();

    GenerateMediaVariantsJob::dispatchSync($media);

    expect($media->refresh()->variants)->toBeNull();
});
