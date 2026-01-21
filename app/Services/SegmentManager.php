<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Downloader;
use App\Services\Segment;
use App\Services\MPDCache;
use App\Interfaces\Module;
use App\Services\Manifest\Representation;

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

    private function downloadedSegmentCount(): int
    {
        $directoryIterator = new \RecursiveDirectoryIterator(session_dir());
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);
        $regexIter = new \RegexIterator($recursiveIterator, '/^.+\.mp4$/i', \RecursiveRegexIterator::GET_MATCH);
        $files = [];
        foreach ($regexIter as $file) {
            $files[] = $file;
        }
        $queuedIter = new \RegexIterator($recursiveIterator, '/^.+\.queued$/i', \RecursiveRegexIterator::GET_MATCH);
        $queuedFiles = [];
        foreach ($queuedIter as $file) {
            $queuedFiles[] = $file;
        }
        if (count($queuedFiles) > count($files)){
            return 0;
        }
        return count($files) - count($queuedFiles);
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
            if ($seg->getSize() > 0){
              $segments[] = $seg;
            }
        }

        return $segments;
    }
}
