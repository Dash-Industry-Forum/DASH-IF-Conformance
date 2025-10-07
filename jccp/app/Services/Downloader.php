<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use App\Services\MPDCache;
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
    public function downloadSegments(int $periodIndex, int $adaptationSetIndex, int $representationIndex): array
    {


        $segments = [
        'init' => [],
        'segments' => []
        ];

        $representationDir = session_dir() . "${periodIndex}/${adaptationSetIndex}/${representationIndex}/";

        $mpdCache = app(MPDCache::class);

        $representation = $mpdCache->getRepresentation($periodIndex, $adaptationSetIndex, $representationIndex);
        if (!$representation) {
            return $segments;
        }


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

        foreach ($representation->segmentUrls() as $segmentIndex => $segmentUrl) {
            Log::info("Found url $segmentUrl");
            $segmentPath = "${representationDir}${segmentIndex}.mp4";
            $this->downloadFile($segmentUrl, $segmentPath);
            $segments['segments'][] = $segmentPath;
        }
        return $segments;
    }
}
