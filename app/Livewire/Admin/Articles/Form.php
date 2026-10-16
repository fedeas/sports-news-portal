<?php

namespace App\Livewire\Admin\Articles;

use App\Actions\GenerateUniqueSlug;
use App\Actions\MakeSlug;
use App\Actions\SaveArticle;
use App\Actions\UploadMedia;
use App\Enums\ArticleStatus;
use App\Enums\MediaCollection;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\League;
use App\Models\Media;
use App\Models\Tag;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Stevebauman\Purify\Facades\Purify;

#[Title('Article')]
class Form extends Component
{
    use WithFileUploads;

    public const MAX_TAGS = 20;

    public const MAX_TEAMS = 10;

    public const MAX_LEAGUES = 5;

    #[Locked]
    public ?int $articleId = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $body = '';

    public string $category_id = '';

    public string $author_id = '';

    /**
     * @var array<int, string>
     */
    public array $co_author_ids = [];

    /**
     * @var array<int, string>
     */
    public array $tag_names = [];

    public string $newTag = '';

    /**
     * @var array<int, string>
     */
    public array $team_ids = [];

    /**
     * @var array<int, string>
     */
    public array $league_ids = [];

    public string $subjectSearch = '';

    public bool $is_breaking = false;

    public bool $is_featured = false;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $canonical_url = '';

    public string $status = 'draft';

    /**
     * Local (display timezone) date and time in `Y-m-d\TH:i` format.
     */
    public string $publish_at = '';

    /**
     * @var TemporaryUploadedFile|null
     */
    public $featuredImage = null;

    public string $featured_image_alt = '';

    public bool $removeFeaturedImage = false;

    /**
     * Mount the component for a new article, or pre-fill it from an existing one.
     */
    public function mount(?Article $article = null): void
    {
        if ($article === null || ! $article->exists) {
            $this->authorize('create', Article::class);

            $this->author_id = (string) Auth::user()->author?->id;

            return;
        }

        $this->authorize('update', $article);

        $article->load(['tags', 'coAuthors', 'featuredImage', 'teams', 'leagues']);

        $this->articleId = $article->id;
        $this->title = $article->title;
        $this->slug = $article->slug;
        $this->excerpt = (string) $article->excerpt;
        $this->body = $article->body;
        $this->category_id = (string) $article->category_id;
        $this->author_id = (string) $article->author_id;
        $this->co_author_ids = $article->coAuthors->map(fn (Author $author) => (string) $author->id)->all();
        $this->tag_names = $article->tags->pluck('name')->all();
        $this->team_ids = $article->teams->map(fn (Team $team) => (string) $team->id)->all();
        $this->league_ids = $article->leagues->map(fn (League $league) => (string) $league->id)->all();
        $this->is_breaking = $article->is_breaking;
        $this->is_featured = $article->is_featured;
        $this->meta_title = (string) $article->meta_title;
        $this->meta_description = (string) $article->meta_description;
        $this->canonical_url = (string) $article->canonical_url;
        $this->status = $article->status === ArticleStatus::Scheduled
            ? ArticleStatus::Published->value
            : $article->status->value;
        $this->publish_at = (string) $article->published_at
            ?->timezone(config('app.display_timezone'))
            ->format('Y-m-d\TH:i');
        $this->featured_image_alt = (string) $article->featuredImage?->alt_text;
    }

    /**
     * Validate and save the article, its featured image, tags and co-authors.
     */
    public function save(GenerateUniqueSlug $generateUniqueSlug, MakeSlug $makeSlug, SaveArticle $saveArticle, UploadMedia $uploadMedia): void
    {
        $user = Auth::user();

        $article = $this->articleId === null
            ? new Article
            : Article::query()->with(['author', 'featuredImage'])->findOrFail($this->articleId);

        if ($article->exists) {
            $this->authorize('update', $article);
        } else {
            $this->authorize('create', Article::class);
        }

        $canPublish = $user->can('publish', Article::class);

        if ($this->slug !== '') {
            $this->slug = $makeSlug->handle($this->slug);
        } elseif ($this->title !== '') {
            $this->slug = $generateUniqueSlug->handle($this->title, Article::class, $article->id, 220, alsoUniqueAgainst: [Category::class]);
        }

        $this->body = Purify::config('article')->clean($this->body);

        if (trim(html_entity_decode(strip_tags($this->body))) === '') {
            $this->body = '';
        }

        $hasFeaturedImage = $this->featuredImage !== null
            || ($article->featured_image_id !== null && ! $this->removeFeaturedImage);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:220', Rule::unique(Article::class)->ignore($article->id), Rule::unique(Category::class, 'slug')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:200000'],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'author_id' => ['required', 'integer', Rule::exists(Author::class, 'id'), ...($canPublish ? [] : [Rule::in([$user->author?->id])])],
            'co_author_ids' => ['array', 'max:5'],
            'co_author_ids.*' => ['integer', 'distinct', Rule::exists(Author::class, 'id')],
            'tag_names' => ['array', 'max:'.self::MAX_TAGS],
            'tag_names.*' => ['string', 'max:60'],
            'team_ids' => ['array', 'max:'.self::MAX_TEAMS],
            'team_ids.*' => ['integer', 'distinct', Rule::exists(Team::class, 'id')],
            'league_ids' => ['array', 'max:'.self::MAX_LEAGUES],
            'league_ids.*' => ['integer', 'distinct', Rule::exists(League::class, 'id')],
            'is_breaking' => ['boolean', ...($canPublish ? [] : ['declined'])],
            'is_featured' => ['boolean', ...($canPublish ? [] : ['declined'])],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url:https', 'max:255'],
            'status' => ['required', Rule::in($canPublish ? ['draft', 'published', 'archived'] : ['draft'])],
            'publish_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'featuredImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadMedia::MAX_KILOBYTES],
            'featured_image_alt' => [$hasFeaturedImage ? 'required' : 'nullable', 'string', 'max:255'],
        ], [
            'slug.unique' => __('This slug is already used by another article or category.'),
            'author_id.in' => __('You can only write under your own author profile.'),
            'status.in' => __('Only editors can publish or archive articles.'),
            'is_breaking.declined' => __('Only editors can mark articles as breaking or featured.'),
            'is_featured.declined' => __('Only editors can mark articles as breaking or featured.'),
            'featured_image_alt.required' => __('Describe the featured image for screen readers.'),
        ]);

        $newFeaturedImage = null;

        if ($this->featuredImage !== null) {
            try {
                $newFeaturedImage = $uploadMedia->handle($this->featuredImage, MediaCollection::FeaturedImage, $user, $validated['featured_image_alt']);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages(['featuredImage' => Arr::first(Arr::flatten($exception->errors()))]);
            }
        }

        $oldFeaturedImage = $article->featuredImage;

        if ($newFeaturedImage !== null) {
            $article->featuredImage()->associate($newFeaturedImage);
        } elseif ($this->removeFeaturedImage) {
            $article->featuredImage()->dissociate();
        } elseif ($oldFeaturedImage !== null) {
            $oldFeaturedImage->alt_text = $validated['featured_image_alt'];
            $oldFeaturedImage->save();
        }

        $article = $saveArticle->handle($article, [
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'excerpt' => $validated['excerpt'],
            'body' => $validated['body'],
            'category_id' => (int) $validated['category_id'],
            'author_id' => (int) $validated['author_id'],
            'co_author_ids' => array_map('intval', $validated['co_author_ids'] ?? []),
            'tag_names' => $validated['tag_names'] ?? [],
            'team_ids' => array_map('intval', $validated['team_ids'] ?? []),
            'league_ids' => array_map('intval', $validated['league_ids'] ?? []),
            'is_breaking' => $validated['is_breaking'],
            'is_featured' => $validated['is_featured'],
            'meta_title' => $validated['meta_title'],
            'meta_description' => $validated['meta_description'],
            'canonical_url' => $validated['canonical_url'],
            'status' => ArticleStatus::from($validated['status']),
            'publish_at' => $validated['publish_at']
                ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $validated['publish_at'], config('app.display_timezone'))->utc()
                : null,
        ], $user);

        $newFeaturedImage?->mediable()->associate($article)->save();

        if ($oldFeaturedImage !== null && $article->featured_image_id !== $oldFeaturedImage->id) {
            $oldFeaturedImage->delete();
        }

        if ($this->articleId === null) {
            session()->flash('status', __('Article saved.'));

            $this->redirectRoute('admin.articles.edit', ['article' => $article], navigate: true);

            return;
        }

        $this->reset('featuredImage', 'removeFeaturedImage');

        Flux::toast(variant: 'success', text: __('Article saved.'));
    }

    /**
     * Add the typed tag, reusing the name of an existing tag with the same slug.
     */
    public function addTag(MakeSlug $makeSlug): void
    {
        $this->validate(['newTag' => ['required', 'string', 'max:60']]);

        $slug = $makeSlug->handle($this->newTag);

        if ($slug === '') {
            $this->addError('newTag', __('The tag name must contain letters or numbers.'));

            return;
        }

        $this->addTagName(Tag::query()->where('slug', $slug)->value('name') ?? trim($this->newTag), $makeSlug);
    }

    /**
     * Add an existing tag picked from the suggestions.
     */
    public function pickTag(int $tagId, MakeSlug $makeSlug): void
    {
        $this->addTagName(Tag::query()->findOrFail($tagId)->name, $makeSlug);
    }

    public function removeTag(int $index): void
    {
        unset($this->tag_names[$index]);

        $this->tag_names = array_values($this->tag_names);
    }

    /**
     * Mark the article as being about an active team.
     */
    public function addTeam(int $teamId): void
    {
        $team = Team::query()->active()->findOrFail($teamId);

        if (! in_array((string) $team->id, $this->team_ids, true) && count($this->team_ids) < self::MAX_TEAMS) {
            $this->team_ids[] = (string) $team->id;
        }

        $this->reset('subjectSearch');
    }

    public function removeTeam(int $teamId): void
    {
        $this->team_ids = array_values(array_filter($this->team_ids, fn (mixed $id) => (int) $id !== $teamId));
    }

    /**
     * Mark the article as being about an active league.
     */
    public function addLeague(int $leagueId): void
    {
        $league = League::query()->active()->findOrFail($leagueId);

        if (! in_array((string) $league->id, $this->league_ids, true) && count($this->league_ids) < self::MAX_LEAGUES) {
            $this->league_ids[] = (string) $league->id;
        }

        $this->reset('subjectSearch');
    }

    public function removeLeague(int $leagueId): void
    {
        $this->league_ids = array_values(array_filter($this->league_ids, fn (mixed $id) => (int) $id !== $leagueId));
    }

    /**
     * A shareable preview link for the article being edited.
     */
    #[Computed]
    public function previewUrl(): ?string
    {
        return $this->articleId === null ? null : Article::query()->findOrFail($this->articleId)->previewUrl();
    }

    /**
     * Active teams and leagues matching the search, by name or transliterated slug, not yet selected.
     *
     * @return array{teams: Collection<int, Team>, leagues: Collection<int, League>}
     */
    #[Computed]
    public function subjectSuggestions(): array
    {
        $search = trim($this->subjectSearch);

        if (mb_strlen($search) < 2) {
            return ['teams' => new Collection, 'leagues' => new Collection];
        }

        $slug = app(MakeSlug::class)->handle($search);

        $matches = fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->when($slug !== '', fn (Builder $query) => $query->orWhere('slug', 'like', "%{$slug}%"));

        return [
            'teams' => Team::query()->active()->where($matches)
                ->whereKeyNot(array_map('intval', $this->team_ids))
                ->orderBy('name')->limit(6)->get(['id', 'name']),
            'leagues' => League::query()->active()->where($matches)
                ->whereKeyNot(array_map('intval', $this->league_ids))
                ->orderBy('name')->limit(4)->get(['id', 'name']),
        ];
    }

    /**
     * @return Collection<int, Team>
     */
    #[Computed]
    public function selectedTeams(): Collection
    {
        return Team::query()->whereKey(array_map('intval', $this->team_ids))->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, League>
     */
    #[Computed]
    public function selectedLeagues(): Collection
    {
        return League::query()->whereKey(array_map('intval', $this->league_ids))->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Existing tags matching what is being typed, by name or by transliterated slug.
     *
     * @return Collection<int, Tag>
     */
    #[Computed]
    public function tagSuggestions(): Collection
    {
        $search = trim($this->newTag);

        if (mb_strlen($search) < 2) {
            return new Collection;
        }

        $slug = app(MakeSlug::class)->handle($search);

        return Tag::query()
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->when($slug !== '', fn (Builder $query) => $query->orWhere('slug', 'like', "%{$slug}%")))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name']);
    }

    /**
     * Top-level categories with their subcategories.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryOptions(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->with(['children' => fn (HasMany $query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Author>
     */
    #[Computed]
    public function authorOptions(): Collection
    {
        return Author::query()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function currentFeaturedImage(): ?Media
    {
        return $this->articleId === null
            ? null
            : Article::query()->with('featuredImage')->find($this->articleId)?->featuredImage;
    }

    private function addTagName(string $name, MakeSlug $makeSlug): void
    {
        $slug = $makeSlug->handle($name);

        $alreadyAdded = collect($this->tag_names)
            ->contains(fn (mixed $tagName) => is_string($tagName) && $makeSlug->handle($tagName) === $slug);

        if (! $alreadyAdded && count($this->tag_names) < self::MAX_TAGS) {
            $this->tag_names[] = $name;
        }

        $this->reset('newTag');
    }
}
