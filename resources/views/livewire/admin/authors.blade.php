<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Authors') }}</flux:heading>
            <flux:subheading>{{ __('Public bylines for articles') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New author') }}</flux:button>
    </div>

    <flux:table :paginate="$this->authors">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Account') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->authors as $author)
                <flux:table.row :key="$author->id">
                    <flux:table.cell>
                        <div class="flex items-center gap-3">
                            <flux:avatar size="sm" :src="$author->photo?->url()" :name="$author->name" />
                            <span class="font-medium">{{ $author->name }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">
                        {{ $author->user?->email ?? __('Guest') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$author->is_active ? 'green' : 'zinc'">
                            {{ $author->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="edit({{ $author->id }})">{{ __('Edit') }}</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $author->id }})"
                                    wire:confirm="{{ __('Delete this author?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center">{{ __('No authors yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="author-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit author') : __('New author') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required maxlength="100" />
            <flux:input wire:model="slug" :label="__('Slug')" :description="__('Leave empty to generate it from the name.')" maxlength="120" />

            <flux:select wire:model="user_id" :label="__('Account')">
                <flux:select.option value="">{{ __('No account (guest byline)') }}</flux:select.option>
                @foreach ($this->userOptions as $user)
                    <flux:select.option :value="$user->id">{{ $user->name }} ({{ $user->email }})</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="bio" :label="__('Bio')" rows="3" maxlength="2000" />
            <flux:input wire:model="email" :label="__('Public email')" type="email" maxlength="150" />

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:input wire:model="twitter" label="X / Twitter" type="url" placeholder="https://" />
                <flux:input wire:model="facebook" label="Facebook" type="url" placeholder="https://" />
                <flux:input wire:model="instagram" label="Instagram" type="url" placeholder="https://" />
            </div>

            <div class="space-y-3">
                <flux:input wire:model="photo" :label="__('Photo')" type="file" accept="image/jpeg,image/png,image/webp" />

                <div wire:loading wire:target="photo">
                    <flux:text>{{ __('Uploading…') }}</flux:text>
                </div>

                @if ($editingId && ! $photo)
                    <flux:checkbox wire:model="removePhoto" :label="__('Remove photo')" />
                @endif
            </div>

            <flux:switch wire:model="is_active" :label="__('Active')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="photo">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
