@php
    $fixture = $this->fixtures[0] ?? null;
    $status = $fixture ? App\Enums\FixtureStatus::from($fixture['status']) : null;
    $kickoffAt = $fixture ? Carbon\CarbonImmutable::parse($fixture['kickoff_at']) : null;
    // Poll only while something can still change soon.
    $shouldPoll = $status === App\Enums\FixtureStatus::Live
        || ($status === App\Enums\FixtureStatus::Scheduled && $kickoffAt->between(now()->subHours(3), now()->addHours(3)));
@endphp

<div @if ($shouldPoll) wire:poll.visible.30s @endif>
    @if ($fixture)
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-4 text-center">
            <a href="{{ $fixture['home']['url'] }}" class="text-lg leading-tight font-extrabold text-balance hover:underline sm:text-2xl">
                {{ $fixture['home']['name'] }}
            </a>

            <div aria-live="polite">
                @if ($status->hasScore())
                    <p class="text-4xl font-extrabold tabular-nums sm:text-5xl">{{ $fixture['score_home'] }} – {{ $fixture['score_away'] }}</p>
                @else
                    <x-local-datetime :value="$kickoffAt" format="H:i" class="block text-3xl font-extrabold tabular-nums" />
                @endif

                <p @class([
                    'mt-1 text-sm font-semibold',
                    'text-brand-content' => $status === App\Enums\FixtureStatus::Live,
                    'text-zinc-500 dark:text-zinc-400' => $status !== App\Enums\FixtureStatus::Live,
                ])>
                    {{ $status->label() }}@if ($status === App\Enums\FixtureStatus::Live && $fixture['minute']) · {{ $fixture['minute'] }}′@endif
                </p>
            </div>

            <a href="{{ $fixture['away']['url'] }}" class="text-lg leading-tight font-extrabold text-balance hover:underline sm:text-2xl">
                {{ $fixture['away']['name'] }}
            </a>
        </div>
    @endif
</div>
