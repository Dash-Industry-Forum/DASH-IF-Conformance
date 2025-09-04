<?php

namespace App\Services\Manifest;

use League\Uri\Uri;
use App\Services\MPDCache;
use App\Services\Manifest\Representation;

class AdaptationSet
{
    private readonly \DOMElement $dom;

    public readonly int $periodIndex;
    public readonly int $adaptationSetIndex;



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

    public function hasProfile(string $profile): bool
    {
        $profileList = explode(',', $this->getTransientAttribute('profiles'));
        return in_array($profile, $profileList);
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
        return app(MPDCache::class)->getPeriod($this->periodIndex)
                                   ->getDOMElements($tagName);
    }
}
