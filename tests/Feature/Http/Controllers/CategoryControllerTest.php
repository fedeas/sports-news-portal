<?php

use App\Enums\SectionIcon;
use App\Http\Controllers\CategoryController;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\League;
use App\Models\Media;
use App\Models\Tag;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * The row of section chips above the articles: label, URL and whether it is the page you are on.
 *
 * @return array<int, array{label: string, url: string, current: bool}>
 */
function subcategoryChips(TestResponse $response): array
{
    preg_match('#<nav aria-label="'.__('Subcategories').'".*?</nav>#su', $response->getContent(), $nav);

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.($nav[0] ?? ''));

    return collect($document->getElementsByTagName('a'))
        ->map(fn (DOMElement $link): array => [
            'label' => trim($link->textContent),
            'url' => $link->getAttribute('href'),
            'current' => $link->getAttribute('aria-current') === 'page',
        ])
        ->all();
}

describe('category pages', function () {
    it('lists published articles from the category and its subcategories, newest first', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo']);
        $cyprus = Category::factory()->childOf($football)->create(['slug' => 'kypros']);

        Article::factory()->published()->for($football)->create(['title' => 'Παλαιότερο άρθρο', 'published_at' => now()->subDays(2)]);
        Article::factory()->published()->for($cyprus)->create(['title' => 'Νεότερο άρθρο', 'published_at' => now()->subHour()]);

        $this->get('/podosfairo')
            ->assertOk()
            ->assertSeeInOrder(['Νεότερο άρθρο', 'Παλαιότερο άρθρο']);
    });

    it('opens the first page with the main story and four beside it, and later pages with the feed', function () {
        $football = Category::factory()->create(['name' => 'Ποδόσφαιρο', 'slug' => 'podosfairo']);
        Article::factory()->published()->for($football)->count(CategoryController::ARTICLES_PER_PAGE)
            ->sequence(fn ($sequence) => ['title' => "Θέμα {$sequence->index}", 'published_at' => now()->subMinutes($sequence->index)])
            ->create();
        Article::factory()->published()->for($football)->create(['title' => 'Παλιό θέμα', 'published_at' => now()->subDays(5)]);

        $firstPage = $this->get('/podosfairo')->assertOk();
        $firstPage->assertSee('<section aria-label="Τελευταία νέα: Ποδόσφαιρο"', false)
            ->assertSeeInOrder(['Θέμα 0', 'Θέμα 1', 'Θέμα 4', 'Θέμα 5']);

        $this->get('/podosfairo?page=2')
            ->assertOk()
            ->assertSee('Παλιό θέμα')
            ->assertDontSee('<section aria-label="Τελευταία νέα', false);
    });

    it('shows the section pictogram, which a subsection without one borrows', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo', 'icon' => SectionIcon::Football]);
        Category::factory()->childOf($football)->create(['slug' => 'kypros']);

        $this->get('/podosfairo')->assertOk()->assertSee('data-section-icon="football"', false);
        $this->get('/podosfairo/kypros')->assertOk()->assertSee('data-section-icon="football"', false);
    });

    it('hides articles that are not live', function (string $state) {
        $category = Category::factory()->create(['slug' => 'podosfairo']);

        $article = match ($state) {
            'draft' => Article::factory()->for($category)->create(),
            'scheduled' => Article::factory()->scheduled()->for($category)->create(),
            'archived' => Article::factory()->archived()->for($category)->create(),
            'trashed' => tap(Article::factory()->published()->for($category)->create())->delete(),
        };

        $this->get('/podosfairo')
            ->assertOk()
            ->assertDontSee($article->title);
    })->with(['draft', 'scheduled', 'archived', 'trashed']);

    it('offers the other subsections on a subsection page, with the current one marked', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo', 'name' => 'Ποδόσφαιρο']);
        Category::factory()->childOf($football)->create(['slug' => 'kypros', 'name' => 'Κύπρος', 'sort_order' => 1]);
        Category::factory()->childOf($football)->create(['slug' => 'ellada', 'name' => 'Ελλάδα', 'sort_order' => 2]);
        Category::factory()->childOf($football)->inactive()->create(['slug' => 'krymmeni', 'name' => 'Κρυφή']);
        Category::factory()->create(['slug' => 'basket', 'name' => 'Μπάσκετ']);

        $response = $this->get('/podosfairo/kypros')->assertOk();

        expect(subcategoryChips($response))->toBe([
            ['label' => 'Όλα: Ποδόσφαιρο', 'url' => url('/podosfairo'), 'current' => false],
            ['label' => 'Κύπρος', 'url' => url('/podosfairo/kypros'), 'current' => true],
            ['label' => 'Ελλάδα', 'url' => url('/podosfairo/ellada'), 'current' => false],
        ]);
    });

    it('marks the section itself on the section page', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo', 'name' => 'Ποδόσφαιρο']);
        Category::factory()->childOf($football)->create(['slug' => 'kypros', 'name' => 'Κύπρος']);

        expect(subcategoryChips($this->get('/podosfairo')->assertOk()))->toBe([
            ['label' => 'Όλα: Ποδόσφαιρο', 'url' => url('/podosfairo'), 'current' => true],
            ['label' => 'Κύπρος', 'url' => url('/podosfairo/kypros'), 'current' => false],
        ]);
    });

    it('returns 404 for unknown and inactive categories', function () {
        Category::factory()->inactive()->create(['slug' => 'krymmeni']);

        $this->get('/den-yparxei')->assertNotFound();
        $this->get('/krymmeni')->assertNotFound();
    });

    it('redirects a subcategory requested at the top level to its full path', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo']);
        Category::factory()->childOf($football)->create(['slug' => 'kypros']);

        $this->get('/kypros')->assertRedirect('/podosfairo/kypros')->assertStatus(301);
    });

    it('shows only its own articles on a subcategory page', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo']);
        $cyprus = Category::factory()->childOf($football)->create(['slug' => 'kypros', 'name' => 'Κύπρος']);

        Article::factory()->published()->for($cyprus)->create(['title' => 'Άρθρο για την Κύπρο']);
        Article::factory()->published()->for($football)->create(['title' => 'Γενικό ποδοσφαιρικό άρθρο']);

        $this->get('/podosfairo/kypros')
            ->assertOk()
            ->assertSee('Κύπρος')
            ->assertSee('Άρθρο για την Κύπρο')
            ->assertDontSee('Γενικό ποδοσφαιρικό άρθρο');
    });

    it('redirects a subcategory reached through another section to its full path', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo']);
        Category::factory()->create(['slug' => 'basket']);
        Category::factory()->childOf($football)->create(['slug' => 'kypros']);

        $this->get('/basket/kypros')->assertStatus(301)->assertRedirect('/podosfairo/kypros');
    });

    it('paginates articles', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        $author = Author::factory()->create();

        Article::factory()->published()->for($category)->for($author)->count(21)->sequence(
            fn ($sequence) => ['title' => "Άρθρο νούμερο {$sequence->index}", 'published_at' => now()->subMinutes($sequence->index + 1)],
        )->create();

        $this->get('/podosfairo?page=2')
            ->assertOk()
            ->assertSee('Άρθρο νούμερο 20')
            ->assertDontSee('Άρθρο νούμερο 0<', false)
            ->assertSee('<link rel="canonical" href="'.url('/podosfairo').'?page=2" />', false);
    });

    it('shows a new article once it is saved, even when the page is cached', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        $this->get('/podosfairo');
        $article = Article::withoutEvents(fn () => Article::factory()->published()->for($category)->create(['title' => 'Φρέσκο άρθρο']));

        $cached = $this->get('/podosfairo');
        $article->update(['excerpt' => 'Ενημέρωση']);

        $cached->assertDontSee('Φρέσκο άρθρο');
        $this->get('/podosfairo')->assertSee('Φρέσκο άρθρο');
    });

    it('paginates beyond the cached pages', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        $author = Author::factory()->create();

        Article::factory()->published()->for($category)->for($author)->count(101)->sequence(
            fn ($sequence) => ['title' => "Άρθρο νούμερο {$sequence->index}", 'published_at' => now()->subMinutes($sequence->index + 1)],
        )->create();

        $this->get('/podosfairo?page=6')
            ->assertOk()
            ->assertSee('Άρθρο νούμερο 100')
            ->assertDontSee('Άρθρο νούμερο 99<', false);
    });

    it('uses the category meta title and description', function () {
        Category::factory()->create([
            'slug' => 'podosfairo',
            'name' => 'Ποδόσφαιρο',
            'meta_title' => 'Ποδόσφαιρο: ειδήσεις και αποτελέσματα',
            'meta_description' => 'Όλα τα νέα του ποδοσφαίρου.',
        ]);

        $this->get('/podosfairo')
            ->assertOk()
            ->assertSee('<title>Ποδόσφαιρο: ειδήσεις και αποτελέσματα | '.config('app.name').'</title>', false)
            ->assertSee('<meta name="description" content="Όλα τα νέα του ποδοσφαίρου." />', false)
            ->assertSee('<link rel="canonical" href="'.url('/podosfairo').'" />', false);
    });

    it('escapes the category name', function () {
        Category::factory()->create(['slug' => 'podosfairo', 'name' => '<script>alert("xss")</script>']);

        $this->get('/podosfairo')
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('article pages', function () {
    it('renders a published article with its body, byline and tags', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        $article = Article::factory()->published()->for($category)
            ->for(Author::factory()->create(['name' => 'Μαρία Χριστοδούλου']))
            ->create([
                'slug' => 'omonoia-apoel-2-1',
                'title' => 'Ομόνοια – ΑΠΟΕΛ 2-1',
                'body' => '<h2>Το ματς</h2><p>Η <strong>Ομόνοια</strong> κέρδισε.</p>',
            ]);
        $article->coAuthors()->attach(Author::factory()->create(['name' => 'Γιώργος Παπαδόπουλος']));
        $article->tags()->attach(Tag::factory()->create(['name' => 'ΑΠΟΕΛ']));

        $this->get('/podosfairo/omonoia-apoel-2-1')
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Ομόνοια – ΑΠΟΕΛ 2-1')
            ->assertSee('<h2>Το ματς</h2><p>Η <strong>Ομόνοια</strong> κέρδισε.</p>', false)
            ->assertSeeInOrder(['Μαρία Χριστοδούλου', 'Γιώργος Παπαδόπουλος'])
            ->assertSee('ΑΠΟΕΛ');
    });

    it('returns 404 for articles that are not live', function (string $state) {
        $category = Category::factory()->create(['slug' => 'podosfairo']);

        match ($state) {
            'draft' => Article::factory()->for($category)->create(['slug' => 'arthro']),
            'scheduled' => Article::factory()->scheduled()->for($category)->create(['slug' => 'arthro']),
            'archived' => Article::factory()->archived()->for($category)->create(['slug' => 'arthro']),
            'trashed' => Article::factory()->published()->for($category)->create(['slug' => 'arthro'])->delete(),
            'in an inactive category' => Article::factory()->published()
                ->for(tap($category)->update(['is_active' => false]))
                ->create(['slug' => 'arthro']),
        };

        $this->get('/podosfairo/arthro')->assertNotFound();
    })->with(['draft', 'scheduled', 'archived', 'trashed', 'in an inactive category']);

    it('counts a view of the article page but not of a redirect to it', function () {
        $section = Category::factory()->create(['slug' => 'podosfairo']);
        Category::factory()->create(['slug' => 'basket']);
        $article = Article::factory()->published()->for($section)->create(['slug' => 'megalo-derby', 'view_count' => 0]);

        $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/podosfairo/megalo-derby');
        $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/basket/megalo-derby');

        expect($article->refresh()->view_count)->toBe(1);
    });

    it('redirects to the canonical URL when reached through another category', function () {
        Category::factory()->create(['slug' => 'basket']);
        $football = Category::factory()->create(['slug' => 'podosfairo']);
        Article::factory()->published()->for($football)->create(['slug' => 'omonoia-apoel-2-1']);

        $this->get('/basket/omonoia-apoel-2-1')
            ->assertStatus(301)
            ->assertRedirect('/podosfairo/omonoia-apoel-2-1');
    });

    it('includes canonical, Open Graph and Twitter tags using the featured image', function () {
        Storage::fake('public', ['url' => 'https://cdn.test/storage']);
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        Article::factory()->published()->for($category)->create([
            'slug' => 'omonoia-apoel-2-1',
            'title' => 'Ομόνοια – ΑΠΟΕΛ 2-1',
            'meta_description' => 'Η Ομόνοια κέρδισε το ντέρμπι.',
            'featured_image_id' => Media::factory()->withVariants()->create(['path' => 'media/2026/09/derby.jpg'])->id,
        ]);

        $this->get('/podosfairo/omonoia-apoel-2-1')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/podosfairo/omonoia-apoel-2-1').'" />', false)
            ->assertSee('<meta property="og:type" content="article" />', false)
            ->assertSee('<meta property="og:title" content="Ομόνοια – ΑΠΟΕΛ 2-1" />', false)
            ->assertSee('<meta property="og:description" content="Η Ομόνοια κέρδισε το ντέρμπι." />', false)
            ->assertSee('<meta property="og:image" content="https://cdn.test/storage/media/2026/09/derby-1200.webp" />', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image" />', false);
    });

    it('includes NewsArticle structured data', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo', 'name' => 'Ποδόσφαιρο']);
        Article::factory()->published()->for($category)
            ->for(Author::factory()->create(['name' => 'Μαρία Χριστοδούλου']))
            ->create(['slug' => 'omonoia-apoel-2-1', 'title' => 'Ομόνοια – ΑΠΟΕΛ 2-1', 'published_at' => '2026-09-15 15:30:00']);

        $html = $this->get('/podosfairo/omonoia-apoel-2-1')->assertOk()->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $data = json_decode($matches[1] ?? '', true);

        expect($data['@type'])->toBe('NewsArticle')
            ->and($data['headline'])->toBe('Ομόνοια – ΑΠΟΕΛ 2-1')
            ->and($data['datePublished'])->toBe('2026-09-15T15:30:00+00:00')
            ->and($data['articleSection'])->toBe('Ποδόσφαιρο')
            ->and($data['author'])->toBe([['@type' => 'Person', 'name' => 'Μαρία Χριστοδούλου']]);
    });

    it('escapes the title in the page and meta tags', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        Article::factory()->published()->for($category)->create([
            'slug' => 'arthro',
            'title' => '"><script>alert(1)</script>',
        ]);

        $this->get('/podosfairo/arthro')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('content=""><script>', false);
    });
});

it('links an article to its active teams and leagues', function () {
    $category = Category::factory()->create(['slug' => 'podosfairo']);
    $article = Article::factory()->published()->for($category)->create(['slug' => 'arthro']);
    $article->teams()->attach([
        Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας', 'slug' => 'olympiada'])->id,
        Team::factory()->inactive()->create(['name' => 'Κρυφή Ομάδα'])->id,
    ]);
    $article->leagues()->attach(League::factory()->create(['slug' => 'protathlima-kyprou']));

    $this->get('/podosfairo/arthro')
        ->assertOk()
        ->assertSee('href="'.url('/teams/olympiada').'"', false)
        ->assertSee('href="'.url('/leagues/protathlima-kyprou').'"', false)
        ->assertDontSee('Κρυφή Ομάδα');
});

describe('renamed slugs', function () {
    it('redirects an old article slug to the current URL', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        $article = Article::factory()->published()->for($category)->create(['slug' => 'palio-slug']);

        $article->update(['slug' => 'neo-slug']);

        $this->get('/podosfairo/palio-slug')->assertStatus(301)->assertRedirect('/podosfairo/neo-slug');
    });

    it('redirects an old section slug to the current URL', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);

        $category->update(['slug' => 'podosfairo-kyprou']);

        $this->get('/podosfairo')->assertStatus(301)->assertRedirect('/podosfairo-kyprou');
    });

    it('redirects an old subcategory slug to the current URL', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo']);
        $cyprus = Category::factory()->childOf($football)->create(['slug' => 'kypros']);

        $cyprus->update(['slug' => 'kypriako-protathlima']);

        $this->get('/podosfairo/kypros')->assertStatus(301)->assertRedirect('/podosfairo/kypriako-protathlima');
    });

    it('redirects subcategories and articles after their section is renamed', function () {
        $football = Category::factory()->create(['slug' => 'podosfairo']);
        Category::factory()->childOf($football)->create(['slug' => 'kypros']);
        Article::factory()->published()->for($football)->create(['slug' => 'omonoia-apoel']);

        $football->update(['slug' => 'podosfairo-kyprou']);

        $this->get('/podosfairo/kypros')->assertStatus(301)->assertRedirect('/podosfairo-kyprou/kypros');
        $this->get('/podosfairo/omonoia-apoel')->assertStatus(301)->assertRedirect('/podosfairo-kyprou/omonoia-apoel');
    });

    it('does not redirect to an article that is no longer live', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);
        $article = Article::factory()->published()->for($category)->create(['slug' => 'palio-slug']);

        $article->update(['slug' => 'neo-slug']);
        $article->delete();

        $this->get('/podosfairo/palio-slug')->assertNotFound();
    });

    it('does not redirect to a section that is no longer active', function () {
        $category = Category::factory()->create(['slug' => 'podosfairo']);

        $category->update(['slug' => 'podosfairo-kyprou', 'is_active' => false]);

        $this->get('/podosfairo')->assertNotFound();
    });

    it('still returns 404 for paths that never existed', function () {
        Category::factory()->create(['slug' => 'podosfairo']);

        $this->get('/podosfairo/den-yparxei')->assertNotFound();
        $this->get('/den-yparxei')->assertNotFound();
    });
});

it('does not shadow application routes', function (string $path, int $status) {
    Category::factory()->create(['slug' => 'podosfairo']);

    $this->get($path)->assertStatus($status);
})->with([
    'login' => ['/login', 200],
    'health check' => ['/up', 200],
    'admin (redirects guests)' => ['/admin/articles', 302],
    'settings (redirects guests)' => ['/settings/profile', 302],
]);

it('offers share links and topic and writer links on an article page', function () {
    $author = Author::factory()->create(['name' => 'Ελένη Κωνσταντίνου', 'slug' => 'eleni']);
    $article = Article::factory()->published()->for($author)->for(Category::factory()->create(['slug' => 'podosfairo']))->create(['slug' => 'megalo-derby']);
    $article->tags()->attach(Tag::factory()->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel'])->id);

    $this->get($article->url())
        ->assertOk()
        ->assertSee(__('Share'))
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.urlencode($article->url()), false)
        ->assertSee(route('tags.show', 'apoel'), false)
        ->assertSee(route('authors.show', 'eleni'), false);
});

it('does not offer sharing on a preview', function () {
    $article = Article::factory()->create();

    $this->get($article->previewUrl())->assertOk()->assertDontSee(__('Share'));
});

it('cannot break out of the share script through a canonical URL', function () {
    $article = Article::factory()->published()->create([
        'canonical_url' => "https://example.com/a'+alert(1)+'b",
    ]);

    $content = $this->get($article->url())->assertOk()->getContent();

    // The handler reads the URL from Alpine data, and @js escapes the quotes that would end the string.
    expect($content)->toContain('navigator.clipboard.writeText(url)')
        ->not->toContain("writeText('")
        ->toContain(trim(json_encode("'+alert(1)+'", JSON_HEX_APOS), '"'));
});
