<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SlugHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A slug a model used to have, kept so its old URLs can redirect with a 301.
 *
 * @property int $id
 * @property string $sluggable_type
 * @property int $sluggable_id
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 */
#[Table(name: 'slug_history')]
#[Fillable(['sluggable_type', 'sluggable_id', 'slug'])]
class SlugHistory extends Model
{
    /** @use HasFactory<SlugHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return MorphTo<Model, $this>
     */
    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The model of the given class that used to have this slug, if it still exists.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel|null
     */
    public static function findPreviousOwner(string $modelClass, string $slug): ?Model
    {
        $owner = static::query()
            ->where('sluggable_type', (new $modelClass)->getMorphClass())
            ->where('slug', $slug)
            ->first()
            ?->sluggable;

        return $owner instanceof $modelClass ? $owner : null;
    }
}
