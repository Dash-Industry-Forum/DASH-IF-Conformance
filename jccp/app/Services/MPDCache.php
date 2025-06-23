<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use App\Services\Manifest\Period;

class MPDCache
{
    private \DOMElement|null $dom = null;

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
        if (!$this->dom) {
            $this->dom = $this->parseDom();
        }
        return $this->dom;
    }

    public function getPeriod(int $periodIndex): Period|null
    {
        if ($periodIndex > $this->getPeriodCount()) {
            return null;
        }
        $dom = $this->getDom();
        return new Period(
            $dom->getElementsByTagName('Period')->item($periodIndex)
        );
    }


    public function getMPD(): string
    {
        return Cache::remember(cache_path(['mpd']), 3600, function () {
            return Tracer::newSpan("Retrieve mpd")->measure(function () {
                return file_get_contents(session()->get('mpd'));
            });
        });
    }

    public function getAttribute(string $attribute): string
    {
        $dom = $this->getDom();
        if (!$dom) {
            return '';
        }
        return $dom->getAttribute($attribute);
    }

    public function getPeriodCount(): int
    {
        $dom = $this->getDom();
        if (!$dom) {
            return 0;
        }
        return count($dom->getElementsByTagName('Period'));
    }
}
