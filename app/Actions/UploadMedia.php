<?php

namespace App\Actions;

use App\Enums\MediaCollection;
use App\Jobs\GenerateMediaVariantsJob;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;

class UploadMedia
{
    /**
     * Accepted image types. SVG is never accepted, for any role, because it can carry scripts.
     */
    public const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const MAX_KILOBYTES = 10_240;

    public const MAX_DIMENSION = 8_000;

    public function __construct(private ImageManager $images) {}

    /**
     * Validate and store an uploaded image, then queue its responsive variants.
     *
     * The image is decoded and re-encoded before it is stored, which strips EXIF/GPS
     * metadata and drops anything that isn't image data (e.g. code appended to the file).
     * The stored filename is always generated; the client filename is kept only as metadata.
     *
     * @throws ValidationException
     */
    public function handle(
        UploadedFile $file,
        MediaCollection $collection,
        User $uploader,
        ?string $altText = null,
        string $disk = 'public',
    ): Media {
        Validator::make(
            ['file' => $file, 'alt_text' => $altText],
            [
                'file' => [
                    'required',
                    'file',
                    'max:'.self::MAX_KILOBYTES,
                    'mimetypes:'.implode(',', self::ALLOWED_MIME_TYPES),
                    Rule::dimensions()->maxWidth(self::MAX_DIMENSION)->maxHeight(self::MAX_DIMENSION),
                ],
                'alt_text' => [$collection->requiresAltText() ? 'required' : 'nullable', 'string', 'max:255'],
            ],
        )->validate();

        $mimeType = (string) $file->getMimeType();

        try {
            $image = $this->images->decodePath($file->getRealPath());
        } catch (ImageException) {
            throw ValidationException::withMessages(['file' => __('The file is not a valid image.')]);
        }

        [$encoder, $extension] = match ($mimeType) {
            'image/png' => [new PngEncoder, 'png'],
            'image/webp' => [new WebpEncoder(quality: 85), 'webp'],
            default => [new JpegEncoder(quality: 85), 'jpg'],
        };

        $encoded = $image->encode($encoder);
        $path = 'media/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;

        Storage::disk($disk)->put($path, $encoded->toString(), 'public');

        $media = new Media(['alt_text' => $altText]);
        $media->forceFill([
            'disk' => $disk,
            'path' => $path,
            'original_filename' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
            'mime_type' => $mimeType,
            'size' => $encoded->size(),
            'width' => $image->width(),
            'height' => $image->height(),
            'collection' => $collection,
        ]);
        $media->uploader()->associate($uploader);
        $media->save();

        GenerateMediaVariantsJob::dispatch($media)->afterCommit();

        return $media;
    }
}
