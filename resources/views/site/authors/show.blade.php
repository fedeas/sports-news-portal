<x-layouts::site
    :title="$author->name"
    :description="$author->bio ?? __('Articles by :author.', ['author' => $author->name])"
    :canonical="$canonical"
>
    <x-slot:head>
        <script type="application/ld+json">@json($structuredData)</script>
    </x-slot:head>

    <div class="site-container py-6 sm:py-10">
        <nav aria-label="{{ __('Breadcrumb') }}" class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('authors.index') }}" class="hover:underline">{{ __('Writers') }}</a></li>
            </ol>
        </nav>

        <header class="flex flex-wrap items-center gap-5">
            @if ($author->photo)
                <x-media-image
                    :media="$author->photo"
                    :lazy="false"
                    sizes="96px"
                    class="size-24 rounded-full object-cover"
                />
            @endif

            <div class="min-w-0">
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $author->name }}</h1>

                @if ($author->bio)
                    <p class="mt-2 max-w-2xl text-zinc-600 dark:text-zinc-400">{{ $author->bio }}</p>
                @endif

                @if ($author->socialLinks())
                    <ul class="mt-3 flex flex-wrap gap-3 text-sm font-semibold">
                        @foreach ($author->socialLinks() as $network => $link)
                            <li>
                                <a href="{{ $link }}" rel="nofollow noopener" target="_blank" class="text-brand-content hover:underline">
                                    {{ ucfirst($network) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </header>

        @if ($articles->isEmpty())
            <p class="mt-10 text-zinc-600 dark:text-zinc-400">{{ __('No articles by this writer yet.') }}</p>
        @else
            <div class="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <x-article-card :article="$article" :lazy="$loop->index >= 3" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $articles->links() }}
            </div>
        @endif
    </div>
</x-layouts::site>
