<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Fixtures') }}</flux:heading>
            <flux:subheading>{{ __('Match schedule and results') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New fixture') }}</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:select wire:model.live="season" :aria-label="__('Season')" class="sm:max-w-sm">
            <flux:select.option value="">{{ __('All seasons') }}</flux:select.option>
            @foreach ($this->seasons as $seasonOption)
                <flux:select.option :value="$seasonOption->id">{{ $seasonOption->league->name }} – {{ $seasonOption->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="status" :aria-label="__('Status')" class="sm:max-w-56">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($this->statuses as $statusOption)
                <flux:select.option :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->fixtures">
        <flux:table.columns>
            <flux:table.column>{{ __('Match') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Kickoff') }}</flux:table.column>
            <flux:table.column class="hidden md:table-cell">{{ __('Season') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->fixtures as $fixture)
                <flux:table.row :key="$fixture->id">
                    <flux:table.cell class="font-medium">
                        {{ $fixture->homeTeam->name }}
                        @if ($fixture->status->hasScore())
                            <span class="tabular-nums">{{ $fixture->score_home }} – {{ $fixture->score_away }}</span>
                        @else
                            –
                        @endif
                        {{ $fixture->awayTeam->name }}
                    </flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell"><x-local-datetime :value="$fixture->kickoff_at" /></flux:table.cell>
                    <flux:table.cell class="hidden md:table-cell">{{ $fixture->season->league->name }} {{ $fixture->season->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match ($fixture->status) {
                            App\Enums\FixtureStatus::Live => 'red',
                            App\Enums\FixtureStatus::Finished => 'green',
                            App\Enums\FixtureStatus::Postponed, App\Enums\FixtureStatus::Cancelled => 'amber',
                            default => 'zinc',
                        }">
                            {{ $fixture->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $fixture->id }})">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $fixture->id }})"
                                    wire:confirm="{{ __('Delete this fixture?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">{{ __('No fixtures found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="fixture-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit fixture') : __('New fixture') }}</flux:heading>

            <flux:select wire:model.live="season_id" :label="__('Season')" required>
                <flux:select.option value="">{{ __('Choose a season') }}</flux:select.option>
                @foreach ($this->seasons as $seasonOption)
                    <flux:select.option :value="$seasonOption->id">{{ $seasonOption->league->name }} – {{ $seasonOption->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:select wire:model="home_team_id" :label="__('Home team')" required>
                    <flux:select.option value="">{{ __('Choose a team') }}</flux:select.option>
                    @foreach ($this->teamOptions as $teamOption)
                        <flux:select.option :value="$teamOption->id">{{ $teamOption->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="away_team_id" :label="__('Away team')" required>
                    <flux:select.option value="">{{ __('Choose a team') }}</flux:select.option>
                    @foreach ($this->teamOptions as $teamOption)
                        <flux:select.option :value="$teamOption->id">{{ $teamOption->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="kickoff_at" type="datetime-local" :label="__('Kickoff')" :description="__('Cyprus time.')" required />

                <flux:select wire:model.live="fixture_status" :label="__('Status')">
                    @foreach ($this->statuses as $statusOption)
                        <flux:select.option :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <flux:input wire:model="score_home" type="number" min="0" max="99" :label="__('Home score')" />
                <flux:input wire:model="score_away" type="number" min="0" max="99" :label="__('Away score')" />
                <flux:input wire:model="minute" type="number" min="0" max="150" :label="__('Minute')" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="round" :label="__('Round')" maxlength="30" />
                <flux:input wire:model="venue" :label="__('Venue')" maxlength="150" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="external_source" :label="__('External source')" maxlength="30" />
                <flux:input wire:model="external_id" :label="__('External ID')" maxlength="60" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
