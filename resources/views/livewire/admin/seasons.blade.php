<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Seasons') }}</flux:heading>
            <flux:subheading>{{ __('Seasons of each league') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New season') }}</flux:button>
    </div>

    <flux:select wire:model.live="league" :aria-label="__('League')" class="max-w-sm">
        <flux:select.option value="">{{ __('All leagues') }}</flux:select.option>
        @foreach ($this->leagues as $leagueOption)
            <flux:select.option :value="$leagueOption->id">{{ $leagueOption->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:table :paginate="$this->seasons">
        <flux:table.columns>
            <flux:table.column>{{ __('Season') }}</flux:table.column>
            <flux:table.column>{{ __('League') }}</flux:table.column>
            <flux:table.column class="hidden md:table-cell">{{ __('Dates') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->seasons as $season)
                <flux:table.row :key="$season->id">
                    <flux:table.cell class="font-medium">
                        {{ $season->name }}
                        @if ($season->is_current)
                            <flux:badge size="sm" color="green" class="ms-2">{{ __('Current') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $season->league->name }}</flux:table.cell>
                    <flux:table.cell class="hidden md:table-cell">
                        {{ $season->starts_on?->format('d/m/Y') }} – {{ $season->ends_on?->format('d/m/Y') }}
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $season->id }})">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item icon="shield-check" wire:click="editRoster({{ $season->id }})">{{ __('Teams') }}</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $season->id }})"
                                    wire:confirm="{{ __('Delete this season?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center">{{ __('No seasons yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="season-form" class="w-full max-w-md">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit season') : __('New season') }}</flux:heading>

            <flux:select wire:model="league_id" :label="__('League')" required>
                <flux:select.option value="">{{ __('Choose a league') }}</flux:select.option>
                @foreach ($this->leagues as $leagueOption)
                    <flux:select.option :value="$leagueOption->id">{{ $leagueOption->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" :label="__('Name')" placeholder="2025/2026" required maxlength="20" />

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="starts_on" :label="__('Starts on')" type="date" />
                <flux:input wire:model="ends_on" :label="__('Ends on')" type="date" />
            </div>

            <flux:input wire:model="external_id" :label="__('External ID')" maxlength="60" />
            <flux:switch wire:model="is_current" :label="__('Current season')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="season-roster" class="w-full max-w-md">
        <form wire:submit="saveRoster" class="space-y-6">
            <flux:heading size="lg">{{ __('Teams in this season') }}</flux:heading>

            @if ($this->rosterOptions->isEmpty())
                <flux:text>{{ __('No teams yet.') }}</flux:text>
            @else
                <flux:checkbox.group wire:model="roster_team_ids" class="max-h-80 overflow-y-auto">
                    @foreach ($this->rosterOptions as $teamOption)
                        <flux:checkbox wire:key="roster-team-{{ $teamOption->id }}" :value="(string) $teamOption->id" :label="$teamOption->name" />
                    @endforeach
                </flux:checkbox.group>
            @endif

            <flux:error name="roster_team_ids" />
            <flux:error name="roster_team_ids.*" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
