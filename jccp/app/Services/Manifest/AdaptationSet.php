<?php

namespace App\Services\Manifest;

use League\Uri\Uri;
use App\Services\MPDCache;
use App\Services\Manifest\Representation;

class AdaptationSet
{
    private \DOMElement $dom;

    private int $periodIndex;
    private int $adaptationSetIndex;



    public function __construct(\DOMElement $dom, int $periodIndex, int $adaptationSetIndex)
    {
        $this->dom = $dom;
        $this->periodIndex = $periodIndex;
        $this->adaptationSetIndex = $adaptationSetIndex;
    }

    public function path(): string
    {
        return "$this->periodIndex::$this->adaptationSetIndex";
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
            app(MPDCache::class)->getPeriod($this->periodIndex)->getBaseUrl()
        )->toString();
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
        return app(MPDCache::class)->getPeriod($this->periodIndex)
                                   ->getTransientAttribute($attribute);
    }


    public function getRepresentation(int $representationIndex): ?Representation
    {
        $representations = $this->dom->getElementsByTagName('Representation');
        if ($representationIndex >= count($representations)) {
            return null;
        }
        return new Representation(
            $representations->item($representationIndex),
            $this->periodIndex,
            $this->adaptationSetIndex,
            $representationIndex
        );
    }

    /**
     * @return array<Representation>
     */
    public function allRepresentations(): array
    {
        $result = [];
        foreach ($this->dom->getElementsByTagName('Representation') as $representationIndex => $representation) {
            $result[] = new Representation(
                $representation,
                $this->periodIndex,
                $this->adaptationSetIndex,
                $representationIndex
            );
        }
        return $result;
    }
}
