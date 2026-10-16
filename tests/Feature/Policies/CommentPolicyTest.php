<?php

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can moderate comments', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $comment = Comment::factory()->create();

    expect($user->can('viewAny', Comment::class))->toBe($allowed)
        ->and($user->can('update', $comment))->toBe($allowed)
        ->and($user->can('delete', $comment))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
