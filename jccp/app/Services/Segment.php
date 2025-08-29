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
    public readonly string $segmentPath;
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

        $resultPath = $this->representationDir . $this->segmentIndex . "_dump.xml";
        if (file_exists($resultPath)) {
            return $resultPath;
        }

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
                             $resultPath);

        return $resultPath;
    }


    public function runAnalyzedFunction(string $funcName): mixed
    {
        foreach ($this->analyzedRepresentations as $analysis) {
            try {
                $result = call_user_func([$analysis, $funcName]);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Exception $e) {
            }
        }
        return null;
    }
}
