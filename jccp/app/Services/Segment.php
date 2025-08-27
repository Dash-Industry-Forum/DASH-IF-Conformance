<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Downloader;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Process;
use App\Services\Validators\MP4Box;

class Segment
{
    private string $initPath = '';
    private string $segmentPath = '';
    private string $representationDir = '';
    private int $segmentIndex;
    public function __construct(string $init, string $segment, string $representationDir, int $segmentIndex)
    {
        $this->initPath = $init;
        $this->segmentPath = $segment;
        $this->representationDir = $representationDir;
        $this->segmentIndex = $segmentIndex;
    }

    public function analyseGPAC(): string
    {
            Log::info("Analyze with gpac: " . $this->initPath . " // " . $this->segmentPath);
        if ($this->segmentPath == '') {
            return app(MP4Box::class)->run($this->initPath) ? $this->initPath : '';
        }
        if ($this->initPath == '') {
            return app(MP4Box::class)->run($this->segmentPath) ? $this->segmentPath : '';
        }

        //TODO Add error handling
        $concatPath = $this->representationDir . "seg" . $this->segmentIndex . ".mp4";
        Process::run("cat " . $this->initPath . " " . $this->segmentPath . " > ${concatPath}");
        $analyseResult = app(MP4Box::class)->run($concatPath);
        if (!$analyseResult) {
            return '';
        }
        Process::run("rm " . $concatPath);
        Process::run("mv " . $this->representationDir . "seg" . $this->segmentIndex . "_dump.xml " .
                             $this->representationDir . $this->segmentIndex . "_dump.xml");
        return $this->representationDir . $this->segmentIndex . "_dump.xml";
    }

    public function getSegments(int $periodIndex, int $adaptationSetIndex, int $representationIndex): void
    {
        $downloader = app(Downloader::class);
        $segments = $downloader->downloadSegments($periodIndex, $adaptationSetIndex, $representationIndex);
    }
}
