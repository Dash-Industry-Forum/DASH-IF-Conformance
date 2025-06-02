<?php

namespace App\Services\Manifest;

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

    public function getId(): string
    {
        return $this->dom->getAttribute('id');
    }

    public function getAttribute(string $attribute): string
    {
        return $this->dom->getAttribute($attribute);
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
