<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneMapUploadChunks extends Command
{
    protected $signature = 'maps:prune-chunks {--hours=24 : Elimina gli upload inattivi da più di questo numero di ore}';

    protected $description = 'Elimina i frammenti orfani di upload di mappe abbandonati';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $threshold = now()->subHours((int) $this->option('hours'))->getTimestamp();
        $pruned = 0;

        foreach ($disk->allDirectories('map-chunks') as $directory) {
            if (! $this->isUploadDirectory($directory)) {
                continue;
            }

            $lastActivity = collect($disk->files($directory))
                ->map(fn (string $file): int => $disk->lastModified($file))
                ->max() ?? $disk->lastModified($directory);

            if ($lastActivity < $threshold) {
                $disk->deleteDirectory($directory);
                $pruned++;
            }
        }

        $this->info("Upload di mappe orfani eliminati: {$pruned}");

        return self::SUCCESS;
    }

    /**
     * Upload directories live at map-chunks/{campaign}/{user}/{upload_id}.
     */
    private function isUploadDirectory(string $directory): bool
    {
        return count(explode('/', $directory)) === 4;
    }
}
