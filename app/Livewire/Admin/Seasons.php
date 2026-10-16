<?php

namespace App\Livewire\Admin;

use App\Actions\RecalculateStandings;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Seasons')]
class Seasons extends Component
{
    use WithPagination;

    /**
     * The league the list is filtered by.
     */
    #[Url(except: '')]
    public string $league = '';

    #[Locked]
    public ?int $editingId = null;

    public string $league_id = '';

    public string $name = '';

    public string $starts_on = '';

    public string $ends_on = '';

    public bool $is_current = false;

    public string $external_id = '';

    #[Locked]
    public ?int $rosterSeasonId = null;

    /**
     * @var array<int, string>
     */
    public array $roster_team_ids = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Season::class);
    }

    public function updatedLeague(): void
    {
        $this->resetPage();
    }

    /**
     * Open an empty form for a new season, for the filtered league if there is one.
     */
    public function create(): void
    {
        $this->authorize('create', Season::class);

        $this->resetForm();
        $this->league_id = $this->league;

        Flux::modal('season-form')->show();
    }

    /**
     * Open the form pre-filled with an existing season.
     */
    public function edit(int $seasonId): void
    {
        $season = Season::query()->findOrFail($seasonId);

        $this->authorize('update', $season);

        $this->resetForm();

        $this->editingId = $season->id;
        $this->league_id = (string) $season->league_id;
        $this->name = $season->name;
        $this->starts_on = (string) $season->starts_on?->format('Y-m-d');
        $this->ends_on = (string) $season->ends_on?->format('Y-m-d');
        $this->is_current = $season->is_current;
        $this->external_id = (string) $season->external_id;

        Flux::modal('season-form')->show();
    }

    /**
     * Create or update the season. Marking it current unmarks the league's other seasons.
     */
    public function save(): void
    {
        $season = $this->editingId === null
            ? new Season
            : Season::query()->findOrFail($this->editingId);

        if ($season->exists) {
            $this->authorize('update', $season);
        } else {
            $this->authorize('create', Season::class);
        }

        $validated = $this->validate([
            'league_id' => ['required', 'integer', Rule::exists(League::class, 'id')],
            'name' => [
                'required', 'string', 'max:20', 'regex:/^\d{4}(\/\d{4})?$/',
                Rule::unique(Season::class)->where('league_id', (int) $this->league_id)->ignore($season->id),
            ],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', ...($this->starts_on !== '' ? ['after_or_equal:starts_on'] : [])],
            'is_current' => ['boolean'],
            'external_id' => ['nullable', 'string', 'max:60'],
        ], [
            'name.regex' => __('Use the format 2025/2026 or 2026.'),
            'name.unique' => __('This league already has a season with this name.'),
        ]);

        DB::transaction(function () use ($season, $validated) {
            $season->fill([
                'league_id' => (int) $validated['league_id'],
                'name' => $validated['name'],
                'starts_on' => $validated['starts_on'] ?: null,
                'ends_on' => $validated['ends_on'] ?: null,
                'is_current' => $validated['is_current'],
                'external_id' => $validated['external_id'] ?: null,
            ])->save();

            if ($season->is_current) {
                Season::query()
                    ->where('league_id', $season->league_id)
                    ->whereKeyNot($season->id)
                    ->update(['is_current' => false]);
            }
        });

        $this->resetForm();

        Flux::modal('season-form')->close();
        Flux::toast(variant: 'success', text: __('Season saved.'));
    }

    public function delete(int $seasonId): void
    {
        $season = Season::query()->findOrFail($seasonId);

        $this->authorize('delete', $season);

        if (Fixture::query()->where('season_id', $season->id)->exists()) {
            Flux::toast(variant: 'danger', text: __('This season still has fixtures.'));

            return;
        }

        $season->delete();

        Flux::toast(variant: 'success', text: __('Season deleted.'));
    }

    /**
     * Open the roster of teams taking part in a season.
     */
    public function editRoster(int $seasonId): void
    {
        $season = Season::query()->findOrFail($seasonId);

        $this->authorize('update', $season);

        $this->rosterSeasonId = $season->id;
        $this->roster_team_ids = $season->teams()->pluck('teams.id')->map(fn (int $id) => (string) $id)->all();
        $this->resetValidation();

        Flux::modal('season-roster')->show();
    }

    /**
     * Save the roster and rebuild the standings so every team has a row.
     */
    public function saveRoster(RecalculateStandings $recalculateStandings): void
    {
        $season = Season::query()->with('league')->findOrFail($this->rosterSeasonId);

        $this->authorize('update', $season);

        $validated = $this->validate([
            'roster_team_ids' => ['array', 'max:40'],
            'roster_team_ids.*' => ['integer', 'distinct', Rule::exists(Team::class, 'id')->where('sport_id', $season->league->sport_id)],
        ], [
            'roster_team_ids.*.exists' => __('Choose teams from the league\'s sport.'),
        ]);

        $season->teams()->sync(array_map('intval', $validated['roster_team_ids'] ?? []));
        $recalculateStandings->handle($season);

        Flux::modal('season-roster')->close();
        Flux::toast(variant: 'success', text: __('Teams saved.'));
    }

    /**
     * Active teams of the roster season's sport.
     *
     * @return Collection<int, Team>
     */
    #[Computed]
    public function rosterOptions(): Collection
    {
        $season = $this->rosterSeasonId === null ? null : Season::query()->with('league')->find($this->rosterSeasonId);

        if ($season === null) {
            return new Collection;
        }

        return Team::query()->active()->where('sport_id', $season->league->sport_id)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return LengthAwarePaginator<int, Season>
     */
    #[Computed]
    public function seasons(): LengthAwarePaginator
    {
        return Season::query()
            ->with('league')
            ->when($this->league !== '', fn (Builder $query) => $query->where('league_id', (int) $this->league))
            ->orderByDesc('name')
            ->paginate(30);
    }

    /**
     * @return Collection<int, League>
     */
    #[Computed]
    public function leagues(): Collection
    {
        return League::query()->orderBy('name')->get(['id', 'name']);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'league_id', 'name', 'starts_on', 'ends_on', 'is_current', 'external_id');
        $this->resetValidation();
    }
}
