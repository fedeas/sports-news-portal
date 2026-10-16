<?php

use App\Enums\ArticleStatus;
use App\Enums\MediaCollection;
use App\Livewire\Admin\Articles\Form;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\League;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

function fillArticleForm(Testable $component, Category $category, Author $author): Testable
{
    return $component
        ->set('title', 'Ομόνοια – ΑΠΟΕΛ 2-1 στο ντέρμπι')
        ->set('body', '<div>Η Ομόνοια κέρδισε το ντέρμπι.</div>')
        ->set('category_id', (string) $category->id)
        ->set('author_id', (string) $author->id);
}

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.articles.create'))->assertRedirect(route('login'));
    });

    it('renders the editor for editors', function () {
        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee('trix-editor', false);
    });

    it('links to a signed preview of the article being edited', function () {
        $article = Article::factory()->create();

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.articles.edit', $article))
            ->assertSee(route('articles.preview', $article).'?expires=', false);
    });

    it('forbids authors without an author profile from writing articles', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.articles.create'))
            ->assertForbidden();
    });

    it('forbids authors from editing articles by others', function () {
        $user = User::factory()->author()->create();
        Author::factory()->forUser($user)->create();

        $this->actingAs($user)
            ->get(route('admin.articles.edit', Article::factory()->create()))
            ->assertForbidden();
    });

    it('forbids authors from editing their published articles', function () {
        $user = User::factory()->author()->create();
        $article = Article::factory()->published()->for(Author::factory()->forUser($user))->create();

        $this->actingAs($user)
            ->get(route('admin.articles.edit', $article))
            ->assertForbidden();
    });
});

describe('editors', function () {
    it('publish an article with a generated slug and new tags', function () {
        $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00'));
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create();
        $author = Author::factory()->create();

        $component = fillArticleForm(Livewire::test(Form::class), $category, $author)
            ->set('newTag', 'ΑΠΟΕΛ')
            ->call('addTag')
            ->set('status', 'published')
            ->set('is_breaking', true)
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::query()->with('tags')->sole();

        $component->assertRedirect(route('admin.articles.edit', $article));

        expect($article->slug)->toBe('omonoia-apoel-2-1-sto-dermpi')
            ->and($article->status)->toBe(ArticleStatus::Published)
            ->and($article->published_at->toDateTimeString())->toBe('2026-09-16 10:00:00')
            ->and($article->is_breaking)->toBeTrue()
            ->and($article->category_id)->toBe($category->id)
            ->and($article->author_id)->toBe($author->id)
            ->and($article->tags->pluck('name')->all())->toBe(['ΑΠΟΕΛ']);
    });

    it('schedule an article using Cyprus local time', function () {
        $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00'));
        $this->actingAs(User::factory()->editor()->create());

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), Author::factory()->create())
            ->set('status', 'published')
            ->set('publish_at', '2026-09-20T18:30')
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::query()->sole();

        expect($article->status)->toBe(ArticleStatus::Scheduled)
            ->and($article->published_at->toDateTimeString())->toBe('2026-09-20 15:30:00');
    });

    it('generate a slug that does not collide with a category', function () {
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create(['slug' => 'podosfairo']);

        fillArticleForm(Livewire::test(Form::class), $category, Author::factory()->create())
            ->set('title', 'Ποδόσφαιρο')
            ->call('save')
            ->assertHasNoErrors();

        expect(Article::query()->sole()->slug)->toBe('podosfairo-2');
    });

    it('cannot use a slug that belongs to a category', function () {
        $this->actingAs(User::factory()->editor()->create());
        $category = Category::factory()->create(['slug' => 'podosfairo']);

        fillArticleForm(Livewire::test(Form::class), $category, Author::factory()->create())
            ->set('slug', 'podosfairo')
            ->call('save')
            ->assertHasErrors(['slug' => 'unique']);

        expect(Article::query()->count())->toBe(0);
    });

    it('cannot save a body with no visible text after sanitising', function () {
        $this->actingAs(User::factory()->editor()->create());

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), Author::factory()->create())
            ->set('body', '<script>alert(1)</script>')
            ->call('save')
            ->assertHasErrors(['body' => 'required']);
    });

    it('update an article, keep its slug and remove a tag', function () {
        $this->actingAs(User::factory()->editor()->create());
        $article = Article::factory()->create(['slug' => 'palio-slug']);
        $article->tags()->attach(Tag::factory()->create());

        Livewire::test(Form::class, ['article' => $article])
            ->set('title', 'Νέος τίτλος')
            ->call('removeTag', 0)
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();

        expect($article->title)->toBe('Νέος τίτλος')
            ->and($article->slug)->toBe('palio-slug')
            ->and($article->tags()->count())->toBe(0);
    });

    it('cannot change the edited article id from the browser', function () {
        $this->actingAs(User::factory()->editor()->create());

        expect(fn () => Livewire::test(Form::class)->set('articleId', 1))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });
});

describe('authors', function () {
    it('save a draft under their own profile', function () {
        $user = User::factory()->author()->create();
        $profile = Author::factory()->forUser($user)->create();
        $this->actingAs($user);

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), $profile)
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::query()->sole();

        expect($article->status)->toBe(ArticleStatus::Draft)
            ->and($article->author_id)->toBe($profile->id)
            ->and($article->created_by)->toBe($user->id);
    });

    it('cannot publish', function () {
        $user = User::factory()->author()->create();
        $profile = Author::factory()->forUser($user)->create();
        $this->actingAs($user);

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), $profile)
            ->set('status', 'published')
            ->call('save')
            ->assertHasErrors(['status' => 'in']);

        expect(Article::query()->count())->toBe(0);
    });

    it('cannot write under another author profile', function () {
        $user = User::factory()->author()->create();
        Author::factory()->forUser($user)->create();
        $this->actingAs($user);

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), Author::factory()->create())
            ->call('save')
            ->assertHasErrors(['author_id' => 'in']);
    });

    it('cannot mark an article as breaking news', function () {
        $user = User::factory()->author()->create();
        $profile = Author::factory()->forUser($user)->create();
        $this->actingAs($user);

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), $profile)
            ->set('is_breaking', true)
            ->call('save')
            ->assertHasErrors(['is_breaking' => 'declined']);
    });
});

describe('tags', function () {
    it('adds a tag only once when names differ in case and accents', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Form::class)
            ->set('newTag', 'ΑΠΟΕΛ')
            ->call('addTag')
            ->set('newTag', 'Αποέλ')
            ->call('addTag')
            ->assertSet('tag_names', ['ΑΠΟΕΛ'])
            ->assertSet('newTag', '');
    });

    it('uses the stored name when adding an existing tag', function () {
        $this->actingAs(User::factory()->editor()->create());
        Tag::factory()->create(['name' => 'Ανόρθωση', 'slug' => 'anorthosi']);

        Livewire::test(Form::class)
            ->set('newTag', 'ανορθωση')
            ->call('addTag')
            ->assertSet('tag_names', ['Ανόρθωση']);
    });
});

describe('teams and leagues', function () {
    it('links an article to a team and a league found by search', function () {
        $this->actingAs(User::factory()->editor()->create());
        $team = Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας', 'slug' => 'olympiada-lefkosias']);
        $league = League::factory()->create(['name' => 'Πρωτάθλημα Κύπρου', 'slug' => 'protathlima-kyprou']);

        $component = fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), Author::factory()->create())
            ->set('subjectSearch', 'olympiada');

        expect($component->instance()->subjectSuggestions['teams']->modelKeys())->toBe([$team->id]);

        $component
            ->call('addTeam', $team->id)
            ->call('addLeague', $league->id)
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::query()->sole();

        expect($article->teams()->pluck('teams.id')->all())->toBe([$team->id])
            ->and($article->leagues()->pluck('leagues.id')->all())->toBe([$league->id]);
    });

    it('does not suggest inactive teams', function () {
        $this->actingAs(User::factory()->editor()->create());
        Team::factory()->inactive()->create(['name' => 'Κρυφή Ομάδα']);

        $component = Livewire::test(Form::class)->set('subjectSearch', 'Κρυφή');

        expect($component->instance()->subjectSuggestions['teams'])->toBeEmpty();
    });

    it('removes a team from an article', function () {
        $this->actingAs(User::factory()->editor()->create());
        $article = Article::factory()->create();
        $team = Team::factory()->create();
        $article->teams()->attach($team);

        Livewire::test(Form::class, ['article' => $article])
            ->call('removeTeam', $team->id)
            ->call('save')
            ->assertHasNoErrors();

        expect($article->teams()->count())->toBe(0);
    });

    it('rejects a team that does not exist', function () {
        $this->actingAs(User::factory()->editor()->create());

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), Author::factory()->create())
            ->set('team_ids', ['999'])
            ->call('save')
            ->assertHasErrors(['team_ids.0' => 'exists']);
    });
});

describe('featured image', function () {
    it('requires alt text when an image is uploaded', function () {
        $this->actingAs(User::factory()->editor()->create());

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), Author::factory()->create())
            ->set('featuredImage', UploadedFile::fake()->image('match.jpg', 1600, 900))
            ->call('save')
            ->assertHasErrors(['featured_image_alt' => 'required']);

        expect(Article::query()->count())->toBe(0);
    });

    it('stores the uploaded image as the article featured image', function () {
        $this->actingAs(User::factory()->editor()->create());

        fillArticleForm(Livewire::test(Form::class), Category::factory()->create(), Author::factory()->create())
            ->set('featuredImage', UploadedFile::fake()->image('match.jpg', 1600, 900))
            ->set('featured_image_alt', 'Πανηγυρισμοί μετά το γκολ')
            ->call('save')
            ->assertHasNoErrors();

        $image = Article::query()->with('featuredImage')->sole()->featuredImage;

        expect($image->collection)->toBe(MediaCollection::FeaturedImage)
            ->and($image->alt_text)->toBe('Πανηγυρισμοί μετά το γκολ')
            ->and($image->mediable_type)->toBe('article');
    });
});
