<section class="w-full space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Articles') }}</flux:heading>
            <flux:subheading>{{ __('News, reports and features') }}</flux:subheading>
        </div>

        @can('create', App\Models\Article::class)
            <flux:button variant="primary" icon="plus" :href="route('admin.articles.create')" wire:navigate>{{ __('New article') }}</flux:button>
        @endcan
    </div>

    @cannot('create', App\Models\Article::class)
        <flux:callout icon="information-circle" :heading="__('You need an author profile to write articles. Ask an editor to create one for your account.')" />
    @endcannot

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            icon="magnifying-glass"
            :placeholder="__('Search articles')"
            :aria-label="__('Search articles')"
            class="sm:max-w-sm"
        />

        <flux:select wire:model.live="status" :aria-label="__('Status')" class="sm:max-w-56">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($this->statuses as $articleStatus)
                <flux:select.option :value="$articleStatus->value">{{ $articleStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->articles">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column class="hidden md:table-cell">{{ __('Category') }}</flux:table.column>
            <flux:table.column class="hidden lg:table-cell">{{ __('Author') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">{{ __('Publish at') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->articles as $article)
                <flux:table.row :key="$article->id">
                    <flux:table.cell class="max-w-48 truncate font-medium sm:max-w-md">
                        @can('update', $article)
                            <flux:link :href="route('admin.articles.edit', $article)" wire:navigate>{{ $article->title }}</flux:link>
                        @else
                            {{ $article->title }}
                        @endcan
                    </flux:table.cell>
                    <flux:table.cell class="hidden md:table-cell">{{ $article->category->name }}</flux:table.cell>
                    <flux:table.cell class="hidden lg:table-cell">{{ $article->author->name }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match ($article->status) {
                            App\Enums\ArticleStatus::Published => 'green',
                            App\Enums\ArticleStatus::Scheduled => 'amber',
                            App\Enums\ArticleStatus::Archived => 'red',
                            default => 'zinc',
                        }">
                            {{ $article->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">
                        <x-local-datetime :value="$article->published_at" />
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="eye" :href="$article->previewUrl()" target="_blank">{{ __('Preview') }}</flux:menu.item>
                                @can('update', $article)
                                    <flux:menu.item icon="pencil-square" :href="route('admin.articles.edit', $article)" wire:navigate>{{ __('Edit') }}</flux:menu.item>
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="delete({{ $article->id }})"
                                        wire:confirm="{{ __('Delete this article?') }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                @endcan
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">{{ __('No articles found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
