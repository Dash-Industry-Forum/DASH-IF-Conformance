<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Arifhp86\ClearExpiredCacheFile\Events\GarbageCollectionEnded;
use Illuminate\Support\Facades\Storage;

class GarbageCollection
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(GarbageCollectionEnded $event): void
    {
        $disk = Storage::build([
        'driver' => 'local',
        'root' => storage_path("sessions/"),
        ]);

        foreach ($disk->directories() as $sessionId) {
            if (Cache::get("$sessionId::mpd::url", '') == '') {
                $disk->deleteDirectory($sessionId);
            }
        }
    }
}
