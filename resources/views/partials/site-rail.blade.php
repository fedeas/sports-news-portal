{{-- The rail beside the news: where the league stands, what is on next, what people are reading. --}}
<aside class="space-y-6">
    @if ($season && $standings->isNotEmpty())
        <section aria-labelledby="rail-standings-heading">
            <div class="section-heading">
                <h2 id="rail-standings-heading" class="text-lg font-extrabold">
                    <a href="{{ $season->league->url() }}" class="hover:underline">{{ $season->league->name }}</a>
                </h2>
            </div>

            <table class="mt-2 w-full text-sm">
                <caption class="sr-only">{{ __('Standings :season', ['season' => $season->name]) }}</caption>
                <thead class="figures bg-ink text-xs text-white dark:bg-white dark:text-ink">
                    <tr>
                        <th scope="col" class="w-8 py-1.5 text-center">#</th>
                        <th scope="col" class="py-1.5 text-start">{{ __('Team') }}</th>
                        <th scope="col" class="w-10 py-1.5 text-center"><abbr title="{{ __('Played') }}" class="no-underline">{{ __('P') }}</abbr></th>
                        <th scope="col" class="w-10 py-1.5 text-center"><abbr title="{{ __('Points') }}" class="no-underline">{{ __('Pts') }}</abbr></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($standings as $standing)
                        <tr @class([
                            'border-b border-zinc-200 dark:border-zinc-800',
                            'bg-zinc-100 dark:bg-zinc-900' => $standing->position === 1,
                        ])>
                            <td class="figures py-1.5 text-center font-bold">{{ $standing->position }}</td>
                            <th scope="row" class="max-w-40 py-1.5 text-start font-semibold">
                                <a href="{{ $standing->team->url() }}" class="flex min-w-0 items-center gap-2 hover:underline">
                                    <x-logo-badge :media="$standing->team->logo" :name="$standing->team->name" size="sm" />
                                    <span class="truncate">{{ $standing->team->short_name ?? $standing->team->name }}</span>
                                </a>
                            </th>
                            <td class="figures py-1.5 text-center">{{ $standing->played }}</td>
                            <td class="figures py-1.5 text-center font-bold">{{ $standing->points }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <a href="{{ $season->league->url() }}" class="font-ui mt-2 inline-block text-sm font-semibold underline underline-offset-2 hover:no-underline">
                {{ __('Full table') }}
            </a>
        </section>
    @endif

    @if ($upcoming->isNotEmpty())
        <section aria-labelledby="rail-fixtures-heading">
            <div class="section-heading">
                <h2 id="rail-fixtures-heading" class="text-lg font-extrabold">{{ __('Next matches') }}</h2>
            </div>

            <ul class="mt-2 divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($upcoming as $fixture)
                    <li class="py-2">
                        <a href="{{ route('fixtures.show', $fixture) }}" class="block hover:underline">
                            <x-local-datetime :value="$fixture->kickoff_at" format="j M, H:i" class="figures block text-xs text-zinc-500 dark:text-zinc-400" />
                            <span class="mt-0.5 block text-sm font-semibold">
                                {{ $fixture->homeTeam->short_name ?? $fixture->homeTeam->name }}
                                <span class="text-zinc-400" aria-hidden="true">–</span>
                                {{ $fixture->awayTeam->short_name ?? $fixture->awayTeam->name }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($mostRead->isNotEmpty())
        <section aria-labelledby="most-read-heading">
            <div class="section-heading">
                <h2 id="most-read-heading" class="text-lg font-extrabold">{{ __('Most read') }}</h2>
            </div>

            {{-- A ranking, so the numbers carry the order. --}}
            <ol class="mt-2">
                @foreach ($mostRead as $article)
                    <li class="flex items-baseline gap-3 border-b border-zinc-200 py-2 last:border-b-0 dark:border-zinc-800">
                        <span aria-hidden="true" class="figures w-5 shrink-0 text-xl font-bold text-zinc-400 dark:text-zinc-500">{{ $loop->iteration }}</span>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm leading-snug font-semibold text-balance">
                                <a href="{{ $article->url() }}" class="hover:underline">{{ $article->title }}</a>
                            </h3>

                            <x-byline :article="$article" :with-date="false" class="mt-1 text-xs" />
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</aside>
