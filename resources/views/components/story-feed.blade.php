@props([
    'articles',
    // 2 runs two columns on wide screens; 1 keeps one column at a reading width.
    'columns' => 2,
])

{{-- A feed: picture left, headline, a line of summary and the writer. --}}
<div {{ $attributes->class(['grid gap-x-8', 'xl:grid-cols-2' => $columns === 2, 'max-w-4xl' => $columns === 1]) }}>
    @foreach ($articles as $article)
        <x-article-row
            :article="$article"
            size="lg"
            :show-excerpt="true"
            class="border-b border-zinc-200 py-4 dark:border-zinc-800"
        />
    @endforeach
</div>
