<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Teams') }}</flux:heading>
            <flux:subheading>{{ __('Clubs and national teams') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New team') }}</flux:button>
    </div>

    <flux:input
        wire:model.live.debounce.300ms="search"
        type="search"
        icon="magnifying-glass"
        :placeholder="__('Search teams')"
        :aria-label="__('Search teams')"
        class="max-w-sm"
    />

    <flux:table :paginate="$this->teams">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Sport') }}</flux:table.column>
            <flux:table.column class="hidden md:table-cell">{{ __('Country') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->teams as $team)
                <flux:table.row :key="$team->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <x-logo-badge :media="$team->logo" :name="$team->name" size="sm" />
                            <span class="font-medium">{{ $team->name }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ $team->sport->name }}</flux:table.cell>
                    <flux:table.cell class="hidden md:table-cell">{{ $team->country }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$team->is_active ? 'green' : 'zinc'">
                            {{ $team->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $team->id }})">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $team->id }})"
                                    wire:confirm="{{ __('Delete this team?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">{{ __('No teams found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="team-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit team') : __('New team') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required maxlength="100" />
            <flux:input wire:model="slug" :label="__('Slug')" :description="__('Leave empty to generate it from the name.')" maxlength="120" />

            <flux:select wire:model="sport_id" :label="__('Sport')" required>
                <flux:select.option value="">{{ __('Choose a sport') }}</flux:select.option>
                @foreach ($this->sports as $sport)
                    <flux:select.option :value="$sport->id">{{ $sport->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="short_name" :label="__('Short name')" maxlength="20" />
                <flux:input wire:model="country" :label="__('Country code')" placeholder="CY" maxlength="2" />
                <flux:input wire:model="founded_year" :label="__('Founded')" type="number" min="1850" :max="now()->year" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="external_source" :label="__('External source')" maxlength="30" />
                <flux:input wire:model="external_id" :label="__('External ID')" maxlength="60" />
            </div>

            <div class="space-y-3">
                <flux:input wire:model="logo" :label="__('Logo')" type="file" accept="image/png,image/webp,image/jpeg" />

                <div wire:loading wire:target="logo">
                    <flux:text>{{ __('Uploading…') }}</flux:text>
                </div>

                @if ($editingId && ! $logo)
                    <flux:checkbox wire:model="removeLogo" :label="__('Remove logo')" />
                @endif
            </div>

            <flux:switch wire:model="is_active" :label="__('Active')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
