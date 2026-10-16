<x-layouts::site
    :title="__('Teams')"
    :description="__('All the teams covered by :site.', ['site' => config('app.name')])"
    :canonical="$teams->currentPage() > 1 ? route('teams.index').'?page='.$teams->currentPage() : route('teams.index')"
>
    <div class="site-container py-6 sm:py-10">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Teams') }}</h1>

        @if ($teams->isEmpty())
            <p class="mt-10 text-zinc-600 dark:text-zinc-400">{{ __('No teams yet.') }}</p>
        @else
            <ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($teams as $team)
                    <li>
                        <a
                            href="{{ $team->url() }}"
                            class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 font-semibold transition-colors hover:border-brand focus-visible:outline-2 focus-visible:outline-brand dark:border-zinc-800"
                        >
                            <x-logo-badge :media="$team->logo" :name="$team->name" />
                            {{ $team->name }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-10">
                {{ $teams->links() }}
            </div>
        @endif
    </div>
</x-layouts::site>
