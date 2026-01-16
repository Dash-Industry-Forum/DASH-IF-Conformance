<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use League\Uri\Uri;
use Keepsuit\LaravelOpenTelemetry\Facades\Tracer;
use App\Services\Manifest\ManifestType;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Manifest\ProfileSpecificMPD;

class MPDCache
{
    /**
     * @var array<string, \DOMELement> $domCache;
     **/
    private array $domCache = [];
    public string $error = '';


    public function __construct()
    {
    }

    private function parseDom(ManifestType $type): ?\DOMElement
    {
        return Tracer::newSpan("Parse mpd")->measure(function () use ($type) {
            $mpd = $this->getMPD($type);
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

    private function getDom(ManifestType $type): ?\DOMElement
    {
        if (!array_key_exists($type->name, $this->domCache)) {
            $this->domCache[$type->name] = $this->parseDom($type);
        }
        return $this->domCache[$type->name];
    }

    public function getMPD(ManifestType $type = ManifestType::Regular): string
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
        $res = Cache::remember(cache_path(['mpd','contents', $type->name]), 3600, function () use ($type) {
            return Tracer::newSpan("Retrieve mpd")->measure(function () use ($type) {
                $contents = '';
                try {
                    $contents = file_get_contents(session()->get('mpd'));
                } catch (\Exception $e) {
                }
                if ($contents === false) {
                    return '';
                }
                if ($type == ManifestType::Regular) {
                    Cache::put(cache_path(['mpd','url_retrieval']), time(), $seconds = 3600);
                }
                return $contents;
            });
        });
        if ($res == '') {
            $this->error = "Unable to retrieve MPD";
        }
        return $res;
    }

    public function getBaseUrl(ManifestType $type = ManifestType::Regular): string
    {
        $myBase = '';
        $dom = $this->getDom($type);
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

    public function getPeriodCount(ManifestType $type = ManifestType::Regular): int
    {
        $dom = $this->getDom($type);
        if (!$dom) {
            return 0;
        }
        return count($dom->getElementsByTagName('Period'));
    }

    public function getPeriod(int $periodIndex, ManifestType $type = ManifestType::Regular): ?Period
    {
        $dom = $this->getDom($type);
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
    public function allPeriods(ManifestType $type = ManifestType::Regular): array
    {
        $result = [];
        $dom = $this->getDom($type);
        if ($dom) {
            foreach ($dom->getElementsByTagName('Period') as $periodIndex => $period) {
                $result[] = new Period($period, $periodIndex);
            }
        }
        return $result;
    }

    public function getAdaptationSet(
        int $periodIndex,
        int $adaptationIndex,
        ManifestType $type = ManifestType::Regular
    ): ?AdaptationSet {
        $period = $this->getPeriod($periodIndex, $type);
        if (!$period) {
            return null;
        }
        return $period->getAdaptationSet($adaptationIndex);
    }

    public function getRepresentation(
        int $periodIndex,
        int $adaptationIndex,
        int $representationIndex,
        ManifestType $type = ManifestType::Regular
    ): ?Representation {
        $period = $this->getPeriod($periodIndex, $type);
        if (!$period) {
            return null;
        }
        return $period->getRepresentation($adaptationIndex, $representationIndex);
    }



    public function getAttribute(string $attribute, ManifestType $type = ManifestType::Regular): string
    {
        $dom = $this->getDom($type);
        if (!$dom) {
            return '';
        }
        return $dom->getAttribute($attribute);
    }

    /**
     * @return \DOMNodeList<\DOMElement>
     **/
    public function getDOMElements(string $tagName, ManifestType $type = ManifestType::Regular): ?\DOMNodeList
    {
        $dom = $this->getDom($type);
        if (!$dom) {
            return null;
        }
        return $dom->getElementsByTagName($tagName);
    }

    public function hasProfile(string $profile, ManifestType $type = ManifestType::Regular): bool
    {
        $profileList = explode(',', $this->getAttribute('profiles', $type));
        return in_array($profile, $profileList);
    }

    public function profileSpecificMPD(string $profile, ManifestType $type = ManifestType::Regular): ?ProfileSpecificMPD
    {
        if (!$this->hasProfile($profile, $type)) {
            return null;
        }
        $result = new ProfileSpecificMPD();

        foreach ($this->allPeriods($type) as $period) {
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
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string>
     **/
    public function getMediaTypes(ManifestType $type = ManifestType::Regular): array
    {
        $mediaTypes = [];

        foreach ($this->allPeriods($type) as $period) {
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
