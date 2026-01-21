<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

use Arifhp86\ClearExpiredCacheFile\Events\GarbageCollectionEnded;

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
        $allManifests = glob("/tmp/*/manifest.mpd");

        foreach($allManifests as $manifest){
            $split = explode("/", $manifest);
            $sessionId = $split[2];

            $cachedUrl = Cache::get("$sessionId::mpd::url", '');

            if ($cachedUrl == ''){
              Log::info("Manifest for " . $sessionId . " is expired");
              Process::run("rm -r /tmp/$sessionId");
            }
        }
    }
}
