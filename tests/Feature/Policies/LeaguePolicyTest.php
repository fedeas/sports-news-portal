<?php

use App\Enums\UserRole;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins and editors can manage leagues', function (UserRole $role, bool $allowed) {
    $user = User::factory()->role($role)->create();
    $league = League::factory()->create();

    expect($user->can('viewAny', League::class))->toBe($allowed)
        ->and($user->can('create', League::class))->toBe($allowed)
        ->and($user->can('update', $league))->toBe($allowed)
        ->and($user->can('delete', $league))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'editor' => [UserRole::Editor, true],
    'author' => [UserRole::Author, false],
]);
