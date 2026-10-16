<x-layouts::site
    :title="__('Page not found')"
    :description="__('The page you are looking for does not exist or has been moved.')"
    :canonical="false"
>
    <x-slot:head>
        <meta name="robots" content="noindex" />
    </x-slot:head>

    <div class="mx-auto max-w-2xl px-4 py-16 text-center sm:py-24">
        <p class="text-6xl font-extrabold text-brand-content" aria-hidden="true">404</p>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Page not found') }}</h1>
        <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ __('The page you are looking for does not exist or has been moved.') }}</p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a
                href="{{ route('home') }}"
                class="rounded-lg bg-brand px-4 py-2 font-semibold text-white hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
            >
                {{ __('Go to the homepage') }}
            </a>
            <a
                href="{{ route('search') }}"
                class="rounded-lg border border-zinc-300 px-4 py-2 font-semibold hover:border-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:border-zinc-700"
            >
                {{ __('Search') }}
            </a>
        </div>
    </div>
</x-layouts::site>
