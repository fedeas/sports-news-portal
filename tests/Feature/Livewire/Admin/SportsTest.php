<?php

use App\Enums\UserRole;
use App\Livewire\Admin\Sports;
use App\Models\Category;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.sports.index'))->assertRedirect(route('login'));
    });

    it('forbids non-admin roles', function (UserRole $role) {
        $this->actingAs(User::factory()->role($role)->create())
            ->get(route('admin.sports.index'))
            ->assertForbidden();
    })->with([
        'editor' => UserRole::Editor,
        'author' => UserRole::Author,
    ]);

    it('renders sports for admins with names escaped', function () {
        Sport::factory()->create(['name' => '<script>alert("xss")</script>']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.sports.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('saving sports', function () {
    it('creates a sport with a slug generated from the Greek name', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Sports::class)
            ->call('create')
            ->set('name', 'Ποδόσφαιρο')
            ->set('sort_order', '1')
            ->call('save')
            ->assertHasNoErrors();

        $sport = Sport::query()->sole();

        expect($sport->name)->toBe('Ποδόσφαιρο')
            ->and($sport->slug)->toBe('podosfairo')
            ->and($sport->sort_order)->toBe(1)
            ->and($sport->is_active)->toBeTrue();
    });

    it('requires a name', function () {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Sports::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);

        expect(Sport::query()->count())->toBe(0);
    });

    it('rejects a slug used by another sport', function () {
        $this->actingAs(User::factory()->admin()->create());
        Sport::factory()->create(['slug' => 'mpasket']);

        Livewire::test(Sports::class)
            ->call('create')
            ->set('name', 'Μπάσκετ')
            ->set('slug', 'mpasket')
            ->call('save')
            ->assertHasErrors(['slug' => 'unique']);
    });

    it('updates an existing sport and keeps its slug', function () {
        $this->actingAs(User::factory()->admin()->create());
        $sport = Sport::factory()->create(['name' => 'Μπάσκετ', 'slug' => 'mpasket']);

        Livewire::test(Sports::class)
            ->call('edit', $sport->id)
            ->set('name', 'Καλαθοσφαίριση')
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $sport->refresh();

        expect($sport->name)->toBe('Καλαθοσφαίριση')
            ->and($sport->slug)->toBe('mpasket')
            ->and($sport->is_active)->toBeFalse();
    });

    it('does not allow the edited sport id to be changed from the browser', function () {
        $this->actingAs(User::factory()->admin()->create());

        expect(fn () => Livewire::test(Sports::class)->set('editingId', 1))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });
});

describe('deleting sports', function () {
    it('deletes a sport without categories', function () {
        $this->actingAs(User::factory()->admin()->create());
        $sport = Sport::factory()->create();

        Livewire::test(Sports::class)->call('delete', $sport->id);

        expect(Sport::query()->find($sport->id))->toBeNull();
    });

    it('keeps a sport that teams still use', function () {
        $this->actingAs(User::factory()->admin()->create());
        $team = Team::factory()->create();

        Livewire::test(Sports::class)->call('delete', $team->sport_id);

        expect(Sport::query()->find($team->sport_id))->not->toBeNull();
    });

    it('keeps a sport that still has categories', function () {
        $this->actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();

        Livewire::test(Sports::class)->call('delete', $category->sport_id);

        expect(Sport::query()->find($category->sport_id))->not->toBeNull();
    });
});
