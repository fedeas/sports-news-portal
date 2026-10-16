<?php

use App\Enums\UserRole;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can list, rename and delete tags', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $tag = Tag::factory()->create();

    expect($user->can('viewAny', Tag::class))->toBe($allowed)
        ->and($user->can('update', $tag))->toBe($allowed)
        ->and($user->can('delete', $tag))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);

test('every staff role can create tags', function (UserRole $role) {
    expect(User::factory()->role($role)->create()->can('create', Tag::class))->toBeTrue();
})->with(UserRole::cases());
