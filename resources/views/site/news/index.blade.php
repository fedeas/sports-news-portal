<x-layouts::site
    :title="__('News')"
    :description="__('Every story on :site as it is published, newest first.', ['site' => config('app.name')])"
    :canonical="$canonical"
>
    <div class="site-container page-with-rail py-6 sm:py-10">
        <div class="min-w-0">
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('News') }}</h1>

            @if ($articles->isEmpty())
                <p class="mt-10 text-zinc-600 dark:text-zinc-400">{{ __('No articles have been published yet.') }}</p>
            @else
                {{-- One running list, broken by day the way a news wire is. --}}
                @foreach ($days as $day => $dayArticles)
                    @php($date = \Illuminate\Support\Carbon::parse($day, config('app.display_timezone')))

                    <section aria-labelledby="day-{{ $day }}-heading" class="mt-8">
                        <div class="section-heading">
                            <h2 id="day-{{ $day }}-heading" class="text-lg font-extrabold">
                                @if ($date->isToday())
                                    {{ __('Today') }}
                                @elseif ($date->isYesterday())
                                    {{ __('Yesterday') }}
                                @else
                                    {{ $date->locale(app()->getLocale())->translatedFormat('l, j F Y') }}
                                @endif
                            </h2>
                        </div>

                        <ol class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($dayArticles as $article)
                                <li class="flex gap-4 py-4">
                                    <x-local-datetime :value="$article->published_at" format="H:i" class="figures w-12 shrink-0 pt-0.5 text-sm font-bold" />

                                    <x-article-row :article="$article" :show-date="false" class="flex-1" />
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach

                <div class="mt-10">
                    {{ $articles->links() }}
                </div>
            @endif
        </div>

        <div class="page-with-rail__rail">
            @include('partials.site-rail')
        </div>
    </div>
</x-layouts::site>
