<?php

namespace App\Livewire\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Actions\MakeSlug;
use App\Enums\SectionIcon;
use App\Models\Article;
use App\Models\Category;
use App\Models\Sport;
use App\Rules\NotReservedSlug;
use Flux\Flux;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Categories')]
class Categories extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $icon = '';

    public string $sport_id = '';

    public string $parent_id = '';

    public string $description = '';

    public string $meta_title = '';

    public string $meta_description = '';

    public string $sort_order = '0';

    public bool $is_active = true;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    /**
     * Open an empty form for a new category.
     */
    public function create(): void
    {
        $this->authorize('create', Category::class);

        $this->resetForm();

        Flux::modal('category-form')->show();
    }

    /**
     * Open the form pre-filled with an existing category.
     */
    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->authorize('update', $category);

        $this->resetForm();

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->icon = (string) $category->icon?->value;
        $this->sport_id = (string) $category->sport_id;
        $this->parent_id = (string) $category->parent_id;
        $this->description = (string) $category->description;
        $this->meta_title = (string) $category->meta_title;
        $this->meta_description = (string) $category->meta_description;
        $this->sort_order = (string) $category->sort_order;
        $this->is_active = $category->is_active;

        Flux::modal('category-form')->show();
    }

    /**
     * Create or update the category in the form.
     */
    public function save(GenerateUniqueSlug $generateUniqueSlug, MakeSlug $makeSlug): void
    {
        $category = $this->editingId === null
            ? new Category
            : Category::query()->findOrFail($this->editingId);

        if ($category->exists) {
            $this->authorize('update', $category);
        } else {
            $this->authorize('create', Category::class);
        }

        if ($this->slug !== '') {
            $this->slug = $makeSlug->handle($this->slug);
        } elseif ($this->name !== '') {
            $this->slug = $generateUniqueSlug->handle(
                $this->name,
                Category::class,
                $category->id,
                120,
                config('reserved-slugs'),
                [Article::class],
            );
        }

        $parentRules = ['nullable', 'integer', Rule::exists(Category::class, 'id')->whereNull('parent_id')];

        if ($category->exists) {
            $parentRules[] = Rule::notIn([$category->id]);

            if ($category->children()->exists()) {
                $parentRules[] = 'prohibited';
            }
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique(Category::class)->ignore($category->id), Rule::unique(Article::class, 'slug'), new NotReservedSlug],
            'icon' => ['nullable', Rule::enum(SectionIcon::class)],
            'sport_id' => ['nullable', 'integer', Rule::exists(Sport::class, 'id')],
            'parent_id' => $parentRules,
            'description' => ['nullable', 'string', 'max:5000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ], [
            'parent_id.exists' => __('Choose a top-level category as the parent.'),
            'parent_id.not_in' => __('A category cannot be its own parent.'),
            'parent_id.prohibited' => __('A category with subcategories cannot be moved under another category.'),
        ]);

        $category->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'icon' => $validated['icon'] ?: null,
            'sport_id' => $validated['sport_id'] ?: null,
            'parent_id' => $validated['parent_id'] ?: null,
            'description' => $validated['description'] ?: null,
            'meta_title' => $validated['meta_title'] ?: null,
            'meta_description' => $validated['meta_description'] ?: null,
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => $validated['is_active'],
        ])->save();

        $this->resetForm();

        Flux::modal('category-form')->close();
        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    /**
     * Delete a category that has no subcategories.
     */
    public function delete(int $categoryId): void
    {
        $category = Category::query()->withCount('children')->findOrFail($categoryId);

        $this->authorize('delete', $category);

        if ($category->children_count > 0) {
            Flux::toast(variant: 'danger', text: __('Delete or move its subcategories first.'));

            return;
        }

        if (Article::query()->withTrashed()->where('category_id', $category->id)->exists()) {
            Flux::toast(variant: 'danger', text: __('This category still has articles.'));

            return;
        }

        $category->delete();

        Flux::toast(variant: 'success', text: __('Category deleted.'));
    }

    /**
     * Top-level categories, each with its subcategories.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        return Category::query()
            ->whereNull('parent_id')
            ->with([
                'sport',
                'children' => fn (Builder $query) => $query->with('sport')->orderBy('sort_order')->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * @return Collection<int, Sport>
     */
    #[Computed]
    public function sports(): Collection
    {
        return Sport::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Top-level categories that the edited category may be placed under.
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function parentOptions(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->when($this->editingId !== null, fn ($query) => $query->whereKeyNot($this->editingId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resetForm(): void
    {
        $this->reset(
            'editingId', 'name', 'slug', 'icon', 'sport_id', 'parent_id', 'description',
            'meta_title', 'meta_description', 'sort_order', 'is_active',
        );
        $this->resetValidation();
    }
}
