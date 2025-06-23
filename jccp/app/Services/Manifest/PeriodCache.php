<?php

namespace App\Services\Manifest;

use Illuminate\Support\Facades\Cache;
use App\Services\MPDCache;

class PeriodCache
{
    public int $periodIndex = -1;
    private Period $period;

    public function __construct(int $periodIndex)
    {
        $this->periodIndex = $periodIndex;
        $this->period = app(MPDCache::class)->getPeriod($this->periodIndex);
    }


    public function getAttribute(string $attribute): string
    {
        return $this->period->getAttribute($attribute);
    }

    public function getTransientAttribute(string $attribute): string
    {
        $myAttribute = $this->getAttribute($attribute);
        if ($myAttribute != '') {
            return $myAttribute;
        }
        return app(MPDCache::class)->getAttribute($attribute);
    }
    public function getAdaptationSetCount(): int
    {
        return count($this->period->getAdaptationSets());
    }

    public function getAdaptationSet(int $adaptationSetIndex): AdaptationSet|null
    {
        return $this->period->getAdaptationSet($adaptationSetIndex);
    }
}
