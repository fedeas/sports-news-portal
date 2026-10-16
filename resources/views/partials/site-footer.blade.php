<footer class="mt-16 border-t-4 border-ink bg-white text-zinc-600 dark:border-white dark:bg-zinc-950 dark:text-zinc-400">
    <div class="site-container font-ui flex flex-col gap-4 py-10 text-sm sm:flex-row sm:items-center sm:justify-between">
        <nav aria-label="{{ __('Footer') }}">
            <ul class="flex flex-wrap gap-x-6 gap-y-2">
                <li><a href="{{ route('teams.index') }}" class="hover:text-ink hover:underline dark:hover:text-white">{{ __('Teams') }}</a></li>
                <li><a href="{{ route('leagues.index') }}" class="hover:text-ink hover:underline dark:hover:text-white">{{ __('Leagues') }}</a></li>
                <li><a href="{{ route('authors.index') }}" class="hover:text-ink hover:underline dark:hover:text-white">{{ __('Writers') }}</a></li>
            </ul>
        </nav>

        <p>&copy; {{ now()->year }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
    </div>
</footer>
