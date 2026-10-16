<?php

use App\Jobs\GenerateMediaVariantsJob;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('queues a job per stored image', function () {
    Queue::fake();
    $media = Media::factory()->count(2)->create();

    $this->artisan('media:regenerate-variants')->assertSuccessful();

    Queue::assertPushed(GenerateMediaVariantsJob::class, 2);
    Queue::assertPushed(GenerateMediaVariantsJob::class, fn (GenerateMediaVariantsJob $job) => $job->media->is($media->first()));
});

it('adds a newly introduced width to images that already have sizes', function () {
    Storage::fake('public');
    $path = 'media/2026/09/photo.jpg';
    $image = UploadedFile::fake()->image('photo.jpg', 2400, 1350);
    Storage::disk('public')->put($path, file_get_contents($image->getRealPath()));
    $media = Media::factory()->create([
        'path' => $path,
        'width' => 2400,
        'height' => 1350,
        'variants' => [480 => ['path' => 'media/2026/09/photo-480.webp', 'format' => 'webp']],
    ]);

    $this->artisan('media:regenerate-variants', ['--sync' => true])->assertSuccessful();

    expect(array_keys($media->refresh()->variants))->toBe([480, 800, 1200, 2000]);
    Storage::disk('public')->assertExists('media/2026/09/photo-2000.webp');
});
