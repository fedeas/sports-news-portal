<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Categories') }}</flux:heading>
            <flux:subheading>{{ __('Site sections and their subcategories') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New category') }}</flux:button>
    </div>

    <flux:table :paginate="$this->categories">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Sport') }}</flux:table.column>
            <flux:table.column class="hidden md:table-cell">{{ __('Slug') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
                @include('livewire.admin.partials.category-row', ['category' => $category, 'isChild' => false])

                @foreach ($category->children as $child)
                    @include('livewire.admin.partials.category-row', ['category' => $child, 'isChild' => true])
                @endforeach
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">{{ __('No categories yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="category-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit category') : __('New category') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required maxlength="100" />
            <flux:input wire:model="slug" :label="__('Slug')" :description="__('Leave empty to generate it from the name.')" maxlength="120" />

            <flux:select wire:model="icon" :label="__('Icon')" :description="__('Shown beside the name in the menu and on the section page.')">
                <flux:select.option value="">{{ __('No icon') }}</flux:select.option>
                @foreach (\App\Enums\SectionIcon::cases() as $sectionIcon)
                    <flux:select.option :value="$sectionIcon->value">{{ $sectionIcon->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:select wire:model="sport_id" :label="__('Sport')">
                    <flux:select.option value="">{{ __('All sports') }}</flux:select.option>
                    @foreach ($this->sports as $sport)
                        <flux:select.option :value="$sport->id">{{ $sport->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="parent_id" :label="__('Parent category')">
                    <flux:select.option value="">{{ __('None (top level)') }}</flux:select.option>
                    @foreach ($this->parentOptions as $parent)
                        <flux:select.option :value="$parent->id">{{ $parent->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
            <flux:input wire:model="meta_title" :label="__('Meta title')" maxlength="70" />
            <flux:textarea wire:model="meta_description" :label="__('Meta description')" rows="2" maxlength="160" />

            <div class="grid items-end gap-6 sm:grid-cols-2">
                <flux:input wire:model="sort_order" :label="__('Sort order')" type="number" min="0" required />
                <flux:switch wire:model="is_active" :label="__('Active')" />
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
