<?php

namespace App\Livewire\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Actions\MakeSlug;
use App\Models\Sport;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Sports')]
class Sports extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $icon = '';

    public string $sort_order = '0';

    public bool $is_active = true;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Sport::class);
    }

    /**
     * Open an empty form for a new sport.
     */
    public function create(): void
    {
        $this->authorize('create', Sport::class);

        $this->resetForm();

        Flux::modal('sport-form')->show();
    }

    /**
     * Open the form pre-filled with an existing sport.
     */
    public function edit(int $sportId): void
    {
        $sport = Sport::query()->findOrFail($sportId);

        $this->authorize('update', $sport);

        $this->resetForm();

        $this->editingId = $sport->id;
        $this->name = $sport->name;
        $this->slug = $sport->slug;
        $this->icon = (string) $sport->icon;
        $this->sort_order = (string) $sport->sort_order;
        $this->is_active = $sport->is_active;

        Flux::modal('sport-form')->show();
    }

    /**
     * Create or update the sport in the form.
     */
    public function save(GenerateUniqueSlug $generateUniqueSlug, MakeSlug $makeSlug): void
    {
        $sport = $this->editingId === null
            ? new Sport
            : Sport::query()->findOrFail($this->editingId);

        if ($sport->exists) {
            $this->authorize('update', $sport);
        } else {
            $this->authorize('create', Sport::class);
        }

        if ($this->slug !== '') {
            $this->slug = $makeSlug->handle($this->slug);
        } elseif ($this->name !== '') {
            $this->slug = $generateUniqueSlug->handle($this->name, Sport::class, $sport->id, 50);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:50'],
            'slug' => ['required', 'string', 'max:50', Rule::unique(Sport::class)->ignore($sport->id)],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['boolean'],
        ]);

        $sport->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'icon' => $validated['icon'] ?: null,
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => $validated['is_active'],
        ])->save();

        $this->resetForm();

        Flux::modal('sport-form')->close();
        Flux::toast(variant: 'success', text: __('Sport saved.'));
    }

    /**
     * Delete a sport that no longer has categories.
     */
    public function delete(int $sportId): void
    {
        $sport = Sport::query()->withCount(['categories', 'teams', 'leagues'])->findOrFail($sportId);

        $this->authorize('delete', $sport);

        if ($sport->categories_count + $sport->teams_count + $sport->leagues_count > 0) {
            Flux::toast(variant: 'danger', text: __('This sport is still used by categories, teams or leagues.'));

            return;
        }

        $sport->delete();

        Flux::toast(variant: 'success', text: __('Sport deleted.'));
    }

    /**
     * @return LengthAwarePaginator<int, Sport>
     */
    #[Computed]
    public function sports(): LengthAwarePaginator
    {
        return Sport::query()
            ->withCount('categories')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'slug', 'icon', 'sort_order', 'is_active');
        $this->resetValidation();
    }
}
