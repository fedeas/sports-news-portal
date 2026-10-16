<x-layouts::site
    :title="$tag->name"
    :description="__('All the news on :topic.', ['topic' => $tag->name])"
    :canonical="$canonical"
>
    <div class="site-container py-6 sm:py-10">
        <nav aria-label="{{ __('Breadcrumb') }}" class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">
            <ol class="flex flex-wrap items-center gap-1.5">
                <li><a href="{{ route('home') }}" class="hover:underline">{{ __('Home') }}</a></li>
                <li aria-hidden="true">/</li>
                <li>{{ __('Topic') }}</li>
            </ol>
        </nav>

        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $tag->name }}</h1>

        @if ($articles->isEmpty())
            <p class="mt-10 text-zinc-600 dark:text-zinc-400">{{ __('No articles on this topic yet.') }}</p>
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
