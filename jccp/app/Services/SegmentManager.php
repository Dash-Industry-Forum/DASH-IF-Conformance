<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Downloader;
use App\Services\Segment;
use App\Interfaces\Module;

class SegmentManager
{
    public function __construct()
    {
    }

    public function getSegments(int $periodIndex, int $adaptationSetIndex, int $representationIndex): array
    {
        $representationDir = session_dir() . "${periodIndex}/${adaptationSetIndex}/${representationIndex}/";
        $downloader = app(Downloader::class);
        $segmentFiles = $downloader->downloadSegments($periodIndex, $adaptationSetIndex, $representationIndex);


        $segments = [];
        foreach ($segmentFiles['segments'] as $segmentIdx => $segment) {
            Log::info("Segment $segment");
            $seg = new Segment(
                init: count($segmentFiles['init']) ? $segmentFiles['init'][0] : '',
                segment: $segment,
                representationDir: $representationDir,
                segmentIndex: $segmentIdx
            );
            $seg->analyseGPAC();
            $segments[] = $seg;
        }

        return $segments;
    }
}
