<?php

use App\Livewire\Admin\Articles\Index;
use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('listing articles', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.articles.index'))->assertRedirect(route('login'));
    });

    it('lists every article for editors with titles escaped', function () {
        Article::factory()->create(['title' => '<script>alert("xss")</script>']);
        Article::factory()->published()->create(['title' => 'Ανόρθωση – Άρης 1-0']);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee('Ανόρθωση – Άρης 1-0')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });

    it('lists only their own articles for authors', function () {
        $user = User::factory()->author()->create();
        Article::factory()->for(Author::factory()->forUser($user))->create(['title' => 'Το δικό μου άρθρο']);
        Article::factory()->create(['title' => 'Άρθρο άλλου συντάκτη']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Το δικό μου άρθρο')
            ->assertDontSee('Άρθρο άλλου συντάκτη');
    });

    it('offers a signed preview link for articles an author can no longer edit', function () {
        $user = User::factory()->author()->create();
        $article = Article::factory()->published()->for(Author::factory()->forUser($user))->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee(route('articles.preview', $article).'?expires=', false)
            ->assertDontSee(route('admin.articles.edit', $article));
    });

    it('filters articles by status', function () {
        $this->actingAs(User::factory()->editor()->create());
        Article::factory()->published()->create(['title' => 'Δημοσιευμένο άρθρο']);
        Article::factory()->create(['title' => 'Πρόχειρο άρθρο']);

        Livewire::test(Index::class)
            ->set('status', 'published')
            ->assertSee('Δημοσιευμένο άρθρο')
            ->assertDontSee('Πρόχειρο άρθρο');
    });

    it('ignores status filter values that are not article statuses', function () {
        $this->actingAs(User::factory()->editor()->create());
        Article::factory()->published()->create(['title' => 'Δημοσιευμένο άρθρο']);
        Article::factory()->create(['title' => 'Πρόχειρο άρθρο']);

        Livewire::test(Index::class)
            ->set('status', "draft' OR '1'='1")
            ->assertSee('Δημοσιευμένο άρθρο')
            ->assertSee('Πρόχειρο άρθρο');
    });
});

describe('deleting articles', function () {
    it('moves an article to the trash', function () {
        $this->actingAs(User::factory()->editor()->create());
        $article = Article::factory()->published()->create();

        Livewire::test(Index::class)->call('delete', $article->id);

        expect(Article::query()->withTrashed()->find($article->id)->trashed())->toBeTrue();
    });

    it('forbids authors from deleting their published articles', function () {
        $user = User::factory()->author()->create();
        $article = Article::factory()->published()->for(Author::factory()->forUser($user))->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('delete', $article->id)
            ->assertForbidden();

        expect($article->refresh()->trashed())->toBeFalse();
    });
});
