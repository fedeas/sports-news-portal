<?php

use App\Enums\MediaCollection;
use App\Livewire\Admin\Authors;
use App\Models\Article;
use App\Models\Author;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

describe('page access', function () {
    it('redirects guests to the login screen', function () {
        $this->get(route('admin.authors.index'))->assertRedirect(route('login'));
    });

    it('forbids authors', function () {
        $this->actingAs(User::factory()->author()->create())
            ->get(route('admin.authors.index'))
            ->assertForbidden();
    });

    it('renders authors for editors with names escaped', function () {
        Author::factory()->create(['name' => '<script>alert("xss")</script>']);

        $this->actingAs(User::factory()->editor()->create())
            ->get(route('admin.authors.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    });
});

describe('saving authors', function () {
    it('creates a guest byline with a generated slug and social links', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Authors::class)
            ->call('create')
            ->set('name', 'Μαρία Χριστοδούλου')
            ->set('bio', 'Αθλητική συντάκτρια.')
            ->set('twitter', 'https://x.com/maria')
            ->call('save')
            ->assertHasNoErrors();

        $author = Author::query()->sole();

        expect($author->slug)->toBe('maria-christodoulou')
            ->and($author->user_id)->toBeNull()
            ->and($author->social_links)->toBe(['twitter' => 'https://x.com/maria']);
    });

    it('links an author to a staff account', function () {
        $this->actingAs(User::factory()->editor()->create());
        $journalist = User::factory()->author()->create();

        Livewire::test(Authors::class)
            ->call('create')
            ->set('name', 'Γιώργος Παπαδόπουλος')
            ->set('user_id', (string) $journalist->id)
            ->call('save')
            ->assertHasNoErrors();

        expect(Author::query()->sole()->user_id)->toBe($journalist->id);
    });

    it('rejects an account that already has an author profile', function () {
        $this->actingAs(User::factory()->editor()->create());
        $journalist = User::factory()->author()->create();
        Author::factory()->forUser($journalist)->create();

        Livewire::test(Authors::class)
            ->call('create')
            ->set('name', 'Δεύτερο Προφίλ')
            ->set('user_id', (string) $journalist->id)
            ->call('save')
            ->assertHasErrors(['user_id' => 'unique']);

        expect(Author::query()->count())->toBe(1);
    });

    it('rejects social links that are not https URLs', function (string $link) {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Authors::class)
            ->call('create')
            ->set('name', 'Μαρία Χριστοδούλου')
            ->set('facebook', $link)
            ->call('save')
            ->assertHasErrors(['facebook']);
    })->with([
        'javascript scheme' => 'javascript:alert(1)',
        'plain http' => 'http://facebook.com/maria',
        'not a url' => 'maria',
    ]);

    it('updates an existing author and keeps its slug', function () {
        $this->actingAs(User::factory()->editor()->create());
        $author = Author::factory()->create(['name' => 'Μαρία Χ.', 'slug' => 'maria-x']);

        Livewire::test(Authors::class)
            ->call('edit', $author->id)
            ->set('name', 'Μαρία Χριστοδούλου')
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $author->refresh();

        expect($author->name)->toBe('Μαρία Χριστοδούλου')
            ->and($author->slug)->toBe('maria-x')
            ->and($author->is_active)->toBeFalse();
    });

    it('does not allow the edited author id to be changed from the browser', function () {
        $this->actingAs(User::factory()->editor()->create());

        expect(fn () => Livewire::test(Authors::class)->set('editingId', 1))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });
});

describe('author photos', function () {
    it('stores an uploaded photo as the author avatar', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Authors::class)
            ->call('create')
            ->set('name', 'Μαρία Χριστοδούλου')
            ->set('photo', UploadedFile::fake()->image('maria.jpg', 400, 400))
            ->call('save')
            ->assertHasNoErrors();

        $author = Author::query()->with('photo')->sole();

        expect($author->photo->collection)->toBe(MediaCollection::Avatar)
            ->and($author->photo->alt_text)->toBe('Μαρία Χριστοδούλου')
            ->and($author->photo->mediable_type)->toBe('author')
            ->and($author->photo->mediable_id)->toBe($author->id);

        Storage::disk('public')->assertExists($author->photo->path);
    });

    it('deletes the previous photo when a new one is uploaded', function () {
        $this->actingAs(User::factory()->editor()->create());
        $oldPhoto = Media::factory()->create();
        $author = Author::factory()->create(['photo_media_id' => $oldPhoto->id]);

        Livewire::test(Authors::class)
            ->call('edit', $author->id)
            ->set('photo', UploadedFile::fake()->image('new.jpg', 400, 400))
            ->call('save')
            ->assertHasNoErrors();

        expect(Media::query()->find($oldPhoto->id))->toBeNull()
            ->and($author->refresh()->photo_media_id)->not->toBeNull();
    });

    it('removes the photo when requested', function () {
        $this->actingAs(User::factory()->editor()->create());
        $photo = Media::factory()->create();
        $author = Author::factory()->create(['photo_media_id' => $photo->id]);

        Livewire::test(Authors::class)
            ->call('edit', $author->id)
            ->set('removePhoto', true)
            ->call('save')
            ->assertHasNoErrors();

        expect($author->refresh()->photo_media_id)->toBeNull()
            ->and(Media::query()->find($photo->id))->toBeNull();
    });

    it('rejects an SVG photo', function () {
        $this->actingAs(User::factory()->editor()->create());

        Livewire::test(Authors::class)
            ->call('create')
            ->set('name', 'Μαρία Χριστοδούλου')
            ->set('photo', UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'))
            ->call('save')
            ->assertHasErrors(['photo']);

        expect(Author::query()->count())->toBe(0)
            ->and(Media::query()->count())->toBe(0);
    });
});

describe('deleting authors', function () {
    it('deletes an author together with its photo', function () {
        $this->actingAs(User::factory()->editor()->create());
        $photo = Media::factory()->create();
        $author = Author::factory()->create(['photo_media_id' => $photo->id]);

        Livewire::test(Authors::class)->call('delete', $author->id);

        expect(Author::query()->find($author->id))->toBeNull()
            ->and(Media::query()->find($photo->id))->toBeNull();
    });

    it('keeps an author that still has articles, including trashed ones', function () {
        $this->actingAs(User::factory()->editor()->create());
        $article = Article::factory()->create();
        $article->delete();

        Livewire::test(Authors::class)->call('delete', $article->author_id);

        expect(Author::query()->find($article->author_id))->not->toBeNull();
    });
});
