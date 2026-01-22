<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Downloader;
use App\Services\Segment;
use App\Services\MPDCache;
use App\Interfaces\Module;
use App\Services\Manifest\Representation;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;

//TODO : Make singleton
class SegmentManager
{
    /**
     * @var array<string,array<Segment>> $loadedSegments;
     **/
    private array $loadedSegments = [];

    public function __construct()
    {
    }

    /**
     * @return array<Segment>
     **/
    public function representationSegments(Representation $representation): array
    {
        if (!array_key_exists($representation->path(), $this->loadedSegments)) {
            $this->loadedSegments[$representation->path()] = $this->getSegments(
                $representation->periodIndex,
                $representation->adaptationSetIndex,
                $representation->representationIndex
            );
        }
        return $this->loadedSegments[$representation->path()];
    }

    public function queuedStatus(): int
    {
        return $this->segmentCount() - $this->downloadedSegmentCount();
    }

    /**
     * @return array<string, int>
     **/
    public function segmentState(): array
    {
        $res = [
            'downloaded' => 0,
            'queued' => 0,
            'failed' => 0,
        ];
        $disk = session_disk();
        foreach ($disk->allFiles() as $filePath) {
            if (str_ends_with($filePath, ".mp4")) {
                $res["downloaded"]++;
            }
            if (str_ends_with($filePath, ".failed")) {
                $res["failed"]++;
            }
            if (str_ends_with($filePath, ".queued")) {
                $res["queued"]++;
            }
        }
        return $res;
    }

    /**
     * @return array<string>
     **/
    public function failedSegments(): array
    {
        $res = [];

        $disk = session_disk();
        foreach ($disk->allFiles() as $filePath) {
            if (str_ends_with($filePath, ".failed")) {
                $segmentPortion = explode(".", $filePath)[0];
                $s = explode("/", $segmentPortion);

                $res[] = "$s[0]::$s[1]::$s[2]-$s[3]";
            }
        }
        return $res;
    }

    private function downloadedSegmentCount(): int
    {
        $state = $this->segmentState();
        if ($state['queued'] > $state['downloaded']) {
            return 0;
        }
        return $state['downloaded'] - $state['queued'];
    }

    public function segmentCount(): int
    {
        $segmentCount = 0;

        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $segmentCount += $representation->initializationUrl() ? 1 : 0;
                    $segmentCount += count($representation->segmentUrls());
                }
            }
        }

        return $segmentCount;
    }

    /**
     * @return array<Segment>
     **/
    public function getSegments(int $periodIndex, int $adaptationSetIndex, int $representationIndex): array
    {
        $representationDir = session_dir() . "${periodIndex}/${adaptationSetIndex}/${representationIndex}/";
        $downloader = app(Downloader::class);
        $segmentFiles = $downloader->downloadSegments($periodIndex, $adaptationSetIndex, $representationIndex);


        $segments = [];
        foreach ($segmentFiles['segments'] as $segmentIdx => $segment) {
            $seg = new Segment(
                init: count($segmentFiles['init']) ? $segmentFiles['init'][0] : '',
                segment: $segment,
                representationDir: $representationDir,
                segmentIndex: $segmentIdx
            );
            $path = "$periodIndex::$adaptationSetIndex::$representationIndex::$segmentIdx";
            if ($seg->getSize() > 0) {
                $segments[] = $seg;
            }
        }

        return $segments;
    }
}
