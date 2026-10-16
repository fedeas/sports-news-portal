<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
            <flux:subheading>{{ __('Manage staff accounts and their roles') }}</flux:subheading>
        </div>

        <flux:modal.trigger name="create-user">
            <flux:button variant="primary" icon="plus">{{ __('New user') }}</flux:button>
        </flux:modal.trigger>
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell class="font-medium">{{ $user->name }}</flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @can('update', $user)
                            <flux:select
                                size="sm"
                                class="max-w-40"
                                :aria-label="__('Role')"
                                wire:change="changeRole({{ $user->id }}, $event.target.value)"
                            >
                                @foreach ($this->roles as $role)
                                    <flux:select.option :value="$role->value" :selected="$user->role === $role">
                                        {{ $role->label() }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:badge size="sm">{{ $user->role->label() }}</flux:badge>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="create-user" class="w-full max-w-md">
        <form wire:submit="createUser" class="space-y-6">
            <flux:heading size="lg">{{ __('New user') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="off" />
            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="off" />
            <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password" viewable />
            <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />

            <flux:select wire:model="role" :label="__('Role')">
                @foreach ($this->roles as $role)
                    <flux:select.option :value="$role->value">{{ $role->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
