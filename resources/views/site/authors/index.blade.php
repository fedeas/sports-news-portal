<x-layouts::site
    :title="__('Writers')"
    :description="__('The writers of :site.', ['site' => config('app.name')])"
>
    <div class="site-container py-6 sm:py-10">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Writers') }}</h1>

        @if ($authors->isEmpty())
            <p class="mt-10 text-zinc-600 dark:text-zinc-400">{{ __('No writers yet.') }}</p>
        @else
            <ul class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($authors as $author)
                    <li class="flex items-center gap-4">
                        @if ($author->photo)
                            <x-media-image :media="$author->photo" sizes="64px" class="size-16 rounded-full object-cover" />
                        @else
                            <span aria-hidden="true" class="flex size-16 items-center justify-center rounded-full bg-zinc-100 text-lg font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ mb_substr($author->name, 0, 1) }}
                            </span>
                        @endif

                        <div class="min-w-0">
                            <a href="{{ $author->url() }}" class="font-bold hover:underline">{{ $author->name }}</a>

                            @if ($author->bio)
                                <p class="mt-0.5 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $author->bio }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-10">
                {{ $authors->links() }}
            </div>
        @endif
    </div>
</x-layouts::site>
