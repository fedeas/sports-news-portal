<x-layouts::site
    :title="__('Search')"
    :description="__('Search news and teams on :site.', ['site' => config('app.name')])"
    :canonical="route('search')"
>
    <x-slot:head>
        <meta name="robots" content="noindex, follow" />
    </x-slot:head>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:py-10">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Search') }}</h1>

        <livewire:search />
    </div>
</x-layouts::site>
