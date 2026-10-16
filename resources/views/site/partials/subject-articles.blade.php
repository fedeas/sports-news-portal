{{-- Latest live articles about a team or league. Expects $articles (paginator) and $subjectName. --}}
<section aria-labelledby="subject-news-heading" class="mt-10">
    <div class="section-heading">
        <h2 id="subject-news-heading" class="text-xl font-extrabold">{{ __('Latest news') }}</h2>
    </div>

    @if ($articles->isEmpty())
        <p class="mt-6 text-zinc-600 dark:text-zinc-400">{{ __('No articles about :subject yet.', ['subject' => $subjectName]) }}</p>
    @else
        <div class="mt-6 grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($articles as $article)
                <x-article-card :article="$article" :heading-level="3" :lazy="$loop->index >= 3" />
            @endforeach
        </div>

        <div class="mt-10">
            {{ $articles->links() }}
        </div>
    @endif
</section>
