<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 for the registration screen', function () {
    $this->get('/register')->assertNotFound();
});

it('does not create an account when posting to the registration endpoint', function () {
    // 405 rather than 404: the public GET /{category} route matches the path but not the method.
    $this->post('/register', [
        'name' => 'Guest',
        'email' => 'guest@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertClientError();

    expect(User::query()->where('email', 'guest@example.com')->exists())->toBeFalse();
    $this->assertGuest();
});

it('does not show a sign-up link on the login screen', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('/register');
});
