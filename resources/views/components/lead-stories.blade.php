@props([
    'lead',
    // Up to four stories set beside the lead.
    'beside',
    // What the stories beside the lead are, for screen readers.
    'label',
])

{{-- One main story in half the column, the next four beside it, picture left, spread over its height. --}}
<div {{ $attributes->class('grid gap-6 lg:grid-cols-2 lg:gap-0') }}>
    <div class="lg:pe-6">
        <x-lead-story :article="$lead" />
    </div>

    @if ($beside->isNotEmpty())
        <section aria-label="{{ $label }}" class="lg:border-s lg:border-zinc-200 lg:ps-6 dark:lg:border-zinc-800">
            <div class="flex h-full flex-col justify-between divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($beside as $article)
                    <x-article-row :article="$article" size="lg" class="py-4 first:pt-0 last:pb-0" />
                @endforeach
            </div>
        </section>
    @endif
</div>
