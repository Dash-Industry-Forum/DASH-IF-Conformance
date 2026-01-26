<?php

namespace App\Services\Manifest;

use League\Uri\Uri;
use App\Services\MPDCache;
use Illuminate\Support\Facades\Log;

class Representation
{
    private readonly \DOMElement $dom;
    public readonly int $periodIndex;
    public readonly int $adaptationSetIndex;
    public readonly int $representationIndex;


    public function __construct(
        \DOMElement $dom,
        int $periodIndex,
        int $adaptationSetIndex,
        int $representationIndex
    ) {
        $this->dom = $dom;
        $this->periodIndex = $periodIndex;
        $this->adaptationSetIndex = $adaptationSetIndex;
        $this->representationIndex = $representationIndex;
    }

    public function path(): string
    {
        return "$this->periodIndex::$this->adaptationSetIndex::$this->representationIndex";
    }

    public function getAdaptationSet(): AdaptationSet
    {
        $mpdCache = app(MPDCache::class);
        return $mpdCache->getPeriod($this->periodIndex)->getAdaptationSet($this->adaptationSetIndex);
    }

    public function getBaseUrl(): string
    {
        $myBase = '';
        $baseUrls = dom_direct_children_by_tag_name($this->dom, 'BaseURL');
        if (count($baseUrls)) {
            $myBase = $baseUrls[0]->nodeValue;
        }
        return Uri::fromBaseUri(
            $myBase,
            $this->getAdaptationSet()->getBaseUrl()
        )->toString();
    }

    public function initializationUrl(): ?string
    {
        $base = $this->getBaseUrl();
        if (count($this->dom->getElementsByTagName('SegmentBase'))) {
            //Self-initializing
            return null;
        }

        ///\TODO Add segmentlist variant (based on example_G4)

        $segmentTemplate = $this->dom->getElementsByTagName("SegmentTemplate");
        if (!count($segmentTemplate)) {
            $segmentTemplate = app(MPDCache::class)->getAdaptationSet($this->periodIndex, $this->adaptationSetIndex)
                                   ->getDOMElements('SegmentTemplate');
        }
        if (count($segmentTemplate)) {
            $segmentTemplateUrl = Uri::fromBaseUri(
                $segmentTemplate->item(0)->getAttribute('initialization'),
                $base
            )->toString();

            $uriTemplate = str_replace(
                array('$RepresentationID$'),
                array('{RepresentationID}'),
                $segmentTemplateUrl
            );
            $url = Uri::fromTemplate($uriTemplate, [
                    'RepresentationID' => $this->getId(),
                ])->toString();

            return Uri::fromBaseUri($url, $base)->toString();
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function segmentUrls(): array
    {
        //TODO This function isn't anywhere near done yet
        $result = [];

        $base = $this->getBaseUrl();

        $segmentBase = $this->dom->getElementsByTagName('SegmentBase');
        if (count($segmentBase)) {
            $result[] = $base;
        }

        $segmentList = $this->dom->getElementsByTagName('SegmentList');
        if (count($segmentList)) {
            foreach ($segmentList->item(0)->getElementsByTagName('SegmentURL') as $segmentUrl) {
                $result[] = Uri::fromBaseUri(
                    $segmentUrl->getAttribute('media'),
                    $base
                )->toString();
            }
        }

        $segmentTemplate = $this->dom->getElementsByTagName("SegmentTemplate");
        if (!count($segmentTemplate)) {
            $segmentTemplate = $this->getAdaptationSet()->getDOMElements('SegmentTemplate');
        }
        if (count($segmentTemplate)) {
            $segmentTemplateUrl = Uri::fromBaseUri(
                $segmentTemplate->item(0)->getAttribute('media'),
                $base
            )->toString();
            //TODO: Fix identifiers properly
            $uriTemplate = str_replace(
                array('$Bandwidth$','$Number$','$Number%03d$','$RepresentationID$','$Time$'),
                array('{bandwidth}','{Number}','{Number3d}','{RepresentationID}','{Time}'),
                $segmentTemplateUrl
            );


            $segmentTimeline = $segmentTemplate->item(0)->getElementsByTagName('SegmentTimeline');
            if (count($segmentTimeline)) {
                return $this->timelineUrls($segmentTimeline->item(0), $uriTemplate);
            }


            $startNumber = $segmentTemplate->item(0)->getAttribute('startNumber');
            if (!$startNumber) {
                $startNumber = 1;
            }
            for ($i = 0; $i < 3; $i++) {
                $filledTemplate =
                    Uri::fromTemplate($uriTemplate, [
                    'Number' => ($startNumber + $i),
                    'Number3d' => sprintf('%03d', ($startNumber + $i)),
                    'RepresentationID' => $this->getId(),
                ])->toString();
                $result[] = $filledTemplate;
            }
        }

        if (count($result) == 0 && $base != '') {
            $result[] = $base;
        }


        return $result;
    }


    /**
     * @return array<string>
     */
    ///TODO Implement negative repeats
    private function timelineUrls(\DOMElement $timeline, string $template): array
    {
        $urls = [];
        $time = 0;
        $segmentElements = $timeline->getElementsByTagName('S');
        foreach ($segmentElements as $segmentElement) {
            if ($segmentElement->getAttribute('t') != '') {
                $time = intval($segmentElement->getAttribute('t'));
            }
            $repeats = 1;
            if ($segmentElement->getAttribute('r') != '') {
                $repeats = intval($segmentElement->getAttribute('r')) + 1;
            }
            for ($r = 0; $r < $repeats; $r++) {
                $urls[] = Uri::fromTemplate($template, [
                    'RepresentationID' => $this->getId(),
                    'Time' => $time
                ])->toString();
                $time += intval($segmentElement->getAttribute('d'));
                if (count($urls) > 3) {
                    break;
                }
            }
            if (count($urls) > 3) {
                break;
            }
        }
        return $urls;
    }

    public function getId(): string
    {
        return $this->dom->getAttribute('id');
    }

    public function getAttribute(string $attribute): string
    {
        return $this->dom->getAttribute($attribute);
    }

    public function getTransientAttribute(string $attribute): string
    {
        $myAttribute = $this->getAttribute($attribute);
        if ($myAttribute != '') {
            return $myAttribute;
        }
        return app(MPDCache::class)->getAdaptationSet($this->periodIndex, $this->adaptationSetIndex)
                                   ->getTransientAttribute($attribute);
    }

    public function hasProfile(string $profile): bool
    {
        $profileList = explode(',', $this->getTransientAttribute('profiles'));
        return in_array($profile, $profileList);
    }

    public function hasCodec(string $codec): bool
    {
        $codecList = explode(',', $this->getTransientAttribute('codecs'));
        return in_array($codec, $codecList);
    }


    /**
     * @return \DOMNodeList<\DOMElement>
     **/
    public function getDOMElements(string $tagName): ?\DOMNodeList
    {
        return $this->dom->getElementsByTagName($tagName);
    }

    /**
     * @return \DOMNodeList<\DOMElement>
     **/
    public function getTransientDOMElements(string $tagName): ?\DOMNodeList
    {
        $myElements = $this->getDOMElements($tagName);
        if ($myElements) {
            return $myElements;
        }
        return app(MPDCache::class)->getAdaptationSet($this->periodIndex, $this->adaptationSetIndex)
                                   ->getDOMElements($tagName);
    }
}
