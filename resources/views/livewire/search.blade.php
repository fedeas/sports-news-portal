<div class="mt-6 space-y-8">
    {{-- A plain GET form, so search also works before or without JavaScript. --}}
    <form role="search" action="{{ route('search') }}" method="get">
        <label for="search-query" class="sr-only">{{ __('Search') }}</label>
        <input
            id="search-query"
            name="q"
            type="search"
            wire:model.live.debounce.400ms="query"
            value="{{ $query }}"
            maxlength="100"
            autocomplete="off"
            placeholder="{{ __('Search articles and teams') }}"
            class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-base focus:outline-2 focus:outline-offset-2 focus:outline-brand dark:border-zinc-700 dark:bg-zinc-900"
        />
    </form>

    @php($results = $this->results)
    @php($term = trim($query))

    @if ($results === null)
        @if ($term !== '')
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Type at least :count characters.', ['count' => App\Livewire\Search::MIN_LENGTH]) }}</p>
        @endif
    @elseif ($results['rateLimited'])
        <p role="alert" class="border-y-2 border-ink px-1 py-3 text-sm font-semibold dark:border-white">
            {{ __('Too many searches. Please wait a minute and try again.') }}
        </p>
    @elseif ($results['articles']->isEmpty() && $results['teams']->isEmpty())
        <p role="status" class="text-zinc-600 dark:text-zinc-400">{{ __('No results for “:query”.', ['query' => $term]) }}</p>
    @else
        <p role="status" class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count article found|:count articles found', $results['articles']->total()) }}
        </p>

        @if ($results['teams']->isNotEmpty())
            <section aria-labelledby="search-teams-heading">
                <h2 id="search-teams-heading" class="section-heading text-lg font-extrabold">{{ __('Teams') }}</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($results['teams'] as $team)
                        <li wire:key="search-team-{{ $team->id }}">
                            <a href="{{ $team->url() }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 font-semibold hover:border-brand dark:border-zinc-800">
                                <x-logo-badge :media="$team->logo" :name="$team->name" size="sm" />
                                {{ $team->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($results['articles']->isNotEmpty())
            <section aria-labelledby="search-articles-heading">
                <h2 id="search-articles-heading" class="section-heading text-lg font-extrabold">{{ __('Articles') }}</h2>
                <div class="mt-6 grid gap-x-6 gap-y-10 sm:grid-cols-2">
                    @foreach ($results['articles'] as $article)
                        <x-article-card wire:key="search-article-{{ $article->id }}" :article="$article" :heading-level="3" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $results['articles']->links() }}
                </div>
            </section>
        @endif
    @endif
</div>
