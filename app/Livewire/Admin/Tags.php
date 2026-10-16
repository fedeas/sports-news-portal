<?php

namespace App\Livewire\Admin;

use App\Actions\MakeSlug;
use App\Models\Tag;
use Closure;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tags')]
class Tags extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Tag::class);
    }

    /**
     * Go back to the first page whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open an empty form for a new tag.
     */
    public function create(): void
    {
        $this->authorize('create', Tag::class);

        $this->resetForm();

        Flux::modal('tag-form')->show();
    }

    /**
     * Open the form pre-filled with an existing tag.
     */
    public function edit(int $tagId): void
    {
        $tag = Tag::query()->findOrFail($tagId);

        $this->authorize('update', $tag);

        $this->resetForm();

        $this->editingId = $tag->id;
        $this->name = $tag->name;

        Flux::modal('tag-form')->show();
    }

    /**
     * Create or rename the tag in the form. The slug always follows the name.
     */
    public function save(MakeSlug $makeSlug): void
    {
        $tag = $this->editingId === null
            ? new Tag
            : Tag::query()->findOrFail($this->editingId);

        if ($tag->exists) {
            $this->authorize('update', $tag);
        } else {
            $this->authorize('create', Tag::class);
        }

        $this->name = trim($this->name);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:60', function (string $attribute, mixed $value, Closure $fail) use ($tag, $makeSlug) {
                $slug = $makeSlug->handle((string) $value);

                if ($slug === '') {
                    $fail(__('The tag name must contain letters or numbers.'));
                } elseif (strlen($slug) > 80) {
                    $fail(__('The tag name is too long.'));
                } elseif (Tag::query()->where('slug', $slug)->when($tag->exists, fn (Builder $query) => $query->whereKeyNot($tag->id))->exists()) {
                    $fail(__('A tag with this name already exists.'));
                }
            }],
        ]);

        $tag->fill([
            'name' => $validated['name'],
            'slug' => $makeSlug->handle($validated['name']),
        ])->save();

        $this->resetForm();

        Flux::modal('tag-form')->close();
        Flux::toast(variant: 'success', text: __('Tag saved.'));
    }

    /**
     * Delete a tag.
     */
    public function delete(int $tagId): void
    {
        $tag = Tag::query()->findOrFail($tagId);

        $this->authorize('delete', $tag);

        $tag->delete();

        Flux::toast(variant: 'success', text: __('Tag deleted.'));
    }

    /**
     * Tags matching the search term by name, or by slug so latin input finds Greek tags.
     *
     * @return LengthAwarePaginator<int, Tag>
     */
    #[Computed]
    public function tags(): LengthAwarePaginator
    {
        $search = trim($this->search);
        $searchSlug = app(MakeSlug::class)->handle($search);

        return Tag::query()
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search, $searchSlug) {
                $query->where('name', 'like', "%{$search}%");

                if ($searchSlug !== '') {
                    $query->orWhere('slug', 'like', "%{$searchSlug}%");
                }
            }))
            ->orderBy('name')
            ->paginate(30);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name');
        $this->resetValidation();
    }
}
