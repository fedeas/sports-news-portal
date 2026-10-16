<?php

use App\Actions\UploadMedia;
use App\Enums\MediaCollection;
use App\Enums\UserRole;
use App\Jobs\GenerateMediaVariantsJob;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

it('stores the image under a generated name with its dimensions', function () {
    $uploader = User::factory()->create();

    $media = app(UploadMedia::class)->handle(
        UploadedFile::fake()->image('match-photo.jpg', 1600, 900),
        MediaCollection::Default,
        $uploader,
    );

    expect($media->path)->toMatch('#^media/\d{4}/\d{2}/[0-9a-f-]{36}\.jpg$#')
        ->and($media->width)->toBe(1600)
        ->and($media->height)->toBe(900)
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->original_filename)->toBe('match-photo.jpg')
        ->and($media->uploaded_by)->toBe($uploader->id);

    Storage::disk('public')->assertExists($media->path);
});

it('does not let the client filename influence the stored path', function () {
    $media = app(UploadMedia::class)->handle(
        UploadedFile::fake()->image('../../shell.php.jpg', 100, 100),
        MediaCollection::Default,
        User::factory()->create(),
    );

    expect($media->path)->toMatch('#^media/\d{4}/\d{2}/[0-9a-f-]{36}\.jpg$#')
        ->and($media->path)->not->toContain('shell');
});

it('strips data appended after the image content', function () {
    $image = UploadedFile::fake()->image('photo.jpg', 100, 100);
    $file = UploadedFile::fake()->createWithContent('photo.jpg', file_get_contents($image->getRealPath()).'<?php system($_GET["c"]); ?>');

    $media = app(UploadMedia::class)->handle($file, MediaCollection::Default, User::factory()->create());

    expect(Storage::disk('public')->get($media->path))->not->toContain('<?php');
});

it('rejects SVG files for every role', function (UserRole $role, string $filename, string $content) {
    $file = UploadedFile::fake()->createWithContent($filename, $content);

    expect(fn () => app(UploadMedia::class)->handle($file, MediaCollection::Default, User::factory()->role($role)->create()))
        ->toThrow(ValidationException::class);

    expect(Media::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with(UserRole::cases())->with([
    'svg file' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
    'svg renamed to png' => ['logo.png', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
    'svg with xml declaration renamed to jpg' => ['logo.jpg', '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'],
]);

it('rejects files that are not images', function (string $filename, string $content) {
    $file = UploadedFile::fake()->createWithContent($filename, $content);

    expect(fn () => app(UploadMedia::class)->handle($file, MediaCollection::Default, User::factory()->create()))
        ->toThrow(ValidationException::class);

    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'pdf' => ['document.pdf', '%PDF-1.4 fake'],
    'php disguised as jpg' => ['shell.jpg', '<?php system($_GET["c"]); ?>'],
    'gif' => ['animation.gif', "GIF89a\x01\x00\x01\x00\x00\x00\x00;"],
]);

it('rejects files larger than 10 MB', function () {
    $file = UploadedFile::fake()->image('big.jpg', 100, 100)->size(10_241);

    expect(fn () => app(UploadMedia::class)->handle($file, MediaCollection::Default, User::factory()->create()))
        ->toThrow(ValidationException::class);
});

it('rejects images wider than 8000 pixels', function () {
    $file = UploadedFile::fake()->image('panorama.png', 8_001, 10);

    expect(fn () => app(UploadMedia::class)->handle($file, MediaCollection::Default, User::factory()->create()))
        ->toThrow(ValidationException::class);
});

it('requires alt text for featured images', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    expect(fn () => app(UploadMedia::class)->handle($file, MediaCollection::FeaturedImage, User::factory()->create()))
        ->toThrow(ValidationException::class);

    expect(Media::query()->count())->toBe(0);
});

it('queues generation of the responsive variants', function () {
    $media = app(UploadMedia::class)->handle(
        UploadedFile::fake()->image('photo.jpg', 100, 100),
        MediaCollection::FeaturedImage,
        User::factory()->create(),
        'Ο τερματοφύλακας αποκρούει πέναλτι',
    );

    Queue::assertPushed(GenerateMediaVariantsJob::class, fn (GenerateMediaVariantsJob $job) => $job->media->is($media));
});
