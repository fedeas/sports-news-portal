<?php

use App\Enums\UserRole;
use App\Livewire\Admin\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    });

    it('forbids non-admin roles', function (UserRole $role) {
        $this->actingAs(User::factory()->role($role)->create())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    })->with([
        'editor' => UserRole::Editor,
        'author' => UserRole::Author,
    ]);

    it('renders the user list for admins with names escaped', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => '<script>alert("xss")</script>']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });

    it('refuses to mount the component for a non-admin', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Users::class)->assertForbidden();
    });
});

describe('creating users', function () {
    it('creates a verified account with the chosen role', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Users::class)
            ->set('name', 'Γιώργος Παπαδόπουλος')
            ->set('email', 'giorgos@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('role', 'editor')
            ->call('createUser')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'giorgos@example.com')->sole();

        expect($user->name)->toBe('Γιώργος Παπαδόπουλος')
            ->and($user->role)->toBe(UserRole::Editor)
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeTrue();
    });

    it('requires name, email, password and role', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Users::class)
            ->set('role', '')
            ->call('createUser')
            ->assertHasErrors([
                'name' => 'required',
                'email' => 'required',
                'password' => 'required',
                'role' => 'required',
            ]);
    });

    it('rejects a role that does not exist', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Users::class)
            ->set('name', 'Test')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('role', 'superadmin')
            ->call('createUser')
            ->assertHasErrors(['role']);

        expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
    });

    it('rejects an email that is already taken', function () {
        $this->actingAs(User::factory()->admin()->create());
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(Users::class)
            ->set('name', 'Test')
            ->set('email', 'taken@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('createUser')
            ->assertHasErrors(['email' => 'unique']);
    });
});

describe('changing roles', function () {
    it('changes the role of another account', function () {
        $this->actingAs(User::factory()->admin()->create());
        $author = User::factory()->author()->create();

        Livewire::test(Users::class)
            ->call('changeRole', $author->id, 'editor')
            ->assertHasNoErrors();

        expect($author->refresh()->role)->toBe(UserRole::Editor);
    });

    it('forbids an admin from changing their own role', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(Users::class)
            ->call('changeRole', $admin->id, 'author')
            ->assertForbidden();

        expect($admin->refresh()->role)->toBe(UserRole::Admin);
    });

    it('rejects a role that does not exist', function () {
        $this->actingAs(User::factory()->admin()->create());
        $author = User::factory()->author()->create();

        Livewire::test(Users::class)
            ->call('changeRole', $author->id, 'superadmin')
            ->assertHasErrors(['role']);

        expect($author->refresh()->role)->toBe(UserRole::Author);
    });
});
