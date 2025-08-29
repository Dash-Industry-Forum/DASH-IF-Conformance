<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Downloader;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Process;
use App\Services\Validators\MP4Box;
use App\Services\Validators\MP4BoxRepresentation;

class Segment
{
    private string $initPath = '';
    private string $segmentPath = '';
    private string $representationDir = '';
    private int $segmentIndex;

    /**
     * //TODO Change mixed with correct parent class
     * @var array<mixed> $analyzedRepresentations;
     **/
    private array $analyzedRepresentations;
    public function __construct(string $init, string $segment, string $representationDir, int $segmentIndex)
    {
        $this->initPath = $init;
        $this->segmentPath = $segment;
        $this->representationDir = $representationDir;
        $this->segmentIndex = $segmentIndex;


        $gpacPath = $this->analyseGPAC();
        if ($gpacPath != '') {
            $this->analyzedRepresentations[] = new MP4BoxRepresentation($gpacPath);
        }
    }

    public function analyseGPAC(): string
    {
        Log::info("Analyze with gpac: " . $this->initPath . " // " . $this->segmentPath);

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
