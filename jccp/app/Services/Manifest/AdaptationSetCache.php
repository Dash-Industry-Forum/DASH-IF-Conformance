<?php

namespace App\Services\Manifest;

use Illuminate\Support\Facades\Cache;
use App\Services\Manifest\PeriodCache;

class AdaptationSetCache
{
    public int $periodIndex = -1;
    public int $adaptationSetIndex = -1;
    private PeriodCache $periodCache;
    private AdaptationSet $adaptationSet;

    public function __construct(int $periodIndex, int $adaptationSetIndex)
    {
        $this->periodIndex = $periodIndex;
        $this->adaptationSetIndex = $adaptationSetIndex;

        $this->periodCache = new PeriodCache($periodIndex);
        $this->adaptationSet = $this->periodCache->getAdaptationSet($this->adaptationSetIndex);
    }


    public function getAttribute(string $attribute): string
    {
        return $this->adaptationSet->getAttribute($attribute);
    }

    public function getTransientAttribute(string $attribute): string
    {
        $myAttribute = $this->getAttribute($attribute);
        if ($myAttribute != '') {
            return $myAttribute;
        }
        return new PeriodCache($this->periodIndex)->getTransientAttribute($attribute);
    }
}
