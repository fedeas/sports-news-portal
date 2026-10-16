<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Sports') }}</flux:heading>
            <flux:subheading>{{ __('Sports covered by the site') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New sport') }}</flux:button>
    </div>

    <flux:table :paginate="$this->sports">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Slug') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Categories') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->sports as $sport)
                <flux:table.row :key="$sport->id">
                    <flux:table.cell class="font-medium">{{ $sport->name }}</flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ $sport->slug }}</flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ $sport->categories_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$sport->is_active ? 'green' : 'zinc'">
                            {{ $sport->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $sport->id }})">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $sport->id }})"
                                    wire:confirm="{{ __('Delete this sport?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">{{ __('No sports yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="sport-form" class="w-full max-w-md">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit sport') : __('New sport') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required maxlength="50" />
            <flux:input wire:model="slug" :label="__('Slug')" :description="__('Leave empty to generate it from the name.')" maxlength="50" />
            <flux:input wire:model="icon" :label="__('Icon')" maxlength="50" />
            <flux:input wire:model="sort_order" :label="__('Sort order')" type="number" min="0" required />
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
