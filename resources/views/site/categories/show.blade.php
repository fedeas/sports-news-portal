<x-layouts::site
    :title="$category->meta_title ?? $category->name"
    :description="$category->meta_description ?? $category->description ?? __('Latest :category news', ['category' => $category->name])"
    :canonical="$canonical"
>
    <div class="site-container page-with-rail py-6 sm:py-10">
        <div class="min-w-0">
        @if ($category->parent)
            <nav aria-label="{{ __('Breadcrumb') }}" class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ $category->parent->url() }}" class="hover:underline">{{ $category->parent->name }}</a></li>
                </ol>
            </nav>
        @endif

        {{-- A subsection without a pictogram of its own (a country) shows its section's. --}}
        <h1 class="font-ui flex items-center gap-2.5 text-2xl leading-tight font-bold sm:text-[1.75rem]">
            <x-section-icon :icon="$category->icon ?? $section->icon" class="size-7 sm:size-8" />
            {{ $category->name }}
        </h1>

        @if ($category->description)
            <p class="mt-2 max-w-2xl text-zinc-600 dark:text-zinc-400">{{ $category->description }}</p>
        @endif

        @if ($subcategories->isNotEmpty())
            <nav aria-label="{{ __('Subcategories') }}" class="font-ui mt-5">
                <ul class="flex flex-wrap gap-2">
                    @foreach ([$section, ...$subcategories] as $choice)
                        @php($isCurrent = $choice->is($category))
                        <li>
                            <a
                                href="{{ $choice->url() }}"
                                @if ($isCurrent) aria-current="page" @endif
                                @class([
                                    'inline-block border px-3 py-1 text-sm font-semibold',
                                    'border-ink bg-ink text-white dark:border-white dark:bg-white dark:text-ink' => $isCurrent,
                                    'border-zinc-300 hover:border-ink dark:border-zinc-700 dark:hover:border-white' => ! $isCurrent,
                                ])
                            >
                                {{ $loop->first ? __('All of :section', ['section' => $choice->name]) : $choice->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @if ($articles->isEmpty())
            <p class="mt-10 text-zinc-600 dark:text-zinc-400">{{ __('No articles in this category yet.') }}</p>
        @else
            {{-- The first page opens like the front page: one main story and four beside it. Later pages are the feed. --}}
            @if ($articles->onFirstPage())
                <x-lead-stories
                    :lead="$articles->first()"
                    :beside="$articles->slice(1, 4)"
                    :label="__('Latest :category news', ['category' => $category->name])"
                    class="mt-8"
                />

                @if ($articles->count() > 5)
                    <section aria-labelledby="more-stories-heading" class="mt-10">
                        <div class="section-heading">
                            <h2 id="more-stories-heading" class="text-lg font-bold">{{ __('More stories') }}</h2>
                        </div>

                        <x-story-feed :articles="$articles->slice(5)" :columns="1" />
                    </section>
                @endif
            @else
                <x-story-feed :articles="$articles" :columns="1" class="mt-6" />
            @endif

            <div class="mt-10">
                {{ $articles->links() }}
            </div>
        @endif
        </div>

        <div class="page-with-rail__rail">
            @include('partials.site-rail')
        </div>
    </div>
</x-layouts::site>
