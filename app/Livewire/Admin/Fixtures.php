<?php

namespace App\Livewire\Admin;

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Fixtures')]
class Fixtures extends Component
{
    use WithPagination;

    /**
     * The season the list is filtered by.
     */
    #[Url(except: '')]
    public string $season = '';

    /**
     * The status the list is filtered by.
     */
    #[Url(except: '')]
    public string $status = '';

    #[Locked]
    public ?int $editingId = null;

    public string $season_id = '';

    public string $home_team_id = '';

    public string $away_team_id = '';

    /**
     * Local (display timezone) kickoff in `Y-m-d\TH:i` format.
     */
    public string $kickoff_at = '';

    public string $fixture_status = 'scheduled';

    public string $score_home = '';

    public string $score_away = '';

    public string $minute = '';

    public string $round = '';

    public string $venue = '';

    public string $external_source = '';

    public string $external_id = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Fixture::class);
    }

    /**
     * Go back to the first page whenever a filter changes.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['season', 'status'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Open an empty form for a new fixture, for the filtered season if there is one.
     */
    public function create(): void
    {
        $this->authorize('create', Fixture::class);

        $this->resetForm();
        $this->season_id = $this->season;

        Flux::modal('fixture-form')->show();
    }

    /**
     * Open the form pre-filled with an existing fixture.
     */
    public function edit(int $fixtureId): void
    {
        $fixture = Fixture::query()->findOrFail($fixtureId);

        $this->authorize('update', $fixture);

        $this->resetForm();

        $this->editingId = $fixture->id;
        $this->season_id = (string) $fixture->season_id;
        $this->home_team_id = (string) $fixture->home_team_id;
        $this->away_team_id = (string) $fixture->away_team_id;
        $this->kickoff_at = $fixture->kickoff_at->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i');
        $this->fixture_status = $fixture->status->value;
        $this->score_home = (string) $fixture->score_home;
        $this->score_away = (string) $fixture->score_away;
        $this->minute = (string) $fixture->minute;
        $this->round = (string) $fixture->round;
        $this->venue = (string) $fixture->venue;
        $this->external_source = (string) $fixture->external_source;
        $this->external_id = (string) $fixture->external_id;

        Flux::modal('fixture-form')->show();
    }

    /**
     * Create or update the fixture. Saving a finished result updates the standings.
     */
    public function save(): void
    {
        $fixture = $this->editingId === null
            ? new Fixture
            : Fixture::query()->findOrFail($this->editingId);

        if ($fixture->exists) {
            $this->authorize('update', $fixture);
        } else {
            $this->authorize('create', Fixture::class);
        }

        $scoreRequired = FixtureStatus::tryFrom($this->fixture_status)?->hasScore() ?? false;

        $validated = $this->validate([
            'season_id' => ['required', 'integer', Rule::exists(Season::class, 'id')],
            'home_team_id' => ['required', 'integer', Rule::exists(Team::class, 'id')],
            'away_team_id' => ['required', 'integer', 'different:home_team_id', Rule::exists(Team::class, 'id')],
            'kickoff_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'fixture_status' => ['required', Rule::enum(FixtureStatus::class)],
            'score_home' => [$scoreRequired ? 'required' : 'nullable', 'integer', 'min:0', 'max:99'],
            'score_away' => [$scoreRequired ? 'required' : 'nullable', 'integer', 'min:0', 'max:99'],
            'minute' => ['nullable', 'integer', 'min:0', 'max:150'],
            'round' => ['nullable', 'string', 'max:30'],
            'venue' => ['nullable', 'string', 'max:150'],
            'external_source' => ['nullable', 'string', 'max:30', 'required_with:external_id'],
            'external_id' => [
                'nullable', 'string', 'max:60', 'required_with:external_source',
                Rule::unique(Fixture::class)->where('external_source', $this->external_source)->ignore($fixture->id),
            ],
        ], [
            'away_team_id.different' => __('A team cannot play against itself.'),
            'external_id.unique' => __('This external ID is already used by another fixture from the same source.'),
        ]);

        $fixture->fill([
            'season_id' => (int) $validated['season_id'],
            'home_team_id' => (int) $validated['home_team_id'],
            'away_team_id' => (int) $validated['away_team_id'],
            'kickoff_at' => CarbonImmutable::createFromFormat('Y-m-d\TH:i', $validated['kickoff_at'], config('app.display_timezone'))->utc(),
            'status' => FixtureStatus::from($validated['fixture_status']),
            'score_home' => $validated['score_home'] === '' ? null : (int) $validated['score_home'],
            'score_away' => $validated['score_away'] === '' ? null : (int) $validated['score_away'],
            'minute' => $validated['minute'] === '' ? null : (int) $validated['minute'],
            'round' => $validated['round'] ?: null,
            'venue' => $validated['venue'] ?: null,
            'external_source' => $validated['external_source'] ?: null,
            'external_id' => $validated['external_id'] ?: null,
        ])->save();

        $this->resetForm();

        Flux::modal('fixture-form')->close();
        Flux::toast(variant: 'success', text: __('Fixture saved.'));
    }

    public function delete(int $fixtureId): void
    {
        $fixture = Fixture::query()->findOrFail($fixtureId);

        $this->authorize('delete', $fixture);

        $fixture->delete();

        Flux::toast(variant: 'success', text: __('Fixture deleted.'));
    }

    /**
     * @return LengthAwarePaginator<int, Fixture>
     */
    #[Computed]
    public function fixtures(): LengthAwarePaginator
    {
        return Fixture::query()
            ->with(['season.league', 'homeTeam', 'awayTeam'])
            ->when($this->season !== '', fn (Builder $query) => $query->where('season_id', (int) $this->season))
            ->when(FixtureStatus::tryFrom($this->status), fn (Builder $query, FixtureStatus $status) => $query->where('status', $status))
            ->orderByDesc('kickoff_at')
            ->paginate(30);
    }

    /**
     * @return Collection<int, Season>
     */
    #[Computed]
    public function seasons(): Collection
    {
        return Season::query()->with('league')->orderByDesc('name')->get();
    }

    /**
     * The chosen season's roster, or active teams of its league's sport while it has no roster.
     *
     * @return Collection<int, Team>
     */
    #[Computed]
    public function teamOptions(): Collection
    {
        $season = $this->season_id === '' ? null : Season::query()->with('league')->find((int) $this->season_id);

        if ($season === null) {
            return Team::query()->active()->orderBy('name')->get(['id', 'name']);
        }

        $roster = $season->teams()->orderBy('name')->get(['teams.id', 'teams.name']);

        return $roster->isNotEmpty()
            ? $roster
            : Team::query()->active()->where('sport_id', $season->league->sport_id)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array<int, FixtureStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return FixtureStatus::cases();
    }

    private function resetForm(): void
    {
        $this->reset(
            'editingId', 'season_id', 'home_team_id', 'away_team_id', 'kickoff_at', 'fixture_status',
            'score_home', 'score_away', 'minute', 'round', 'venue', 'external_source', 'external_id',
        );
        $this->resetValidation();
    }
}
