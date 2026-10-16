<x-layouts::site
    :title="__(':home vs :away', ['home' => $fixture->homeTeam->name, 'away' => $fixture->awayTeam->name])"
    :description="__(':home vs :away, :league :season.', [
        'home' => $fixture->homeTeam->name,
        'away' => $fixture->awayTeam->name,
        'league' => $fixture->season->league->name,
        'season' => $fixture->season->name,
    ])"
    :canonical="route('fixtures.show', $fixture)"
>
    <div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-500 dark:text-zinc-400">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ $fixture->season->league->url() }}" class="hover:underline">{{ $fixture->season->league->name }}</a></li>
            </ol>
        </nav>

        <h1 class="sr-only">{{ $fixture->homeTeam->name }} – {{ $fixture->awayTeam->name }}</h1>

        <p class="mt-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
            {{ collect([$fixture->season->league->name, $fixture->season->name, $fixture->round ? __('Round').' '.$fixture->round : null])->filter()->join(' · ') }}
        </p>

        <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-4">
            <div class="flex justify-center"><x-logo-badge :media="$fixture->homeTeam->logo" :name="$fixture->homeTeam->name" size="lg" /></div>
            <div class="w-24"></div>
            <div class="flex justify-center"><x-logo-badge :media="$fixture->awayTeam->logo" :name="$fixture->awayTeam->name" size="lg" /></div>
        </div>

        <div class="mt-4">
            <livewire:live-scores :fixture-id="$fixture->id" :initial="$initial" />
        </div>

        <dl class="mx-auto mt-10 grid max-w-md gap-3 border-t border-zinc-200 pt-6 text-sm dark:border-zinc-800">
            <div class="flex justify-between gap-4">
                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Kickoff') }}</dt>
                <dd><x-local-datetime :value="$fixture->kickoff_at" /></dd>
            </div>
            @if ($fixture->venue)
                <div class="flex justify-between gap-4">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Venue') }}</dt>
                    <dd>{{ $fixture->venue }}</dd>
                </div>
            @endif
        </dl>
    </div>
</x-layouts::site>
