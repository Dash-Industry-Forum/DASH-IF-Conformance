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
