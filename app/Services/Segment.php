<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Downloader;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Process;
use App\Services\Validators\MP4Box;
use App\Services\Validators\MP4BoxRepresentation;
use App\Services\Validators\Boxes;
use App\Services\Segment\BoxAccess;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;

class Segment
{
    private string $initPath = '';
    public readonly string $segmentPath;
    private string $representationDir = '';
    private int $segmentIndex;
    private string $codec = '';
    private string $profile = '';
    private string $level = '';

    /**
     * //TODO Change mixed with correct parent class
     * @var array<mixed> $analyzedRepresentations;
     **/
    private array $analyzedRepresentations = [];
    public function __construct(string $init, string $segment, string $representationDir, int $segmentIndex)
    {
        $span = Tracer::newSpan('New Segment')->start();
        $scope = $span->activate();

        $this->initPath = $init;
        $this->segmentPath = $segment;
        $this->representationDir = $representationDir;
        $this->segmentIndex = $segmentIndex;


        $gpacPath = $this->analyseGPAC();
        if ($gpacPath != '') {
            $this->analyzedRepresentations[] = new MP4BoxRepresentation($gpacPath);
        }

        $scope->detach();
        $span->end();
    }

    public function getSize(): int
    {
        $segmentSize = 0;
        try {
            $segmentSize = filesize($this->segmentPath);
        } catch (\Exception $e) {
        }
        return $segmentSize;
    }

    public function analyseGPAC(): string
    {

        if (file_exists($this->initPath)) {
            $this->fillInfoFromGPAC($this->initPath);
        } elseif (file_exists($this->segmentPath)) {
            $this->fillInfoFromGPAC($this->segmentPath);
        }



        $resultPath = $this->representationDir . $this->segmentIndex . "_dump.xml";
        if (file_exists($resultPath)) {
            return $resultPath;
        }


        if (!file_exists($this->initPath) && !file_exists($this->segmentPath)) {
            return '';
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
                             $resultPath);

        return $resultPath;
    }

    private function fillInfoFromGPAC(string $filePath): void
    {
        $mp4BoxInfo = app(MP4Box::class)->info($filePath);

        $lines = explode("\n", $mp4BoxInfo);

        foreach ($lines as $line) {
            if (strpos($line, 'RFC6381') !== false) {
                $codecOffset = strpos($line, ": ");
                if ($codecOffset === false) {
                    continue;
                }

                $this->codec = substr($line, $codecOffset + 2);
            }
            $profilePos = strpos($line, 'Profile ');
            if ($profilePos !== false) {
                $profile = substr($line, $profilePos + 8);
                $this->profile = substr($profile, 0, strpos($profile, ' '));
            }
            $levelPos = strpos($line, 'Level ');
            if ($levelPos !== false) {
                $level = substr($line, $levelPos + 6);
                $this->level = substr($level, 0, strpos($level, ' '));
            }
        }
    }

    public function getCodec(): string
    {
        return $this->codec;
    }
    public function getProfile(): string
    {
        return $this->profile;
    }
    public function getLevel(): string
    {
        return $this->level;
    }

    public function boxAccess(): BoxAccess
    {
        return new BoxAccess($this->analyzedRepresentations);
    }


    private function runAnalyzedFunction(string $funcName): mixed
    {
        foreach ($this->analyzedRepresentations as $analysis) {
            try {
                $result = call_user_func([$analysis, $funcName]);
                if ($result !== null) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::error("Caught exception: " . $e->getMessage());
            }
        }
        return null;
    }

    public function getTrackId(): ?int
    {
        return $this->runAnalyzedFunction('getTrackIdFromTKHD');
    }

    /**
     * @return ?array<float>
     **/
    public function getFragmentDurations(): ?array
    {
        return $this->runAnalyzedFunction('getFragmentDurations');
    }

    /**
     * @return array<?string>
     **/
    public function getSegmentSAPTypes(): array
    {
        return $this->runAnalyzedFunction('getSegmentSAP');
    }

    /**
     * @return array<float>
     **/
    public function getSegmentDurations(): array
    {
        return $this->runAnalyzedFunction('getSegmentDurations');
    }

    public function getProtectionScheme(): ?Boxes\SINFBox
    {
        return $this->runAnalyzedFunction('getProtectionScheme');
    }

    public function getSampleDescriptor(): ?string
    {
        return $this->runAnalyzedFunction('getSDType');
    }

    /**
     * @return array<string>
     **/
    public function getBrands(): array
    {
        return $this->runAnalyzedFunction('getBrands');
    }

    public function getTimeScale(): int
    {
        return $this->runAnalyzedFunction('getTimeScale');
    }

    public function getHandlerType(): ?string
    {
        return $this->runAnalyzedFunction('getHandlerType');
    }

    public function getSampleAuxiliaryInformation(): ?Boxes\SampleAuxiliaryInformation
    {
        return $this->runAnalyzedFunction('getSampleAuxiliaryInformation');
    }

    public function getBoxNameTree(): ?Boxes\NameOnlyNode
    {
        return $this->runAnalyzedFunction('getBoxNameTree');
    }

    public function getSampleDuration(): ?float
    {
        return $this->runAnalyzedFunction('getSampleDuration');
    }

    /**
     * @return array<string>
     **/
    public function getSIDXReferenceTypes(): array
    {
        return $this->runAnalyzedFunction('getSIDXReferenceTypes');
    }

    /**
     * @return array<string>
     **/
    public function getTopLevelBoxNames(): array
    {
        return $this->runAnalyzedFunction('getTopLevelBoxNames');
    }

    public function getSampleDescription(): ?Boxes\SampleDescription
    {
        return $this->runAnalyzedFunction('getSampleDescription');
    }


    /**
     * @return array<string,string>
     **/
    public function getHEVCConfiguration(): ?array
    {
        return $this->runAnalyzedFunction('getHEVCConfiguration');
    }

    /**
     * @return array<string,string>
     **/
    public function getAudioConfiguration(): ?array
    {
        return $this->runAnalyzedFunction('getAudioConfiguration');
    }

    /**
     * @return array<string,string>
     **/
    public function getAVCConfiguration(): ?array
    {
        return $this->runAnalyzedFunction('getAVCConfiguration');
    }
    /**
     * @return array<string,string>
     **/
    public function getSPSConfiguration(): ?array
    {
        return $this->runAnalyzedFunction('getSPSConfiguration');
    }
    /**
     * @return array<string,string>
     **/
    public function getAACConfiguration(): ?array
    {
        return $this->runAnalyzedFunction('getAACConfiguration');
    }


    public function AVCConfigurationHasSPSPPS(): bool
    {
        return $this->runAnalyzedFunction('AVCConfigurationHasSPSPPS');
    }


    public function getWidth(): ?int
    {
        return $this->runAnalyzedFunction('getWidth');
    }

    public function getHeight(): ?int
    {
        return $this->runAnalyzedFunction('getHeight');
    }

    public function getEPT(): ?int
    {
        return $this->runAnalyzedFunction('getEPT');
    }

    /**
     * @return array<Boxes\NALSample>
     **/
    public function getNalSamples(): array
    {
        return $this->runAnalyzedFunction('getNalSamples');
    }
}
