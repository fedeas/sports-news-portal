<?php

use App\Models\Category;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public', ['url' => 'https://cdn.test/storage']);
});

it('deletes the original and variant files when deleted', function () {
    $media = Media::factory()->withVariants()->create(['path' => 'media/2026/09/photo.jpg']);
    $disk = Storage::disk('public');

    foreach (['photo.jpg', 'photo-480.webp', 'photo-800.webp', 'photo-1200.webp', 'other.jpg'] as $file) {
        $disk->put("media/2026/09/{$file}", 'x');
    }

    $media->delete();

    expect($disk->allFiles())->toBe(['media/2026/09/other.jpg']);
});

it('builds a srcset from its variants in ascending width', function () {
    $media = Media::factory()->withVariants()->make(['path' => 'media/2026/09/photo.jpg']);

    expect($media->srcset())->toBe(
        'https://cdn.test/storage/media/2026/09/photo-480.webp 480w, '
        .'https://cdn.test/storage/media/2026/09/photo-800.webp 800w, '
        .'https://cdn.test/storage/media/2026/09/photo-1200.webp 1200w'
    );
});

it('stores a short alias as the owner type', function () {
    $media = Media::factory()->create();

    $media->mediable()->associate(Category::factory()->create())->save();

    expect($media->refresh()->mediable_type)->toBe('category');
});
