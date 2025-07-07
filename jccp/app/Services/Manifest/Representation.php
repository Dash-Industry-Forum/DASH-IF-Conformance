<?php

namespace App\Services\Manifest;

use League\Uri\Uri;
use App\Services\MPDCache;

class Representation
{
    private \DOMElement $dom;
    private int $periodIndex;
    private int $adaptationSetIndex;
    private int $representationIndex;


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
        $result = array(
        );

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
        if (count($segmentTemplate)) {
            $segmentTemplateUrl = Uri::fromBaseUri(
                $segmentTemplate->item(0)->getAttribute('media'),
                $base
            )->toString();
            $uriTemplate = str_replace(
                array('$Bandwidth$','$Number$','$RepresentationID$','$Time$'),
                array('{bandwidth}','{Number}','{RepresentationID}','{Time}'),
                $segmentTemplateUrl
            );
            $startNumber = $segmentTemplate->item(0)->getAttribute('startNumber');
            if (!$startNumber) {
                $startNumber = 1;
            }
            for ($i = 0; $i < 5; $i++) {
                $result[] = Uri::fromTemplate($uriTemplate, ['Number' => ($startNumber + $i)])->toString();
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
}
