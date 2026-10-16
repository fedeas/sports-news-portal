<?php

namespace App\Actions;

use App\Enums\MediaCollection;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ReplaceOwnedMedia
{
    public function __construct(private UploadMedia $uploadMedia) {}

    /**
     * Save the owner with a newly uploaded image (an author photo, a team logo...), or without its current one.
     *
     * The upload is validated before the owner is saved. The new media records its owner, and the
     * previous media and its files are deleted once the owner no longer points at it.
     *
     * @param  string  $relation  a BelongsTo-to-Media relation on the owner, e.g. "photo" or "logo"
     * @param  string  $errorKey  the form field upload errors are reported under
     *
     * @throws ValidationException
     */
    public function handle(
        Model $owner,
        string $relation,
        ?UploadedFile $file,
        bool $remove,
        MediaCollection $collection,
        User $uploader,
        string $altText,
        string $errorKey,
    ): void {
        $newMedia = null;

        if ($file !== null) {
            try {
                $newMedia = $this->uploadMedia->handle($file, $collection, $uploader, $altText);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([$errorKey => Arr::first(Arr::flatten($exception->errors()))]);
            }
        }

        /** @var Media|null $oldMedia */
        $oldMedia = $owner->getRelationValue($relation);

        /** @var BelongsTo<Media, Model> $belongsTo */
        $belongsTo = $owner->{$relation}();

        if ($newMedia !== null) {
            $belongsTo->associate($newMedia);
        } elseif ($remove) {
            $belongsTo->dissociate();
        }

        $owner->save();

        $newMedia?->mediable()->associate($owner)->save();

        if ($oldMedia !== null && $owner->getAttribute($belongsTo->getForeignKeyName()) !== $oldMedia->id) {
            $oldMedia->delete();
        }
    }
}
