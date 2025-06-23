<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;

class MPDCache
{
    private \DOMElement|null $domCache = null;

    public function __construct()
    {
    }

    private function parseDom(): \DOMElement|null
    {
        return Tracer::newSpan("Parse mpd")->measure(function () {
            $mpd = $this->getMPD();
            $doc = new \DOMDocument();
            $doc->loadXML($mpd);


            $main_element_nodes = $doc->getElementsByTagName('MPD');
            if ($main_element_nodes->length == 0) {
                Log::error("No MPD in xml");
                return null;
            }


            return $main_element_nodes->item(0);
        });
    }

    private function getDom(): \DOMElement|null
    {
        if (!$this->domCache) {
            $this->domCache = $this->parseDom();
        }
        return $this->domCache;
    }

    public function getMPD(): string
    {
        return Cache::remember(cache_path(['mpd']), 3600, function () {
            return Tracer::newSpan("Retrieve mpd")->measure(function () {
                return file_get_contents(session()->get('mpd'));
            });
        });
    }

    public function getPeriodCount(): int
    {
        $dom = $this->getDom();
        if (!$dom) {
            return 0;
        }
        return count($dom->getElementsByTagName('Period'));
    }

    public function getPeriod(int $periodIndex): Period|null
    {
        if ($periodIndex > $this->getPeriodCount()) {
            return null;
        }
        return new Period(
            $this->getDom()->getElementsByTagName('Period')->item($periodIndex),
            $periodIndex
        );
    }

    /**
     * @return array<Period>
     */
    public function allPeriods(): array
    {
        $result = [];
        $dom = $this->getDom();
        if ($dom) {
            foreach ($dom->getElementsByTagName('Period') as $periodIndex => $period) {
                $result[] = new Period($period, $periodIndex);
            }
        }
        return $result;
    }

    public function getAdaptationSet(
        int $periodIndex,
        int $adaptationIndex
    ): AdaptationSet|null {
        $period = $this->getPeriod($periodIndex);
        if (!$period) {
            return null;
        }
        return $period->getAdaptationSet($adaptationIndex);
    }

    public function getRepresentation(
        int $periodIndex,
        int $adaptationIndex,
        int $representationIndex
    ): Representation|null {
        $period = $this->getPeriod($periodIndex);
        if (!$period) {
            return null;
        }
        return $period->getRepresentation($adaptationIndex, $representationIndex);
    }



    public function getAttribute(string $attribute): string
    {
        $dom = $this->getDom();
        if (!$dom) {
            return '';
        }
        return $dom->getAttribute($attribute);
    }
}
