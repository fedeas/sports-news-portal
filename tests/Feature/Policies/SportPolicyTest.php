<?php

use App\Enums\UserRole;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('only admins can manage sports', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $sport = Sport::factory()->create();

    expect($user->can('viewAny', Sport::class))->toBe($allowed)
        ->and($user->can('create', Sport::class))->toBe($allowed)
        ->and($user->can('update', $sport))->toBe($allowed)
        ->and($user->can('delete', $sport))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, false],
    'author' => [UserRole::Author, false],
]);
