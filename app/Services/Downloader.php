<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Jobs\DownloadSegment;

class Downloader
{
    private function downloadFile(string $url, string $targetPath, bool $force = false): bool
    {
        ///TODO Make sure we invalidate these paths with the MPD switch
        if (file_exists($targetPath) && !$force) {
            return true;
        }


        if (file_exists("${targetPath}.queued")) {
            return false;
        }

        touch("${targetPath}.queued");

        DownloadSegment::dispatch($url, $targetPath);

        return false;
    }

    /**
     * @return array<string, array<string>>
     **/
    public function downloadSegments(Representation $representation): array
    {


        $segments = [
        'init' => [],
        'segments' => []
        ];

        $representationDir = session_dir() . $representation->path() . "/";

        $mpdCache = app(MPDCache::class);

        if (!file_exists($representationDir)) {
            mkdir($representationDir, 0777, true);
        }


        $initUrl = $representation->initializationUrl();
        $initPath = '';
        if ($initUrl) {
            $initPath = "${representationDir}init.mp4";
            $this->downloadFile($initUrl, $initPath);
            $segments['init'][] = $initPath;
        }

        //TODO: Make sure we download with a representative segment extension
        //      This also relates to the function where we detect whether a
        //      segment has been downloaded
        foreach ($representation->segmentUrls() as $segmentIndex => $segmentUrl) {
            $segmentPath = "${representationDir}${segmentIndex}.mp4";
            $this->downloadFile($segmentUrl, $segmentPath);
            $segments['segments'][] = $segmentPath;
        }
        return $segments;
    }
}
