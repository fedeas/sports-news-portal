{{-- A season's league table. Expects $season and $standings. --}}
@php
    $formLabels = ['W' => __('Win'), 'D' => __('Draw'), 'L' => __('Loss')];
    $formLetters = ['W' => __('W'), 'D' => __('D'), 'L' => __('L')];
    $formColours = ['W' => 'bg-ink text-white dark:bg-white dark:text-ink', 'D' => 'border border-ink dark:border-white', 'L' => 'bg-zinc-300 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200'];
@endphp

<section aria-labelledby="standings-heading">
    <div class="section-heading">
        <h2 id="standings-heading" class="flex items-baseline gap-2 text-xl font-extrabold">
            {{ __('Standings') }}
            <span class="figures text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $season->name }}</span>
        </h2>
    </div>

    <div class="mt-2 overflow-x-auto">
        <table class="w-full text-sm">
            <caption class="sr-only">{{ __('Standings :season', ['season' => $season->name]) }}</caption>
            <thead class="figures bg-ink text-xs text-white dark:bg-white dark:text-ink">
                <tr>
                    <th scope="col" class="py-2 pe-2 text-start">#</th>
                    <th scope="col" class="py-2 text-start">{{ __('Team') }}</th>
                    <th scope="col" class="px-2 py-2 text-center"><abbr title="{{ __('Played') }}" class="no-underline">{{ __('P') }}</abbr></th>
                    <th scope="col" class="hidden px-2 py-2 text-center sm:table-cell"><abbr title="{{ __('Won') }}" class="no-underline">{{ __('W') }}</abbr></th>
                    <th scope="col" class="hidden px-2 py-2 text-center sm:table-cell"><abbr title="{{ __('Drawn') }}" class="no-underline">{{ __('D') }}</abbr></th>
                    <th scope="col" class="hidden px-2 py-2 text-center sm:table-cell"><abbr title="{{ __('Lost') }}" class="no-underline">{{ __('L') }}</abbr></th>
                    <th scope="col" class="hidden px-2 py-2 text-center sm:table-cell">{{ __('Goals') }}</th>
                    <th scope="col" class="px-2 py-2 text-center"><abbr title="{{ __('Goal difference') }}" class="no-underline">{{ __('GD') }}</abbr></th>
                    <th scope="col" class="px-2 py-2 text-center"><abbr title="{{ __('Points') }}" class="no-underline">{{ __('Pts') }}</abbr></th>
                    <th scope="col" class="hidden py-2 ps-2 text-start md:table-cell">{{ __('Form') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($standings as $standing)
                    <tr @class([
                        'border-b border-zinc-200 dark:border-zinc-800',
                        'bg-zinc-100 dark:bg-zinc-900' => $standing->position === 1,
                    ])>
                        <td class="figures w-8 py-2.5 pe-2 text-center font-bold">{{ $standing->position }}</td>
                        <th scope="row" class="max-w-40 py-2.5 text-start font-semibold sm:max-w-none">
                            <a href="{{ $standing->team->url() }}" class="flex min-w-0 items-center gap-2 hover:underline">
                                <x-logo-badge :media="$standing->team->logo" :name="$standing->team->name" size="sm" />
                                <span class="truncate">{{ $standing->team->name }}</span>
                            </a>
                        </th>
                        <td class="px-2 py-2.5 text-center figures">{{ $standing->played }}</td>
                        <td class="hidden px-2 py-2.5 text-center figures sm:table-cell">{{ $standing->won }}</td>
                        <td class="hidden px-2 py-2.5 text-center figures sm:table-cell">{{ $standing->drawn }}</td>
                        <td class="hidden px-2 py-2.5 text-center figures sm:table-cell">{{ $standing->lost }}</td>
                        <td class="hidden px-2 py-2.5 text-center figures sm:table-cell">{{ $standing->goals_for }}:{{ $standing->goals_against }}</td>
                        <td class="px-2 py-2.5 text-center figures">{{ $standing->goal_difference > 0 ? '+' : '' }}{{ $standing->goal_difference }}</td>
                        <td class="px-2 py-2.5 text-center font-extrabold figures">{{ $standing->points }}</td>
                        <td class="hidden py-2.5 ps-2 md:table-cell">
                            <span class="flex gap-1">
                                @foreach (str_split((string) $standing->form) as $result)
                                    @if (isset($formLabels[$result]))
                                        <span
                                            class="{{ $formColours[$result] }} figures inline-flex size-5 items-center justify-center text-xs font-bold"
                                            title="{{ $formLabels[$result] }}"
                                        >
                                            <span aria-hidden="true">{{ $formLetters[$result] }}</span>
                                            <span class="sr-only">{{ $formLabels[$result] }}</span>
                                        </span>
                                    @endif
                                @endforeach
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
