<?php

use App\Livewire\Admin\Tags;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.tags.index'))
            ->assertForbidden();
    });

    it('renders tags for editors with names escaped', function () {
        Tag::factory()->create(['name' => '<script>alert("xss")</script>']);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.tags.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('listing tags', function () {
    it('filters tags by search term', function (string $search) {
        $this->actingAs(User::factory()->editor()->create());
        Tag::factory()->create(['name' => 'Ανόρθωση', 'slug' => 'anorthosi']);
        Tag::factory()->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);

        Livewire::test(Tags::class)
            ->set('search', $search)
            ->assertSee('Ανόρθωση')
            ->assertDontSee('ΑΠΟΕΛ');
    })->with([
        'greek name' => 'Ανόρθ',
        'latin transliteration' => 'anorth',
    ]);
});

describe('saving tags', function () {
    it('creates a tag with a slug generated from the Greek name', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Tags::class)
            ->call('create')
            ->set('name', 'Ανόρθωση')
            ->call('save')
            ->assertHasNoErrors();

        $tag = Tag::query()->sole();

        expect($tag->name)->toBe('Ανόρθωση')
            ->and($tag->slug)->toBe('anorthosi');
    });

    it('requires a name', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Tags::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    });

    it('rejects a name that matches an existing tag ignoring case and accents', function () {
        $this->actingAs(User::factory()->editor()->create());
        Tag::factory()->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);

        Livewire::test(Tags::class)
            ->call('create')
            ->set('name', 'Αποέλ')
            ->call('save')
            ->assertHasErrors(['name']);

        expect(Tag::query()->count())->toBe(1);
    });

    it('rejects a name without letters or numbers', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Tags::class)
            ->call('create')
            ->set('name', '!!!')
            ->call('save')
            ->assertHasErrors(['name']);

        expect(Tag::query()->count())->toBe(0);
    });

    it('renames a tag and updates its slug', function () {
        $this->actingAs(User::factory()->editor()->create());
        $tag = Tag::factory()->create(['name' => 'Ομόνοια', 'slug' => 'omonoia']);

        Livewire::test(Tags::class)
            ->call('edit', $tag->id)
            ->set('name', 'Ομόνοια Λευκωσίας')
            ->call('save')
            ->assertHasNoErrors();

        $tag->refresh();

        expect($tag->name)->toBe('Ομόνοια Λευκωσίας')
            ->and($tag->slug)->toBe('omonoia-lefkosias');
    });

    it('saves a tag without treating its own slug as a duplicate', function () {
        $this->actingAs(User::factory()->editor()->create());
        $tag = Tag::factory()->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);

        Livewire::test(Tags::class)
            ->call('edit', $tag->id)
            ->set('name', 'Αποέλ')
            ->call('save')
            ->assertHasNoErrors();

        expect($tag->refresh()->name)->toBe('Αποέλ');
    });

    it('does not allow the edited tag id to be changed from the browser', function () {
        $this->actingAs(User::factory()->editor()->create());

        expect(fn () => Livewire::test(Tags::class)->set('editingId', 1))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });
});

describe('deleting tags', function () {
    it('deletes a tag', function () {
        $this->actingAs(User::factory()->editor()->create());
        $tag = Tag::factory()->create();

        Livewire::test(Tags::class)->call('delete', $tag->id);

        expect(Tag::query()->find($tag->id))->toBeNull();
    });
});
