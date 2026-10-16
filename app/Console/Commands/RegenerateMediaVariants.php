<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMediaVariantsJob;
use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('media:regenerate-variants {--sync : Generate now instead of queueing a job per image}')]
#[Description('Generate the responsive sizes again for every stored image, e.g. after adding a width')]
class RegenerateMediaVariants extends Command
{
    public function handle(): int
    {
        $count = 0;

        Media::query()->orderBy('id')->each(function (Media $media) use (&$count) {
            $this->option('sync')
                ? GenerateMediaVariantsJob::dispatchSync($media)
                : GenerateMediaVariantsJob::dispatch($media);

            $count++;
        });

        $this->components->info($this->option('sync')
            ? "Regenerated the sizes of {$count} images."
            : "Queued {$count} images; the queue worker will regenerate their sizes.");

        return self::SUCCESS;
    }
}
