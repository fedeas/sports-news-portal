<?php

namespace App\Livewire\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Actions\MakeSlug;
use App\Actions\ReplaceOwnedMedia;
use App\Actions\UploadMedia;
use App\Enums\MediaCollection;
use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Authors')]
class Authors extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $user_id = '';

    public string $bio = '';

    public string $email = '';

    public string $twitter = '';

    public string $facebook = '';

    public string $instagram = '';

    public bool $is_active = true;

    /**
     * @var TemporaryUploadedFile|null
     */
    public $photo = null;

    public bool $removePhoto = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Author::class);
    }

    /**
     * Open an empty form for a new author.
     */
    public function create(): void
    {
        $this->authorize('create', Author::class);

        $this->resetForm();

        Flux::modal('author-form')->show();
    }

    /**
     * Open the form pre-filled with an existing author.
     */
    public function edit(int $authorId): void
    {
        $author = Author::query()->findOrFail($authorId);

        $this->authorize('update', $author);

        $this->resetForm();

        $this->editingId = $author->id;
        $this->name = $author->name;
        $this->slug = $author->slug;
        $this->user_id = (string) $author->user_id;
        $this->bio = (string) $author->bio;
        $this->email = (string) $author->email;
        $this->is_active = $author->is_active;

        foreach (Author::SOCIAL_NETWORKS as $network) {
            $this->{$network} = $author->social_links[$network] ?? '';
        }

        Flux::modal('author-form')->show();
    }

    /**
     * Create or update the author in the form, including its photo.
     */
    public function save(GenerateUniqueSlug $generateUniqueSlug, MakeSlug $makeSlug, ReplaceOwnedMedia $replaceOwnedMedia): void
    {
        $author = $this->editingId === null
            ? new Author
            : Author::query()->with('photo')->findOrFail($this->editingId);

        if ($author->exists) {
            $this->authorize('update', $author);
        } else {
            $this->authorize('create', Author::class);
        }

        if ($this->slug !== '') {
            $this->slug = $makeSlug->handle($this->slug);
        } elseif ($this->name !== '') {
            $this->slug = $generateUniqueSlug->handle($this->name, Author::class, $author->id, 120);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique(Author::class)->ignore($author->id)],
            'user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id'), Rule::unique(Author::class)->ignore($author->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:150'],
            'twitter' => ['nullable', 'url:https', 'max:255'],
            'facebook' => ['nullable', 'url:https', 'max:255'],
            'instagram' => ['nullable', 'url:https', 'max:255'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadMedia::MAX_KILOBYTES],
        ], [
            'user_id.unique' => __('This account already has an author profile.'),
        ]);

        $author->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'user_id' => $validated['user_id'] ?: null,
            'bio' => $validated['bio'] ?: null,
            'email' => $validated['email'] ?: null,
            'social_links' => array_filter(Arr::only($validated, Author::SOCIAL_NETWORKS)) ?: null,
            'is_active' => $validated['is_active'],
        ]);

        $replaceOwnedMedia->handle($author, 'photo', $this->photo, $this->removePhoto, MediaCollection::Avatar, Auth::user(), $validated['name'], 'photo');

        $this->resetForm();

        Flux::modal('author-form')->close();
        Flux::toast(variant: 'success', text: __('Author saved.'));
    }

    /**
     * Delete an author together with its photo.
     */
    public function delete(int $authorId): void
    {
        $author = Author::query()->with('photo')->findOrFail($authorId);

        $this->authorize('delete', $author);

        if (Article::query()->withTrashed()->where('author_id', $author->id)->exists()) {
            Flux::toast(variant: 'danger', text: __('This author still has articles.'));

            return;
        }

        $photo = $author->photo;

        $author->delete();
        $photo?->delete();

        Flux::toast(variant: 'success', text: __('Author deleted.'));
    }

    /**
     * @return LengthAwarePaginator<int, Author>
     */
    #[Computed]
    public function authors(): LengthAwarePaginator
    {
        return Author::query()
            ->with(['photo', 'user'])
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * Staff accounts without an author profile, plus the one linked to the edited author.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function userOptions(): Collection
    {
        return User::query()
            ->whereDoesntHave('author', fn (Builder $query) => $query->when(
                $this->editingId !== null,
                fn (Builder $query) => $query->whereKeyNot($this->editingId),
            ))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function resetForm(): void
    {
        $this->reset(
            'editingId', 'name', 'slug', 'user_id', 'bio', 'email',
            'twitter', 'facebook', 'instagram', 'is_active', 'photo', 'removePhoto',
        );
        $this->resetValidation();
    }
}
