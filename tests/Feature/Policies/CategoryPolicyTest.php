<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can manage categories', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $category = Category::factory()->create();

    expect($user->can('viewAny', Category::class))->toBe($allowed)
        ->and($user->can('create', Category::class))->toBe($allowed)
        ->and($user->can('update', $category))->toBe($allowed)
        ->and($user->can('delete', $category))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
