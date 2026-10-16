<?php

namespace App\Livewire\Admin;

use App\Actions\GenerateUniqueSlug;
use App\Actions\MakeSlug;
use App\Actions\ReplaceOwnedMedia;
use App\Actions\UploadMedia;
use App\Enums\MediaCollection;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Sport;
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
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Leagues')]
class Leagues extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public string $sport_id = '';

    public string $country = '';

    public string $tier = '';

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
        $this->authorize('viewAny', League::class);
    }

    /**
     * Open an empty form for a new league.
     */
    public function create(): void
    {
        $this->authorize('create', League::class);

        $this->resetForm();

        Flux::modal('league-form')->show();
    }

    /**
     * Open the form pre-filled with an existing league.
     */
    public function edit(int $leagueId): void
    {
        $league = League::query()->findOrFail($leagueId);

        $this->authorize('update', $league);

        $this->resetForm();

        $this->editingId = $league->id;
        $this->name = $league->name;
        $this->slug = $league->slug;
        $this->sport_id = (string) $league->sport_id;
        $this->country = (string) $league->country;
        $this->tier = (string) $league->tier;
        $this->external_source = (string) $league->external_source;
        $this->external_id = (string) $league->external_id;
        $this->is_active = $league->is_active;

        Flux::modal('league-form')->show();
    }

    /**
     * Create or update the league in the form, including its logo.
     */
    public function save(GenerateUniqueSlug $generateUniqueSlug, MakeSlug $makeSlug, ReplaceOwnedMedia $replaceOwnedMedia): void
    {
        $league = $this->editingId === null
            ? new League
            : League::query()->with('logo')->findOrFail($this->editingId);

        if ($league->exists) {
            $this->authorize('update', $league);
        } else {
            $this->authorize('create', League::class);
        }

        if ($this->slug !== '') {
            $this->slug = $makeSlug->handle($this->slug);
        } elseif ($this->name !== '') {
            $this->slug = $generateUniqueSlug->handle($this->name, League::class, $league->id, 120);
        }

        $this->country = Str::upper(trim($this->country));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', Rule::unique(League::class)->ignore($league->id)],
            'sport_id' => ['required', 'integer', Rule::exists(Sport::class, 'id')],
            'country' => ['nullable', 'regex:/^[A-Z]{2}$/'],
            'tier' => ['nullable', 'integer', 'min:1', 'max:20'],
            'external_source' => ['nullable', 'string', 'max:30', 'required_with:external_id'],
            'external_id' => [
                'nullable', 'string', 'max:60', 'required_with:external_source',
                Rule::unique(League::class)->where('external_source', $this->external_source)->ignore($league->id),
            ],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.UploadMedia::MAX_KILOBYTES],
        ], [
            'country.regex' => __('Use a two-letter country code, e.g. CY or GR.'),
            'external_id.unique' => __('This external ID is already used by another league from the same source.'),
        ]);

        $league->fill([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'sport_id' => (int) $validated['sport_id'],
            'country' => $validated['country'] ?: null,
            'tier' => $validated['tier'] ?: null,
            'external_source' => $validated['external_source'] ?: null,
            'external_id' => $validated['external_id'] ?: null,
            'is_active' => $validated['is_active'],
        ]);

        $replaceOwnedMedia->handle($league, 'logo', $this->logo, $this->removeLogo, MediaCollection::Logo, Auth::user(), $validated['name'], 'logo');

        $this->resetForm();

        Flux::modal('league-form')->close();
        Flux::toast(variant: 'success', text: __('League saved.'));
    }

    /**
     * Delete a league together with its seasons and logo.
     */
    public function delete(int $leagueId): void
    {
        $league = League::query()->with('logo')->findOrFail($leagueId);

        $this->authorize('delete', $league);

        if (Fixture::query()->whereHas('season', fn (Builder $query) => $query->where('league_id', $league->id))->exists()) {
            Flux::toast(variant: 'danger', text: __('This league still has fixtures.'));

            return;
        }

        $logo = $league->logo;

        $league->delete();
        $logo?->delete();

        Flux::toast(variant: 'success', text: __('League deleted.'));
    }

    /**
     * @return LengthAwarePaginator<int, League>
     */
    #[Computed]
    public function leagues(): LengthAwarePaginator
    {
        return League::query()
            ->with(['sport', 'logo'])
            ->withCount('seasons')
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
            'editingId', 'name', 'slug', 'sport_id', 'country', 'tier',
            'external_source', 'external_id', 'is_active', 'logo', 'removeLogo',
        );
        $this->resetValidation();
    }
}
