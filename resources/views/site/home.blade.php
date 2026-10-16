<x-layouts::site
    :title="__('Sports news')"
    :description="__('Sports news from Cyprus and Greece: football, basketball, results and analysis.')"
    :canonical="route('home')"
>
    <x-slot:head>
        <script type="application/ld+json">@json($structuredData)</script>
    </x-slot:head>

    <h1 class="sr-only">{{ config('app.name') }}: {{ __('Sports news') }}</h1>

    @include('site.home.breaking', ['articles' => $breaking])

    <livewire:live-scores />

    <div class="site-container py-6 sm:py-8">
        @if ($hero === null)
            <p class="py-16 text-center text-zinc-600 dark:text-zinc-400">{{ __('No articles have been published yet.') }}</p>
        @else
            {{-- One grid, so the narrow column at the far right runs unbroken from the top of the page. --}}
            <div class="page-with-rail">
                <div class="min-w-0 space-y-10">
                    <x-lead-stories :lead="$hero" :beside="$latest->take(4)" :label="__('Latest news')" />

                    @if ($latest->count() > 4)
                        <section aria-labelledby="more-news-heading">
                            <div class="section-heading">
                                <h2 id="more-news-heading" class="text-xl font-extrabold">{{ __('More news') }}</h2>
                            </div>

                            <x-story-feed :articles="$latest->skip(4)" class="mt-2" />
                        </section>
                    @endif

                    @foreach ($categoryBlocks as $block)
                        @include('site.home.category-block', $block)
                    @endforeach
                </div>

                <div class="page-with-rail__rail">
                    @include('partials.site-rail')
                </div>
            </div>
        @endif
    </div>
</x-layouts::site>
