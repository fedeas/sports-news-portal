<?php

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every staff role can open the article list', function (UserRole $role) {
    expect(User::factory()->role($role)->create()->can('viewAny', Article::class))->toBeTrue();
})->with(UserRole::cases());

test('admins and editors can create, edit, delete and publish any article', function (UserRole $role) {
    $user = User::factory()->role($role)->create();
    $article = Article::factory()->published()->create();

    expect($user->can('create', Article::class))->toBeTrue()
        ->and($user->can('update', $article))->toBeTrue()
        ->and($user->can('delete', $article))->toBeTrue()
        ->and($user->can('publish', Article::class))->toBeTrue();
})->with([
    'admin' => UserRole::Admin,
    'editor' => UserRole::Editor,
]);

test('authors without an author profile cannot create articles', function () {
    expect(User::factory()->author()->create()->can('create', Article::class))->toBeFalse();
});

test('authors with an author profile can create articles', function () {
    $user = User::factory()->author()->create();
    Author::factory()->forUser($user)->create();

    expect($user->can('create', Article::class))->toBeTrue();
});

test('authors can edit and delete drafts bylined to their profile', function () {
    $user = User::factory()->author()->create();
    $article = Article::factory()->for(Author::factory()->forUser($user))->create();

    expect($user->can('update', $article))->toBeTrue()
        ->and($user->can('delete', $article))->toBeTrue();
});

test('authors can edit and delete drafts they created', function () {
    $user = User::factory()->author()->create();
    $article = Article::factory()->create(['created_by' => $user->id]);

    expect($user->can('update', $article))->toBeTrue()
        ->and($user->can('delete', $article))->toBeTrue();
});

test('authors cannot edit or delete their own articles once they leave draft', function (string $state) {
    $user = User::factory()->author()->create();
    $article = Article::factory()->{$state}()->for(Author::factory()->forUser($user))->create();

    expect($user->can('update', $article))->toBeFalse()
        ->and($user->can('delete', $article))->toBeFalse();
})->with(['scheduled', 'published', 'archived']);

test('authors cannot edit or delete drafts by other authors', function () {
    $user = User::factory()->author()->create();
    Author::factory()->forUser($user)->create();
    $article = Article::factory()->create();

    expect($user->can('update', $article))->toBeFalse()
        ->and($user->can('delete', $article))->toBeFalse();
});

test('authors cannot publish articles', function () {
    expect(User::factory()->author()->create()->can('publish', Article::class))->toBeFalse();
});
