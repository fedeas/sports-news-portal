<?php

use App\Enums\UserRole;
use App\Models\Fixture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can manage fixtures', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $fixture = Fixture::factory()->create();

    expect($user->can('viewAny', Fixture::class))->toBe($allowed)
        ->and($user->can('create', Fixture::class))->toBe($allowed)
        ->and($user->can('update', $fixture))->toBe($allowed)
        ->and($user->can('delete', $fixture))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
