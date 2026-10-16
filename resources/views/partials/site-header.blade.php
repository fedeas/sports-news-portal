{{-- Masthead: the name at the top left, the sections straight underneath. --}}
<header class="border-b-2 border-ink bg-white dark:border-white dark:bg-zinc-950">
    <div class="site-container relative flex items-center justify-between gap-4 py-3">
        <a
            href="{{ route('home') }}"
            class="flex items-center gap-2.5 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ink dark:focus-visible:outline-white"
        >
            <span class="flex size-9 shrink-0 items-center justify-center rounded-md bg-ink text-white dark:bg-white dark:text-ink">
                <x-app-logo-icon class="size-6" aria-hidden="true" />
            </span>
            <span class="text-2xl leading-none font-extrabold tracking-tight sm:text-[1.75rem]">{{ config('app.name') }}</span>
        </a>

        {{--
            Search. Without JavaScript the icon opens the search page; with it, a bar opens across the
            masthead towards the left and suggests stories and clubs as the reader types.
        --}}
        <div
            data-search
            data-search-endpoint="{{ route('search.suggestions') }}"
            data-search-min-length="{{ \App\Livewire\Search::MIN_LENGTH }}"
            data-label-articles="{{ __('Articles') }}"
            data-label-teams="{{ __('Teams') }}"
            data-label-empty="{{ __('No suggestions for “:query”.') }}"
            data-label-all="{{ __('See all results for “:query”') }}"
            data-label-count="{{ __(':count suggestions') }}"
        >
            <a
                href="{{ route('search') }}"
                data-search-open
                aria-expanded="false"
                aria-controls="site-search-panel"
                class="inline-flex size-10 items-center justify-center hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-ink dark:hover:bg-zinc-900 dark:focus-visible:outline-white"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span class="sr-only">{{ __('Search') }}</span>
            </a>

            <div id="site-search-panel" class="site-search" data-search-panel hidden>
                <form role="search" action="{{ route('search') }}" method="get" class="flex h-full items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5 shrink-0 text-zinc-500" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>

                    <label for="site-search-input" class="sr-only">{{ __('Search') }}</label>
                    <input
                        id="site-search-input"
                        name="q"
                        type="search"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="site-search-suggestions"
                        maxlength="100"
                        autocomplete="off"
                        enterkeyhint="search"
                        placeholder="{{ __('Search articles and teams') }}"
                        class="font-ui h-10 min-w-0 flex-1 border-b-2 border-ink bg-transparent px-1 text-base outline-none placeholder:text-zinc-400 dark:border-white"
                    />

                    <button
                        type="button"
                        data-search-close
                        class="inline-flex size-10 shrink-0 items-center justify-center hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-ink dark:hover:bg-zinc-900 dark:focus-visible:outline-white"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span class="sr-only">{{ __('Close search') }}</span>
                    </button>
                </form>

                <div id="site-search-suggestions" role="listbox" aria-label="{{ __('Suggestions') }}" class="site-search__suggestions" data-search-suggestions hidden></div>
                <p class="sr-only" role="status" data-search-status></p>
            </div>
        </div>
    </div>

    @if ($navigation !== [])
        <nav aria-label="{{ __('Sections') }}" class="border-t border-zinc-300 dark:border-zinc-700">
            <div class="site-container font-ui flex items-stretch">
                {{-- Everything the site covers, in a drawer. A <details> element, so it works without JavaScript. --}}
                <details class="sections-menu" data-sections-menu>
                    <summary class="flex h-full cursor-pointer list-none items-center gap-2 border-e border-zinc-200 pe-4 text-[15px] font-semibold hover:underline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ink dark:border-zinc-800 dark:focus-visible:outline-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        <span class="sr-only whitespace-nowrap sm:not-sr-only">{{ __('All sections') }}</span>
                    </summary>

                    <div class="sections-menu__backdrop" data-sections-menu-backdrop></div>

                    <div class="sections-menu__drawer">
                        <p class="font-ui border-b-2 border-ink px-5 py-4 text-lg font-extrabold dark:border-white">{{ __('All sections') }}</p>

                        <ul class="font-ui px-2 py-2 text-[15px]">
                            <li class="border-b border-zinc-200 dark:border-zinc-800">
                                <a href="{{ route('news.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900">
                                    <x-section-icon :icon="\App\Enums\SectionIcon::News" class="size-5" />
                                    {{ __('News') }}
                                </a>
                            </li>

                            @foreach ($navigation as $section)
                                <li class="border-b border-zinc-200 last:border-b-0 dark:border-zinc-800">
                                    @if ($section['children'] === [] && $section['leagues'] === [])
                                        <a href="{{ $section['url'] }}" class="flex items-center gap-2.5 px-3 py-2.5 font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-900">
                                            <x-section-icon :icon="$section['icon']" class="size-5" />
                                            {{ $section['name'] }}
                                        </a>
                                    @else
                                        <details class="sections-menu__row">
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2.5 font-semibold hover:bg-zinc-100 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ink dark:hover:bg-zinc-900 dark:focus-visible:outline-white">
                                                <span class="flex items-center gap-2.5">
                                                    <x-section-icon :icon="$section['icon']" class="size-5" />
                                                    {{ $section['name'] }}
                                                </span>

                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="sections-menu__chevron size-4 shrink-0" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </summary>

                                            <ul class="pb-2 ps-3 text-sm">
                                                <li>
                                                    <a href="{{ $section['url'] }}" class="block py-1.5 ps-3 font-semibold hover:underline">
                                                        {{ __('All of :section', ['section' => $section['name']]) }}
                                                    </a>
                                                </li>

                                                @foreach ($section['children'] as $child)
                                                    <li>
                                                        <a href="{{ $child['url'] }}" class="flex items-center gap-2 py-1.5 ps-3 hover:underline"><x-section-icon :icon="$child['icon']" class="size-4" />{{ $child['name'] }}</a>
                                                    </li>
                                                @endforeach

                                                @foreach ($section['leagues'] as $league)
                                                    <li>
                                                        @if ($league['teams'] === [])
                                                            <a href="{{ $league['url'] }}" class="block py-1.5 ps-3 hover:underline">{{ $league['name'] }}</a>
                                                        @else
                                                            <details class="sections-menu__row">
                                                                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 py-1.5 ps-3 hover:underline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ink dark:focus-visible:outline-white">
                                                                    {{ $league['name'] }}

                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="sections-menu__chevron size-3.5 shrink-0" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                                    </svg>
                                                                </summary>

                                                                <ul class="pb-1 ps-3 text-zinc-600 dark:text-zinc-400">
                                                                    <li>
                                                                        <a href="{{ $league['url'] }}" class="block py-1.5 ps-3 font-semibold hover:underline">
                                                                            {{ __('Table and fixtures') }}
                                                                        </a>
                                                                    </li>

                                                                    @foreach ($league['teams'] as $team)
                                                                        <li>
                                                                            <a href="{{ $team['url'] }}" class="block py-1.5 ps-3 hover:text-ink hover:underline dark:hover:text-white">{{ $team['name'] }}</a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </details>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </details>

                <ul class="flex min-w-0 overflow-x-auto whitespace-nowrap [scrollbar-width:none]">
                    @php($isNews = request()->routeIs('news.index'))
                    <li class="border-e border-zinc-200 dark:border-zinc-800">
                        <a
                            href="{{ route('news.index') }}"
                            @if ($isNews) aria-current="page" @endif
                            @class([
                                'inline-flex items-center gap-1.5 px-3.5 py-2.5 text-[15px] font-medium focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ink dark:focus-visible:outline-white',
                                'bg-ink text-white dark:bg-white dark:text-ink' => $isNews,
                                'hover:bg-zinc-100 dark:hover:bg-zinc-900' => ! $isNews,
                            ])
                        >
                            <x-section-icon :icon="\App\Enums\SectionIcon::News" class="size-[18px]" />
                            {{ __('News') }}
                        </a>
                    </li>

                    @foreach ($navigation as $section)
                        @php($isCurrent = request()->segment(1) === $section['slug'])
                        <li class="border-e border-zinc-200 dark:border-zinc-800">
                            <a
                                href="{{ $section['url'] }}"
                                @if ($isCurrent) aria-current="page" @endif
                                @class([
                                    'inline-flex items-center gap-1.5 px-3.5 py-2.5 text-[15px] font-medium focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ink dark:focus-visible:outline-white',
                                    'bg-ink text-white dark:bg-white dark:text-ink' => $isCurrent,
                                    'hover:bg-zinc-100 dark:hover:bg-zinc-900' => ! $isCurrent,
                                ])
                            >
                                <x-section-icon :icon="$section['icon']" class="size-[18px]" />
                                {{ $section['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>
    @endif
</header>
