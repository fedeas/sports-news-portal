<flux:table.row :key="$category->id">
    <flux:table.cell @class(['font-medium' => ! $isChild, 'ps-8' => $isChild])>
        @if ($isChild)
            <span aria-hidden="true" class="text-zinc-400">↳</span>
        @endif
        {{ $category->name }}
    </flux:table.cell>
    <flux:table.cell class="hidden sm:table-cell">{{ $category->sport?->name ?? __('All sports') }}</flux:table.cell>
    <flux:table.cell class="hidden md:table-cell">{{ $category->slug }}</flux:table.cell>
    <flux:table.cell>
        <flux:badge size="sm" :color="$category->is_active ? 'green' : 'zinc'">
            {{ $category->is_active ? __('Active') : __('Inactive') }}
        </flux:badge>
    </flux:table.cell>
    <flux:table.cell align="end">
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

            <flux:menu>
                <flux:menu.item icon="pencil-square" wire:click="edit({{ $category->id }})">{{ __('Edit') }}</flux:menu.item>
                <flux:menu.item
                    icon="trash"
                    variant="danger"
                    wire:click="delete({{ $category->id }})"
                    wire:confirm="{{ __('Delete this category?') }}"
                >
                    {{ __('Delete') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:table.cell>
</flux:table.row>
