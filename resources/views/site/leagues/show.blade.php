<x-layouts::site
    :title="$league->name"
    :description="__(':league: news, fixtures, results and standings.', ['league' => $league->name])"
    :canonical="$articles->currentPage() > 1 ? $league->url().'?page='.$articles->currentPage() : $league->url()"
    :image="$league->logo?->largestUrl()"
>
    <x-slot:head>
        <script type="application/ld+json">@json($structuredData)</script>
    </x-slot:head>

    <div class="site-container py-6 sm:py-10">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-500 dark:text-zinc-400">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('leagues.index') }}" class="hover:underline">{{ __('Leagues') }}</a></li>
            </ol>
        </nav>

        <header class="mt-6 flex items-center gap-5">
            <x-logo-badge :media="$league->logo" :name="$league->name" size="lg" />

            <div class="min-w-0">
                <h1 class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">{{ $league->name }}</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ collect([$league->sport->name, $league->countryName()])->filter()->join(' · ') }}
                </p>
            </div>
        </header>

        @if ($season && $standings->isNotEmpty())
            <div class="mt-10">
                @include('site.partials.standings-table', ['season' => $season, 'standings' => $standings])
            </div>
        @endif

        @if ($season)
            <div class="mt-10 grid gap-10 md:grid-cols-2">
                @include('site.partials.fixture-list', [
                    'fixtures' => $upcomingFixtures,
                    'heading' => __('Upcoming fixtures'),
                    'headingId' => 'upcoming-heading',
                    'emptyText' => __('No upcoming fixtures.'),
                ])
                @include('site.partials.fixture-list', [
                    'fixtures' => $recentResults,
                    'heading' => __('Latest results'),
                    'headingId' => 'results-heading',
                    'emptyText' => __('No results yet.'),
                ])
            </div>
        @endif

        @include('site.partials.subject-articles', ['articles' => $articles, 'subjectName' => $league->name])

        @if ($league->seasons->isNotEmpty())
            <section aria-labelledby="seasons-heading" class="mt-10">
                <div class="section-heading">
                    <h2 id="seasons-heading" class="text-xl font-extrabold">{{ __('Seasons') }}</h2>
                </div>

                <ul class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-800">
                    @foreach ($league->seasons as $season)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                            <span class="font-semibold">
                                {{ $season->name }}
                                @if ($season->is_current)
                                    <span class="ms-2 rounded bg-brand px-2 py-0.5 text-xs font-bold text-white">{{ __('Current season') }}</span>
                                @endif
                            </span>

                            @if ($season->starts_on && $season->ends_on)
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $season->starts_on->locale(app()->getLocale())->translatedFormat('j F Y') }}
                                    –
                                    {{ $season->ends_on->locale(app()->getLocale())->translatedFormat('j F Y') }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts::site>
