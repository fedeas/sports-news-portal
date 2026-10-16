<div wire:poll.visible.60s>
    @if ($this->fixtures !== [])
        {{-- The scoreboard: today's matches, read left to right like the board at the ground. --}}
        <section aria-labelledby="live-scores-heading" class="border-b border-zinc-300 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="site-container flex items-stretch gap-4">
                <h2 id="live-scores-heading" class="figures flex shrink-0 items-center text-sm font-bold text-zinc-500 dark:text-zinc-400">
                    {{ __('Today') }}
                </h2>

                <ul class="flex min-w-0 gap-6 overflow-x-auto py-2.5 [scrollbar-width:none]">
                    @foreach ($this->fixtures as $fixture)
                        @php($status = App\Enums\FixtureStatus::from($fixture['status']))
                        <li wire:key="live-score-{{ $fixture['id'] }}" class="shrink-0 border-s border-zinc-300 ps-6 first:border-s-0 first:ps-0 dark:border-zinc-700">
                            <a
                                href="{{ $fixture['url'] }}"
                                class="block w-40 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ink dark:focus-visible:outline-white"
                            >
                                <span class="figures flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($status === App\Enums\FixtureStatus::Live)
                                        <span class="live-dot" aria-hidden="true"></span>
                                        <span class="font-bold text-ink dark:text-white">
                                            {{ __('Live') }}@if ($fixture['minute']) {{ $fixture['minute'] }}′@endif
                                        </span>
                                    @elseif ($status === App\Enums\FixtureStatus::Scheduled)
                                        <x-local-datetime :value="Carbon\CarbonImmutable::parse($fixture['kickoff_at'])" format="H:i" />
                                    @else
                                        <span>{{ $status->label() }}</span>
                                    @endif

                                    <span class="truncate">· {{ $fixture['league'] }}</span>
                                </span>

                                <span class="mt-1 flex justify-between gap-3 text-sm font-semibold">
                                    <span class="truncate">{{ $fixture['home']['short_name'] ?? $fixture['home']['name'] }}</span>
                                    <span class="figures text-base">{{ $fixture['score_home'] }}</span>
                                </span>
                                <span class="flex justify-between gap-3 text-sm font-semibold">
                                    <span class="truncate">{{ $fixture['away']['short_name'] ?? $fixture['away']['name'] }}</span>
                                    <span class="figures text-base">{{ $fixture['score_away'] }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
</div>
