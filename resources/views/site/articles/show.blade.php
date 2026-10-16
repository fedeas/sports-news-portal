<x-layouts::site
    :title="$article->meta_title ?? $article->title"
    :description="$description"
    :canonical="$canonical"
    og-type="article"
    :image="$image"
>
    <x-slot:head>
        @if ($isPreview ?? false)
            <meta name="robots" content="noindex, nofollow" />
        @else
            <meta name="robots" content="max-image-preview:large" />
        @endif
        @if ($article->published_at)
            <meta property="article:published_time" content="{{ $article->published_at->toIso8601String() }}" />
        @endif
        <meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}" />
        <meta property="article:section" content="{{ $article->category->name }}" />
        @foreach ($article->tags as $tag)
            <meta property="article:tag" content="{{ $tag->name }}" />
        @endforeach
        @unless ($isPreview ?? false)
            <script type="application/ld+json">@json($structuredData)</script>
        @endunless
    </x-slot:head>

    @if ($isPreview ?? false)
        <div role="status" class="font-ui bg-ink px-4 py-3 text-center text-sm font-semibold text-white dark:bg-white dark:text-ink">
            {{ $isLive
                ? __('Preview: this is how the article looks to readers.')
                : __('Preview: this article is not live. Anyone with this link can see it until the link expires.') }}
        </div>
    @endif

    <div class="site-container page-with-rail py-6 sm:py-10">
        {{-- The story keeps a reading width and sits centred in the space left of the rail. --}}
        <div class="mx-auto w-full max-w-4xl min-w-0">
    <article>
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-500 dark:text-zinc-400">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                @if ($article->category->parent)
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ $article->category->parent->url() }}" class="hover:underline">{{ $article->category->parent->name }}</a></li>
                @endif
                <li aria-hidden="true">/</li>
                <li><a href="{{ $article->category->url() }}" class="hover:underline">{{ $article->category->name }}</a></li>
            </ol>
        </nav>

        <header class="mt-4 space-y-4">
            @if ($article->is_breaking)
                <span class="figures inline-block bg-ink px-2 py-0.5 text-xs font-bold text-white dark:bg-white dark:text-ink">
                    {{ __('Breaking news') }}
                </span>
            @endif

            <h1 class="text-3xl leading-[1.1] font-semibold text-balance sm:text-[2.5rem]">{{ $article->title }}</h1>

            @if ($article->excerpt)
                <p class="text-lg text-zinc-600 dark:text-zinc-300">{{ $article->excerpt }}</p>
            @endif

            <p class="flex flex-wrap items-center gap-x-2 border-t border-zinc-200 pt-3 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                <span>
                    {{ __('By') }}
                    @foreach ($bylineAuthors as $bylineAuthor)
                        <a href="{{ $bylineAuthor->url() }}" class="font-medium hover:underline">{{ $bylineAuthor->name }}</a>{{ ! $loop->last ? ($loop->remaining === 1 ? ' & ' : ', ') : '' }}
                    @endforeach
                </span>
                @if ($article->published_at)
                    <span aria-hidden="true">·</span>
                    <x-local-datetime :value="$article->published_at" class="figures" />
                @endif
            </p>
        </header>

        @if ($article->featuredImage)
            <figure class="-mx-4 mt-6 sm:mx-0">
                <x-media-image
                    :media="$article->featuredImage"
                    :lazy="false"
                    fetchpriority="high"
                    sizes="(min-width: 896px) 896px, 100vw"
                    class="aspect-video w-full object-cover"
                />

                @if ($article->featuredImage->alt_text)
                    <figcaption class="px-4 pt-2 text-sm text-zinc-500 sm:px-0 dark:text-zinc-400">{{ $article->featuredImage->alt_text }}</figcaption>
                @endif
            </figure>
        @endif

        {{-- The body is the only unescaped output: it is sanitised with HTMLPurifier when saved (SaveArticle). --}}
        <div class="article-body mt-8">
            {!! $article->body !!}
        </div>

        @unless ($isPreview ?? false)
            <x-share-links :url="$canonical" :title="$article->title" class="mt-10" />
        @endunless

        @if ($article->tags->isNotEmpty())
            <ul class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Tags') }}">
                @foreach ($article->tags as $tag)
                    <li>
                        <a href="{{ $tag->url() }}" class="inline-block rounded-full bg-zinc-100 px-3 py-1 text-sm hover:text-brand-content dark:bg-zinc-800">
                            {{ $tag->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($article->activeLeagues->isNotEmpty() || $article->activeTeams->isNotEmpty())
            <nav aria-label="{{ __('Teams and leagues') }}" class="mt-4">
                <ul class="flex flex-wrap gap-2">
                    @foreach ([...$article->activeLeagues, ...$article->activeTeams] as $subject)
                        <li>
                            <a
                                href="{{ $subject->url() }}"
                                class="inline-block rounded-full border border-zinc-200 px-3 py-1 text-sm font-medium hover:border-brand hover:text-brand-content dark:border-zinc-700"
                            >
                                {{ $subject->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </article>

            @unless ($isPreview ?? false)
                <livewire:comments :article-id="$article->id" />
            @endunless
        </div>

        @unless ($isPreview ?? false)
            <div class="page-with-rail__rail">
                @include('partials.site-rail')
            </div>
        @endunless
    </div>

    @if ($relatedArticles->isNotEmpty() && ! ($isPreview ?? false))
        <section aria-labelledby="related-heading" class="site-container mt-6 border-t border-zinc-200 pt-10 dark:border-zinc-800">
            <div class="section-heading">
                <h2 id="related-heading" class="text-xl font-extrabold">{{ __('More from :category', ['category' => $article->category->name]) }}</h2>
            </div>

            <div class="mt-6 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedArticles as $relatedArticle)
                    <x-article-card :article="$relatedArticle" :heading-level="3" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts::site>
