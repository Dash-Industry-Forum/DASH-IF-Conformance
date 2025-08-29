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

    public function getBaseUrl(): string
    {
        $myBase = '';
        $baseUrls = $this->dom->getElementsByTagName('BaseURL');
        if (count($baseUrls)) {
            $myBase = $baseUrls->item(0)->nodeValue;
        }
        return Uri::fromBaseUri(
            $myBase,
            app(MPDCache::class)->getAdaptationSet(
                $this->periodIndex,
                $this->adaptationSetIndex
            )->getBaseUrl()
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
        if (count($segmentTemplate)) {
            $segmentTemplateUrl = Uri::fromBaseUri(
                $segmentTemplate->item(0)->getAttribute('initialization'),
                $base
            )->toString();
            return Uri::fromBaseUri($segmentTemplateUrl, $base)->toString();
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
        Log::info("Base: $base");

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
            $segmentTemplate = app(MPDCache::class)->getAdaptationSet($this->periodIndex, $this->adaptationSetIndex)
                                   ->getDOMElements('SegmentTemplate');
        }
        if (count($segmentTemplate)) {
            $segmentTemplateUrl = Uri::fromBaseUri(
                $segmentTemplate->item(0)->getAttribute('media'),
                $base
            )->toString();
            Log::info("Segment url: " . $segmentTemplateUrl);
            //TODO: Fix identifiers properly
            $uriTemplate = str_replace(
                array('$Bandwidth$','$Number$','$Number%03d$','$RepresentationID$','$Time$'),
                array('{bandwidth}','{Number}','{Number3d}','{RepresentationID}','{Time}'),
                $segmentTemplateUrl
            );
            $startNumber = $segmentTemplate->item(0)->getAttribute('startNumber');
            if (!$startNumber) {
                $startNumber = 1;
            }
            for ($i = 0; $i < 5; $i++) {
                $result[] = Uri::fromTemplate($uriTemplate, [
                    'Number' => ($startNumber + $i),
                    'Number3d' => sprintf('%03d', ($startNumber + $i)),
                    'RepresentationID' => $this->getId()
                ])->toString();
            }
        }


        return $result;
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


    /**
     * @return \DOMNodeList<\DOMElement>
     **/
    public function getDOMElements(string $tagName): ?\DOMNodeList
    {
        return $this->dom->getElementsByTagName($tagName);
    }
}
