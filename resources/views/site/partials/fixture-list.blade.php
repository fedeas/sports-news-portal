{{-- A list of matches. Expects $fixtures, $heading, $headingId and $emptyText. --}}
<section aria-labelledby="{{ $headingId }}">
    <div class="section-heading">
        <h2 id="{{ $headingId }}" class="text-xl font-extrabold">{{ $heading }}</h2>
    </div>

    @if ($fixtures->isEmpty())
        <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $emptyText }}</p>
    @else
        <ul class="mt-2 divide-y divide-zinc-200 dark:divide-zinc-800">
            @foreach ($fixtures as $fixture)
                <li class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 py-3">
                    <a href="{{ $fixture->homeTeam->url() }}" class="flex min-w-0 items-center justify-end gap-2 text-end text-sm font-semibold hover:underline">
                        <span class="truncate">{{ $fixture->homeTeam->name }}</span>
                        <x-logo-badge :media="$fixture->homeTeam->logo" :name="$fixture->homeTeam->name" size="sm" />
                    </a>

                    <a
                        href="{{ route('fixtures.show', $fixture) }}"
                        class="min-w-16 rounded text-center hover:underline"
                        aria-label="{{ __('Match details: :home vs :away', ['home' => $fixture->homeTeam->name, 'away' => $fixture->awayTeam->name]) }}"
                    >
                        @if ($fixture->status->hasScore())
                            <span class="figures text-xl font-bold">{{ $fixture->score_home }} – {{ $fixture->score_away }}</span>
                        @else
                            <x-local-datetime :value="$fixture->kickoff_at" format="j M, H:i" class="figures block text-xs text-zinc-600 dark:text-zinc-400" />
                        @endif

                        @if ($fixture->status === App\Enums\FixtureStatus::Live)
                            <span class="figures block text-xs font-bold">
                                {{ __('Live') }}@if ($fixture->minute) {{ $fixture->minute }}′@endif
                            </span>
                        @elseif (in_array($fixture->status, [App\Enums\FixtureStatus::Postponed, App\Enums\FixtureStatus::Cancelled], true))
                            <span class="block text-xs font-semibold text-zinc-500">{{ $fixture->status->label() }}</span>
                        @endif
                    </a>

                    <a href="{{ $fixture->awayTeam->url() }}" class="flex min-w-0 items-center gap-2 text-sm font-semibold hover:underline">
                        <x-logo-badge :media="$fixture->awayTeam->logo" :name="$fixture->awayTeam->name" size="sm" />
                        <span class="truncate">{{ $fixture->awayTeam->name }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
