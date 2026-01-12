<?php

namespace App\Services\Segment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Downloader;
use App\Services\Segment;
use App\Services\MPDCache;
use App\Interfaces\Module;
use App\Services\Manifest\Representation;
use App\Services\Validators\Boxes;

class BoxAccess
{
    /**
     * //TODO Change mixed with correct parent class
     * @var array<mixed> $analyzedRepresentations;
     **/
    private array $analyzedRepresentations = [];

    /**
     * //TODO Change mixed with correct parent class
     * @param array<mixed> $analyzedRepresentations
     **/
    public function __construct(array $analyzedRepresentations)
    {
        $this->analyzedRepresentations = $analyzedRepresentations;
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

    /**
     * @return array<Boxes\ELSTBox>
     **/
    public function elst(): array
    {
        return $this->runAnalyzedFunction('elstBoxes');
    }

    /**
     * @return array<Boxes\MOOFBox>
     **/
    public function moof(): array
    {
        return $this->runAnalyzedFunction('moofBoxes');
    }

    /**
     * @return array<Boxes\TRUNBox>
     **/
    public function trun(): array
    {
        return $this->runAnalyzedFunction('trunBoxes');
    }

    /**
     * @return array<Boxes\SIDXBox>
     **/
    public function sidx(): array
    {
        return $this->runAnalyzedFunction('sidxBoxes');
    }

    /**
     * @return array<Boxes\TFDTBox>
     **/
    public function tfdt(): array
    {
        return $this->runAnalyzedFunction('tfdtBoxes');
    }

    /**
     * @return array<Boxes\COLRBox>
     **/
    public function colr(): array
    {
        return $this->runAnalyzedFunction('colrBoxes');
    }

    /**
     * @return array<Boxes\PSSHBox>
     **/
    public function pssh(): array
    {
        return $this->runAnalyzedFunction('getPSSHBoxes');
    }

    /**
     * @return array<Boxes\SENCBox>
     **/
    public function senc(): array
    {
        return $this->runAnalyzedFunction('getSENCBoxes');
    }

    /**
     * @return array<Boxes\KINDBox>
     **/
    public function kind(): array
    {
        return $this->runAnalyzedFunction('getKindBoxes');
    }

    /**
     * @return array<Boxes\EventMessage>
     **/
    public function emsg(): array
    {
        return $this->runAnalyzedFunction('getEmsgBoxes');
    }

    /**
     * @return array<Boxes\SampleGroupDescription>
     **/
    public function seig(): array
    {
        return $this->runAnalyzedFunction('getSeigDescriptionGroups');
    }

    /**
     * @return array<Boxes\SampleGroup>
     **/
    public function sgbp(): array
    {
        return $this->runAnalyzedFunction('getSampleGroups');
    }

    /**
     * @return array<Boxes\AC4DSI>
     **/
    public function ac4DSI(): array
    {
        return $this->runAnalyzedFunction('getAC4DSI');
    }

    /**
     * @return array<Boxes\AC4TOC>
     **/
    public function ac4TOC(): array
    {
        return $this->runAnalyzedFunction('getAC4TOC');
    }
}
