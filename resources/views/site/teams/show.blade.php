<x-layouts::site
    :title="$team->name"
    :description="__(':team: news, fixtures and results.', ['team' => $team->name])"
    :canonical="$articles->currentPage() > 1 ? $team->url().'?page='.$articles->currentPage() : $team->url()"
    :image="$team->logo?->largestUrl()"
>
    <x-slot:head>
        <script type="application/ld+json">@json($structuredData)</script>
    </x-slot:head>

    <div class="site-container py-6 sm:py-10">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-500 dark:text-zinc-400">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('teams.index') }}" class="hover:underline">{{ __('Teams') }}</a></li>
            </ol>
        </nav>

        <header class="mt-6 flex items-center gap-5">
            <x-logo-badge :media="$team->logo" :name="$team->name" size="lg" />

            <div class="min-w-0">
                <h1 class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">{{ $team->name }}</h1>

                <dl class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                    <div class="flex gap-1">
                        <dt>{{ __('Sport') }}:</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $team->sport->name }}</dd>
                    </div>
                    @if ($team->country)
                        <div class="flex gap-1">
                            <dt>{{ __('Country') }}:</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $team->countryName() }}</dd>
                        </div>
                    @endif
                    @if ($team->founded_year)
                        <div class="flex gap-1">
                            <dt>{{ __('Founded') }}:</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $team->founded_year }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </header>

        @if ($upcomingFixtures->isNotEmpty() || $recentResults->isNotEmpty())
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

        @include('site.partials.subject-articles', ['articles' => $articles, 'subjectName' => $team->name])
    </div>
</x-layouts::site>
