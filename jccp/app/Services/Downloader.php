<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use App\Services\MPDCache;

class Downloader
{
    private function downloadFile(string $url, string $targetPath, bool $force = false): bool
    {
        ///TODO Make sure we invalidate these paths with the MPD switch
        if (file_exists($targetPath) && !$force) {
            return true;
        }
        $fp = fopen($targetPath, "w+");
        if (!$fp) {
            return false;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_FAILONERROR => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 500,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 0,
        CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
        CURLOPT_FILE => $fp
        ));

        curl_exec($curl);
        fclose($fp);

        return true;
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
