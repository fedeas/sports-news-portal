<?php

use App\Enums\SectionIcon;
use App\Livewire\Admin\Categories;
use App\Models\Article;
use App\Models\Category;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    });

    it('renders categories for editors with names escaped', function () {
        $parent = Category::factory()->create(['name' => 'Ποδόσφαιρο']);
        Category::factory()->childOf($parent)->create(['name' => '<script>alert("xss")</script>']);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Ποδόσφαιρο')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('saving categories', function () {
    it('creates a category with a slug generated from the Greek name', function () {
        $this->actingAs(User::factory()->editor()->create());
        $sport = Sport::factory()->create();

        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Ποδόσφαιρο')
            ->set('sport_id', (string) $sport->id)
            ->call('save')
            ->assertHasNoErrors();

        $category = Category::query()->sole();

        expect($category->slug)->toBe('podosfairo')
            ->and($category->sport_id)->toBe($sport->id)
            ->and($category->parent_id)->toBeNull();
    });

    it('creates a subcategory under a top-level category', function () {
        $this->actingAs(User::factory()->editor()->create());
        $parent = Category::factory()->create();

        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Κύπρος')
            ->set('parent_id', (string) $parent->id)
            ->call('save')
            ->assertHasNoErrors();

        expect(Category::query()->where('name', 'Κύπρος')->sole()->parent_id)->toBe($parent->id);
    });

    it('rejects a reserved slug', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Login')
            ->set('slug', 'login')
            ->call('save')
            ->assertHasErrors(['slug']);

        expect(Category::query()->count())->toBe(0);
    });

    it('rejects a slug used by another category', function () {
        $this->actingAs(User::factory()->editor()->create());
        Category::factory()->create(['slug' => 'podosfairo']);

        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Ποδόσφαιρο')
            ->set('slug', 'podosfairo')
            ->call('save')
            ->assertHasErrors(['slug' => 'unique']);
    });

    it('rejects a slug used by an article', function () {
        $this->actingAs(User::factory()->editor()->create());
        Article::factory()->create(['slug' => 'podosfairo']);

        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Ποδόσφαιρο')
            ->set('slug', 'podosfairo')
            ->call('save')
            ->assertHasErrors(['slug' => 'unique']);
    });

    it('rejects a sport that does not exist', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Ποδόσφαιρο')
            ->set('sport_id', '999')
            ->call('save')
            ->assertHasErrors(['sport_id' => 'exists']);
    });

    it('rejects a subcategory as the parent', function () {
        $this->actingAs(User::factory()->editor()->create());
        $child = Category::factory()->childOf(Category::factory()->create())->create();

        Livewire::test(Categories::class)
            ->call('create')
            ->set('name', 'Λευκωσία')
            ->set('parent_id', (string) $child->id)
            ->call('save')
            ->assertHasErrors(['parent_id' => 'exists']);
    });

    it('does not allow a category to be its own parent', function () {
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create();

        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->set('parent_id', (string) $category->id)
            ->call('save')
            ->assertHasErrors(['parent_id' => 'not_in']);

        expect($category->refresh()->parent_id)->toBeNull();
    });

    it('does not allow a category with subcategories to be moved under another category', function () {
        $this->actingAs(User::factory()->editor()->create());
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();
        $otherParent = Category::factory()->create();

        Livewire::test(Categories::class)
            ->call('edit', $parent->id)
            ->set('parent_id', (string) $otherParent->id)
            ->call('save')
            ->assertHasErrors(['parent_id' => 'prohibited']);

        expect($parent->refresh()->parent_id)->toBeNull();
    });

    it('updates an existing category and keeps its slug', function () {
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create(['name' => 'Μπάσκετ', 'slug' => 'mpasket']);

        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->set('name', 'Καλαθοσφαίριση')
            ->set('meta_description', 'Νέα από το μπάσκετ')
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();

        expect($category->name)->toBe('Καλαθοσφαίριση')
            ->and($category->slug)->toBe('mpasket')
            ->and($category->meta_description)->toBe('Νέα από το μπάσκετ');
    });

    it('gives a category one of the site pictograms, or none', function () {
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create(['icon' => SectionIcon::Basketball]);

        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->assertSet('icon', 'basketball')
            ->set('icon', 'football')
            ->call('save')
            ->assertHasNoErrors();

        expect($category->refresh()->icon)->toBe(SectionIcon::Football);

        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->set('icon', '')
            ->call('save')
            ->assertHasNoErrors();

        expect($category->refresh()->icon)->toBeNull();
    });

    it('rejects a pictogram the site does not draw', function () {
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create();

        Livewire::test(Categories::class)
            ->call('edit', $category->id)
            ->set('icon', '<script>')
            ->call('save')
            ->assertHasErrors(['icon']);

        expect($category->refresh()->icon)->toBeNull();
    });

    it('does not allow the edited category id to be changed from the browser', function () {
        $this->actingAs(User::factory()->editor()->create());

        expect(fn () => Livewire::test(Categories::class)->set('editingId', 1))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });
});

describe('deleting categories', function () {
    it('deletes a category without subcategories', function () {
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create();

        Livewire::test(Categories::class)->call('delete', $category->id);

        expect(Category::query()->find($category->id))->toBeNull();
    });

    it('keeps a category that still has subcategories', function () {
        $this->actingAs(User::factory()->editor()->create());
        $parent = Category::factory()->create();
        Category::factory()->childOf($parent)->create();

        Livewire::test(Categories::class)->call('delete', $parent->id);

        expect(Category::query()->find($parent->id))->not->toBeNull();
    });

    it('keeps a category that still has articles, including trashed ones', function () {
        $this->actingAs(User::factory()->editor()->create());
        $article = Article::factory()->create();
        $article->delete();

        Livewire::test(Categories::class)->call('delete', $article->category_id);

        expect(Category::query()->find($article->category_id))->not->toBeNull();
    });
});
