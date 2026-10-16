<?php

namespace App\Concerns;

use App\Models\SlugHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Remembers a model's previous slugs so its old public URLs can be permanently redirected.
 *
 * @mixin Model
 */
trait RecordsSlugHistory
{
    /**
     * Record the old slug after every slug change.
     */
    protected static function bootRecordsSlugHistory(): void
    {
        static::updated(function (Model $model) {
            if (! $model->wasChanged('slug')) {
                return;
            }

            $type = $model->getMorphClass();

            // An old slug always points at the model that gave it up most recently.
            SlugHistory::query()->updateOrCreate(
                ['sluggable_type' => $type, 'slug' => $model->getOriginal('slug')],
                ['sluggable_id' => $model->getKey()],
            );

            // The current slug always wins, so it never needs a history entry.
            SlugHistory::query()->where('sluggable_type', $type)->where('slug', $model->getAttribute('slug'))->delete();
        });
    }

    /**
     * @return MorphMany<SlugHistory, $this>
     */
    public function slugHistory(): MorphMany
    {
        return $this->morphMany(SlugHistory::class, 'sluggable');
    }
}
