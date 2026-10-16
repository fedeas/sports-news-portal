<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('only admins can list and create accounts', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();

    expect($user->can('viewAny', User::class))->toBe($allowed)
        ->and($user->can('create', User::class))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, false],
    'author' => [UserRole::Author, false],
]);

test('only admins can change another account', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $other = User::factory()->create();

    expect($user->can('update', $other))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, false],
    'author' => [UserRole::Author, false],
]);

test('admins cannot change their own account role', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('update', $admin))->toBeFalse();
});
