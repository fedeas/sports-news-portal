<?php

use App\Actions\GenerateUniqueSlug;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('transliterates a Greek name into a latin slug', function () {
    expect((new GenerateUniqueSlug)->handle('Ποδόσφαιρο', Sport::class))->toBe('podosfairo');
});

it('appends an increasing number when the slug is taken', function () {
    Sport::factory()->create(['slug' => 'podosfairo']);
    Sport::factory()->create(['slug' => 'podosfairo-2']);

    expect((new GenerateUniqueSlug)->handle('Ποδόσφαιρο', Sport::class))->toBe('podosfairo-3');
});

it('keeps the slug of the record being edited', function () {
    $sport = Sport::factory()->create(['slug' => 'podosfairo']);

    expect((new GenerateUniqueSlug)->handle('Ποδόσφαιρο', Sport::class, $sport->id))->toBe('podosfairo');
});

it('skips reserved slugs', function () {
    expect((new GenerateUniqueSlug)->handle('Admin', Sport::class, reserved: ['admin']))->toBe('admin-2');
});

it('keeps the suffixed slug within the maximum length', function () {
    Sport::factory()->create(['slug' => 'abcdefghij']);

    expect((new GenerateUniqueSlug)->handle('abcdefghijklmnop', Sport::class, maxLength: 10))->toBe('abcdefgh-2');
});

it('falls back to a placeholder when the name has no sluggable characters', function () {
    expect((new GenerateUniqueSlug)->handle('!!!', Sport::class))->toBe('untitled');
});
