<?php

namespace App\Livewire\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Actions\MakeSlug;
use App\Actions\ReplaceOwnedMedia;
use App\Actions\UploadMedia;
use App\Enums\MediaCollection;
use App\Models\Fixture;
use App\Models\Sport;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Teams')]
class Teams extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $sport_id = '';

    public string $short_name = '';

    public string $country = '';

    public string $founded_year = '';

    public string $external_source = '';

    public string $external_id = '';

    public bool $is_active = true;

    /**
     * @var TemporaryUploadedFile|null
     */
    public $logo = null;

    public bool $removeLogo = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Team::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open an empty form for a new team.
     */
    public function create(): void
    {
        $this->authorize('create', Team::class);

        $this->resetForm();

        Flux::modal('team-form')->show();
    }

    /**
     * Open the form pre-filled with an existing team.
     */
    public function edit(int $teamId): void
    {
        $team = Team::query()->findOrFail($teamId);

        $this->authorize('update', $team);

        $this->resetForm();

        $this->editingId = $team->id;
        $this->name = $team->name;
        $this->slug = $team->slug;
        $this->sport_id = (string) $team->sport_id;
        $this->short_name = (string) $team->short_name;
        $this->country = (string) $team->country;
        $this->founded_year = (string) $team->founded_year;
        $this->external_source = (string) $team->external_source;
        $this->external_id = (string) $team->external_id;
        $this->is_active = $team->is_active;

        Flux::modal('team-form')->show();
    }

    /**
     * Create or update the team in the form, including its logo.
     */
    public function save(GenerateUniqueSlug $generateUniqueSlug, MakeSlug $makeSlug, ReplaceOwnedMedia $replaceOwnedMedia): void
    {
        $team = $this->editingId === null
            ? new Team
            : Team::query()->with('logo')->findOrFail($this->editingId);

        if ($team->exists) {
            $this->authorize('update', $team);
        } else {
            $this->authorize('create', Team::class);
        }

        if ($this->slug !== '') {
            $this->slug = $makeSlug->handle($this->slug);
        } elseif ($this->name !== '') {
            $this->slug = $generateUniqueSlug->handle($this->name, Team::class, $team->id, 120);
        }

        $this->country = Str::upper(trim($this->country));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique(Team::class)->ignore($team->id)],
            'sport_id' => ['required', 'integer', Rule::exists(Sport::class, 'id')],
            'short_name' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'regex:/^[A-Z]{2}$/'],
            'founded_year' => ['nullable', 'integer', 'min:1850', 'max:'.now()->year],
            'external_source' => ['nullable', 'string', 'max:30', 'required_with:external_id'],
            'external_id' => [
                'nullable', 'string', 'max:60', 'required_with:external_source',
                Rule::unique(Team::class)->where('external_source', $this->external_source)->ignore($team->id),
            ],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadMedia::MAX_KILOBYTES],
        ], [
            'country.regex' => __('Use a two-letter country code, e.g. CY or GR.'),
            'external_id.unique' => __('This external ID is already used by another team from the same source.'),
        ]);

        $team->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'sport_id' => (int) $validated['sport_id'],
            'short_name' => $validated['short_name'] ?: null,
            'country' => $validated['country'] ?: null,
            'founded_year' => $validated['founded_year'] ?: null,
            'external_source' => $validated['external_source'] ?: null,
            'external_id' => $validated['external_id'] ?: null,
            'is_active' => $validated['is_active'],
        ]);

        $replaceOwnedMedia->handle($team, 'logo', $this->logo, $this->removeLogo, MediaCollection::Logo, Auth::user(), $validated['name'], 'logo');

        $this->resetForm();

        Flux::modal('team-form')->close();
        Flux::toast(variant: 'success', text: __('Team saved.'));
    }

    /**
     * Delete a team together with its logo.
     */
    public function delete(int $teamId): void
    {
        $team = Team::query()->with('logo')->findOrFail($teamId);

        $this->authorize('delete', $team);

        if (Fixture::query()->involving($team)->exists()) {
            Flux::toast(variant: 'danger', text: __('This team still has fixtures.'));

            return;
        }

        $logo = $team->logo;

        $team->delete();
        $logo?->delete();

        Flux::toast(variant: 'success', text: __('Team deleted.'));
    }

    /**
     * @return LengthAwarePaginator<int, Team>
     */
    #[Computed]
    public function teams(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Team::query()
            ->with(['sport', 'logo'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('short_name', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(30);
    }

    /**
     * @return Collection<int, Sport>
     */
    #[Computed]
    public function sports(): Collection
    {
        return Sport::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
    }

    private function resetForm(): void
    {
        $this->reset(
            'editingId', 'name', 'slug', 'sport_id', 'short_name', 'country', 'founded_year',
            'external_source', 'external_id', 'is_active', 'logo', 'removeLogo',
        );
        $this->resetValidation();
    }
}
