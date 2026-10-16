<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Tags') }}</flux:heading>
            <flux:subheading>{{ __('Article tags') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New tag') }}</flux:button>
    </div>

    <flux:input
        wire:model.live.debounce.300ms="search"
        type="search"
        icon="magnifying-glass"
        :placeholder="__('Search tags')"
        :aria-label="__('Search tags')"
        class="max-w-sm"
    />

    <flux:table :paginate="$this->tags">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Slug') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->tags as $tag)
                <flux:table.row :key="$tag->id">
                    <flux:table.cell class="font-medium">{{ $tag->name }}</flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ $tag->slug }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $tag->id }})">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $tag->id }})"
                                    wire:confirm="{{ __('Delete this tag?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" class="text-center">{{ __('No tags found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="tag-form" class="w-full max-w-md">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit tag') : __('New tag') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required maxlength="60" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
