@php($lead = $articles->first())

{{-- A section reads like a page of the paper: one story large, the rest as headlines. --}}
<section aria-labelledby="category-{{ $category->id }}-heading">
    <div class="section-heading">
        <h2 id="category-{{ $category->id }}-heading" class="text-2xl font-extrabold">
            <a href="{{ $category->url() }}" class="inline-flex items-center gap-2 hover:underline">
                <x-section-icon :icon="$category->icon" class="size-6" />
                {{ $category->name }}
            </a>
        </h2>

        <a href="{{ $category->url() }}" class="order-last shrink-0 text-sm font-semibold text-brand-content hover:underline">
            {{ __('See all') }}<span class="sr-only">: {{ $category->name }}</span>
        </a>
    </div>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 sm:gap-8">
        <x-article-card :article="$lead" :heading-level="3" class="[&_h3]:text-2xl" />

        @if ($articles->count() > 1)
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($articles->skip(1) as $article)
                    <x-article-row :article="$article" class="py-3 first:pt-0" />
                @endforeach
            </div>
        @endif
    </div>
</section>
