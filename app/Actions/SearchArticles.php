<?php

namespace App\Actions;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SearchArticles
{
    public const MAX_LENGTH = 100;

    /**
     * Shortest word the MySQL FULLTEXT index holds (InnoDB's default `innodb_ft_min_token_size`).
     */
    public const MIN_FULLTEXT_WORD_LENGTH = 3;

    /**
     * Live articles matching the term in their title or excerpt: title matches first, then newest.
     *
     * On MySQL every word of three or more letters must start a word in the title or excerpt, using
     * the FULLTEXT index in boolean mode with the operators stripped from the term, so "Ολυμπ" finds
     * "Ολυμπιάδα". Shorter terms and other drivers (SQLite in tests) require every word with LIKE.
     * The term is always passed as a bound parameter.
     *
     * @return Builder<Article>
     */
    public function handle(string $term): Builder
    {
        $term = Str::of($term)->squish()->limit(self::MAX_LENGTH, '')->value();
        $pattern = "%{$term}%";

        $query = Article::query()
            ->published()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->with(Article::CARD_RELATIONS);

        $booleanTerm = $this->booleanModeTerm($term);

        if ($booleanTerm !== null && in_array($query->getModel()->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $query->whereFullText(['title', 'excerpt'], $booleanTerm, ['mode' => 'boolean']);
        } else {
            foreach ($this->words($term) ?: [$term] as $word) {
                $query->where(fn (Builder $query) => $query->where('title', 'like', "%{$word}%")->orWhere('excerpt', 'like', "%{$word}%"));
            }
        }

        return $query
            ->orderByRaw('case when title like ? then 0 else 1 end', [$pattern])
            ->latest('published_at');
    }

    /**
     * "+word1* +word2*" for the words long enough to be indexed; null when there are none.
     */
    private function booleanModeTerm(string $term): ?string
    {
        $words = collect($this->words($term))
            ->filter(fn (string $word) => mb_strlen($word) >= self::MIN_FULLTEXT_WORD_LENGTH)
            ->map(fn (string $word) => "+{$word}*");

        return $words->isEmpty() ? null : $words->join(' ');
    }

    /**
     * The letters-and-digits words of the term, without punctuation or search operators.
     *
     * @return array<int, string>
     */
    private function words(string $term): array
    {
        return preg_split('/[^\p{L}\p{N}]+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
