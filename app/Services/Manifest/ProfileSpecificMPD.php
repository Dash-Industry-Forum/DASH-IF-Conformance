<?php

namespace App\Services\Manifest;

use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use Illuminate\Support\Facades\Log;

class ProfileSpecificMPD
{
    /**
     * @var array<Period> $periods
    **/
    public array $periods = [];

    /**
     * @var array<AdaptationSet> $adaptationSets
    **/
    public array $adaptationSets = [];

    /**
     * @var array<Representation> $representations
    **/
    public array $representations = [];

    public function isValid(): bool
    {
        if (count($this->periods) == 0) {
            return false;
        }
        foreach ($this->periods as $period) {
            $validRepresentationFound = false;
            foreach ($this->representations as $representation) {
                if ($representation->periodIndex == $period->periodIndex) {
                    $validRepresentationFound = true;
                    break;
                }
            }


            if (!$validRepresentationFound) {
                return false;
            }
        }

        return true;
    }
}
