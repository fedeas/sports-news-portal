<?php

use App\Actions\FindOrCreateTag;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a tag with a transliterated slug', function () {
    $tag = (new FindOrCreateTag)->handle(' Ανόρθωση ');

    expect($tag->name)->toBe('Ανόρθωση')
        ->and($tag->slug)->toBe('anorthosi')
        ->and(Tag::query()->count())->toBe(1);
});

it('returns the existing tag when the name differs only in case and accents', function () {
    $existing = Tag::factory()->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);

    $tag = (new FindOrCreateTag)->handle('Αποέλ');

    expect($tag->is($existing))->toBeTrue()
        ->and($tag->name)->toBe('ΑΠΟΕΛ')
        ->and(Tag::query()->count())->toBe(1);
});

it('rejects a name without letters or numbers', function () {
    expect(fn () => (new FindOrCreateTag)->handle('!!!'))->toThrow(InvalidArgumentException::class);

    expect(Tag::query()->count())->toBe(0);
});
