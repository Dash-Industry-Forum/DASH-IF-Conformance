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
}
