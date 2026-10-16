<?php

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can manage teams', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $team = Team::factory()->create();

    expect($user->can('viewAny', Team::class))->toBe($allowed)
        ->and($user->can('create', Team::class))->toBe($allowed)
        ->and($user->can('update', $team))->toBe($allowed)
        ->and($user->can('delete', $team))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
