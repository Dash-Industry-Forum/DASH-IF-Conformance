<?php

namespace App\Services\Manifest;

use League\Uri\Uri;
use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;

class Period
{
    private readonly \DOMElement $dom;
    public readonly int $periodIndex;

    public function __construct(\DOMElement $dom, int $periodIndex)
    {
        $this->dom = $dom;
        $this->periodIndex = $periodIndex;
    }

    public function path(): string
    {
        return "$this->periodIndex";
    }

    public function asXML(): string
    {
        return $this->dom->ownerDocument->saveXML();
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
            app(MPDCache::class)->getBaseUrl()
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
        return app(MPDCache::class)->getAttribute($attribute);
    }


    public function getAdaptationSetCount(): int
    {
        return count($this->dom->getElementsByTagName('AdaptationSet'));
    }

    public function getAdaptationSet(int $adaptationSetIndex): ?AdaptationSet
    {
        $adaptationSets = $this->dom->getElementsByTagName('AdaptationSet');
        if ($adaptationSetIndex >= count($adaptationSets)) {
            return null;
        }
        return new AdaptationSet($adaptationSets->item($adaptationSetIndex), $this->periodIndex, $adaptationSetIndex);
    }

    /**
     * @return array<AdaptationSet>
     */
    public function allAdaptationSets(): array
    {
        $result = [];
        foreach ($this->dom->getElementsByTagName('AdaptationSet') as $adaptationSetIndex => $adaptationSet) {
            $result[] = new AdaptationSet($adaptationSet, $this->periodIndex, $adaptationSetIndex);
        }
        return $result;
    }

    public function getRepresentation(int $adaptationIndex, int $representationIndex): ?Representation
    {
        $adaptationSet = $this->getAdaptationSet($adaptationIndex);
        if (!$adaptationSet) {
            return null;
        }
        return $adaptationSet->getRepresentation($representationIndex);
    }

    /**
     * @return \DOMNodeList<\DOMElement>
     **/
    public function getDOMElements(string $tagName): ?\DOMNodeList
    {
        return $this->dom->getElementsByTagName($tagName);
    }

    public function hasProfile(string $profile): bool
    {
        $profileList = explode(',', $this->getTransientAttribute('profiles'));
        return in_array($profile, $profileList);
    }
}
