<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
    public function downloadSegments(int $periodIndex, int $adaptationSetIndex, int $representationIndex): void
    {
        $sessionDir = session_dir();

        $mpdCache = app(MPDCache::class);

        $representation = $mpdCache->getRepresentation($periodIndex, $adaptationSetIndex, $representationIndex);


        $prefix = "${periodIndex}_${adaptationSetIndex}_${representationIndex}";


        $initUrl = $representation->initializationUrl();
        if ($initUrl) {
            $this->downloadFile($initUrl, "${sessionDir}${prefix}_init.mp4");
        }

        foreach ($representation->segmentUrls() as $segmentIndex => $segmentUrl) {
            $this->downloadFile($segmentUrl, "${sessionDir}${prefix}_${segmentIndex}.mp4");
        }
    }
}
