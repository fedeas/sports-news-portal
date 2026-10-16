<x-layouts::site
    :title="__('Leagues')"
    :description="__('All the leagues and cups covered by :site.', ['site' => config('app.name')])"
    :canonical="route('leagues.index')"
>
    <div class="site-container py-6 sm:py-10">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Leagues') }}</h1>

        @if ($leagues->isEmpty())
            <p class="mt-10 text-zinc-600 dark:text-zinc-400">{{ __('No leagues yet.') }}</p>
        @else
            <ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($leagues as $league)
                    <li>
                        <a
                            href="{{ $league->url() }}"
                            class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 font-semibold transition-colors hover:border-brand focus-visible:outline-2 focus-visible:outline-brand dark:border-zinc-800"
                        >
                            <x-logo-badge :media="$league->logo" :name="$league->name" />
                            {{ $league->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts::site>
