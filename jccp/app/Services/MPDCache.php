<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use League\Uri\Uri;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Manifest\ProfileSpecificMPD;

class MPDCache
{
    private ?\DOMElement $domCache = null;
    public string $error = '';

    public function __construct()
    {
    }

    private function parseDom(): ?\DOMElement
    {
        return Tracer::newSpan("Parse mpd")->measure(function () {
            $mpd = $this->getMPD();
            if (!$mpd) {
                return null;
            }
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

    private function getDom(): ?\DOMElement
    {
        if (!$this->domCache) {
            $this->domCache = $this->parseDom();
        }
        return $this->domCache;
    }

    public function getMPD(): string
    {
        $cachedUrl = Cache::get(cache_path(['mpd', 'url']), '');
        if ($cachedUrl != session()->get('mpd')) {
            invalidate_mpd_cache();
        }
        if (!session()->get('mpd')) {
            return '';
        }
        Cache::remember(cache_path(['mpd','url']), 3600, function () {
            return session()->get('mpd');
        });
        $res = Cache::remember(cache_path(['mpd','contents']), 3600, function () {
            return Tracer::newSpan("Retrieve mpd")->measure(function () {
                $contents = '';
                try {
                    $contents = file_get_contents(session()->get('mpd'));
                } catch (\Exception $e) {
                }
                if ($contents === false) {
                    return '';
                }
                return $contents;
            });
        });
        if ($res == '') {
            $this->error = "Unable to retrieve MPD";
        }
        return $res;
    }

    public function getBaseUrl(): string
    {
        $myBase = '';
        $dom = $this->getDom();
        if (!$dom) {
            return '';
        }
        $baseUrls = $dom->getElementsByTagName('BaseURL');
        if (count($baseUrls)) {
            $myBase = $baseUrls->item(0)->nodeValue;
        }
        $urlPath = Cache::get(cache_path(['mpd','url']), '');
        if (!$urlPath) {
            return $myBase;
        }
        if ($urlPath[0] == '/') {
            $urlPath = "file://$urlPath";
        }
        return Uri::fromBaseUri($myBase, $urlPath)->toString();
    }

    public function getPeriodCount(): int
    {
        $dom = $this->getDom();
        if (!$dom) {
            return 0;
        }
        return count($dom->getElementsByTagName('Period'));
    }

    public function getPeriod(int $periodIndex): ?Period
    {
        $dom = $this->getDom();
        if (!$dom || $periodIndex > $this->getPeriodCount()) {
            return null;
        }
        return new Period(
            $dom->getElementsByTagName('Period')->item($periodIndex),
            $periodIndex
        );
    }

    /**
     * @return array<int,Period>
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
    ): ?AdaptationSet {
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
    ): ?Representation {
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

    /**
     * @return \DOMNodeList<\DOMElement>
     **/
    public function getDOMElements(string $tagName): ?\DOMNodeList
    {
        $dom = $this->getDom();
        if (!$dom) {
            return null;
        }
        return $dom->getElementsByTagName($tagName);
    }

    public function hasProfile(string $profile): bool
    {
        $profileList = explode(',', $this->getAttribute('profiles'));
        return in_array($profile, $profileList);
    }

    public function profileSpecificMPD(string $profile): ?ProfileSpecificMPD
    {
        if (!$this->hasProfile($profile)) {
            return null;
        }
        $result = new ProfileSpecificMPD();

        foreach ($this->allPeriods() as $period) {
            $result->periods[] = $period;

            foreach ($period->allAdaptationSets() as $adaptationSet) {
                if (!$adaptationSet->hasProfile($profile)) {
                    continue;
                }
                $result->adaptationSets[] = $adaptationSet;
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    if (!$representation->hasProfile($profile)) {
                        continue;
                    }
                    $result->representations[] = $representation;
                    Log::info("Added representation " . $representation->path() . " for profile $profile");
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string>
     **/
    public function getMediaTypes(): array
    {
        $mediaTypes = [];

        foreach ($this->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $contentType = $representation->getTransientAttribute('contentType');
                    $mimeType = $representation->getTransientAttribute('mimeType');
                    if ($contentType == 'video' || strpos($mimeType, 'video') !== false) {
                        $mediaTypes[] = 'video';
                    }
                    if ($contentType == 'audio' || strpos($mimeType, 'audio') !== false) {
                        $mediaTypes[] = 'audio';
                    }
                    if ($contentType == 'text' || strpos($mimeType, 'application') !== false) {
                        $mediaTypes[] = 'subtitle';
                    }
                }
            }
        }

        return array_unique($mediaTypes);
    }
}
