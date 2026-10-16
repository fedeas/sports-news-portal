<?php

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the byline when the linked account is deleted', function () {
    $user = User::factory()->create();
    $author = Author::factory()->forUser($user)->create();

    $user->delete();

    expect($author->refresh()->user_id)->toBeNull()
        ->and($author->name)->not->toBeEmpty();
});

it('links an account to at most one author', function () {
    $user = User::factory()->create();
    Author::factory()->forUser($user)->create();

    expect(fn () => Author::factory()->forUser($user)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});
