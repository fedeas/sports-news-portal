<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defaults new accounts to the author role', function () {
    $user = User::create([
        'name' => 'Νέος Χρήστης',
        'email' => 'new@example.com',
        'password' => 'password',
    ]);

    expect($user->refresh()->role)->toBe(UserRole::Author);
});

it('does not allow the role to be mass assigned', function () {
    $user = User::create([
        'name' => 'Νέος Χρήστης',
        'email' => 'new@example.com',
        'password' => 'password',
        'role' => 'admin',
    ]);

    expect($user->refresh()->role)->toBe(UserRole::Author);
});
