@if ($articles->isNotEmpty())
    <section aria-labelledby="breaking-heading" class="bg-ink text-white dark:bg-white dark:text-ink">
        <div class="site-container flex items-center gap-4 py-2">
            <h2 id="breaking-heading" class="figures flex shrink-0 items-center gap-2 text-sm font-bold">
                <span class="live-dot" aria-hidden="true"></span>
                {{ __('Breaking') }}
            </h2>

            <ul class="flex min-w-0 gap-6 overflow-x-auto whitespace-nowrap [scrollbar-width:none]">
                @foreach ($articles as $article)
                    <li>
                        <a href="{{ $article->url() }}" class="text-[15px] font-semibold hover:underline focus-visible:underline focus-visible:outline-none">
                            {{ $article->title }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
