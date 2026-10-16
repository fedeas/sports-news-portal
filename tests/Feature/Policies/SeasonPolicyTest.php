<?php

use App\Enums\UserRole;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can manage seasons', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $season = Season::factory()->create();

    expect($user->can('viewAny', Season::class))->toBe($allowed)
        ->and($user->can('create', Season::class))->toBe($allowed)
        ->and($user->can('update', $season))->toBe($allowed)
        ->and($user->can('delete', $season))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
