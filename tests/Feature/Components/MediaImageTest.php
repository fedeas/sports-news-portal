<?php

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public', ['url' => 'https://cdn.test/storage']);
});

it('renders a lazy responsive image with dimensions and escaped alt text', function () {
    $media = Media::factory()->withVariants()->make([
        'path' => 'media/2026/09/photo.jpg',
        'alt_text' => '"><script>alert(1)</script>',
    ]);

    $this->blade('<x-media-image :media="$media" sizes="(min-width: 768px) 50vw, 100vw" />', ['media' => $media])
        ->assertSee('src="https://cdn.test/storage/media/2026/09/photo.jpg"', false)
        ->assertSee('srcset="https://cdn.test/storage/media/2026/09/photo-480.webp 480w', false)
        ->assertSee('sizes="(min-width: 768px) 50vw, 100vw"', false)
        ->assertSee('width="1600"', false)
        ->assertSee('height="900"', false)
        ->assertSee('loading="lazy"', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('loads eagerly without a srcset while variants are pending', function () {
    $media = Media::factory()->make(['path' => 'media/2026/09/photo.jpg']);

    $this->blade('<x-media-image :media="$media" :lazy="false" />', ['media' => $media])
        ->assertSee('loading="eager"', false)
        ->assertDontSee('srcset', false);
});
