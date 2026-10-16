<?php

use App\Enums\UserRole;
use App\Models\Author;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can manage authors', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $author = Author::factory()->create();

    expect($user->can('viewAny', Author::class))->toBe($allowed)
        ->and($user->can('create', Author::class))->toBe($allowed)
        ->and($user->can('update', $author))->toBe($allowed)
        ->and($user->can('delete', $author))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
