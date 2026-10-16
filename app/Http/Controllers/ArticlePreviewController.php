<?php

namespace App\Http\Controllers;

use App\Actions\BuildArticlePage;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class ArticlePreviewController extends Controller
{
    /**
     * Show any article, published or not, to whoever holds an unexpired signed preview link.
     *
     * The page is never indexed or cached, and the signed URL is not sent on as a referrer.
     */
    public function __invoke(Article $article, BuildArticlePage $buildArticlePage): Response
    {
        $article->load(BuildArticlePage::relations());

        return response()
            ->view('site.articles.show', [
                ...$buildArticlePage->handle($article),
                'isPreview' => true,
                'isLive' => Article::query()
                    ->published()
                    ->whereHas('category', fn (Builder $query) => $query->active())
                    ->whereKey($article->id)
                    ->exists(),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
