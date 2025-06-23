<?php

namespace App\Services\Manifest;

use Illuminate\Support\Facades\Cache;
use App\Services\Manifest\AdaptationSet;

class Period
{
    private \DOMElement $dom;

    /**
     * @var array<AdaptationSet> $adaptationSets;
     */
    private array $adaptationSets;

    public function __construct(\DOMElement $dom)
    {
        $this->dom = $dom;
        $this->adaptationSets = array();
        foreach ($this->dom->getElementsByTagName('AdaptationSet') as $adaptationSet) {
            $this->adaptationSets[] = new AdaptationSet($adaptationSet);
        }
    }

    public function asXML(): string
    {
        return $this->dom->ownerDocument->saveXML();
    }


    public function getId(): string
    {
        return $this->dom->getAttribute('id');
    }

    public function getAttribute(string $attribute): string
    {
        return $this->dom->getAttribute($attribute);
    }

    /**
     * @param array<string> $parentProfiles;
     * @return array<string>
     **/
    public function getProfiles(array $parentProfiles): array
    {
        $profiles = $this->dom->getAttribute('profiles');
        if ($profiles != '') {
            return explode(',', $profiles);
        }
        return $parentProfiles;
    }

    /**
     * @return array<AdaptationSet>
     **/
    public function getAdaptationSets(): array
    {
        return $this->adaptationSets;
    }

    /**
     * @return array<string>
     */
    public function getAdaptationSetIds(): array
    {
        $result = array();
        foreach ($this->adaptationSets as $adaptationSet) {
            $result[] = $adaptationSet->getId();
        }
        return $result;
    }

    public function getAdaptationSet(int $idx = -1): AdaptationSet|null
    {
        ///\TODO Translate to singleton selector.
        $index = $idx == -1 ? 0 : $idx;
        if ($index >= count($this->adaptationSets)) {
            return null;
        }
        return $this->adaptationSets[$index];
    }
}
