<?php

namespace App\Services\Manifest;

use App\Services\Manifest\Representation;

class AdaptationSet
{
    private \DOMElement $dom;

    /**
     * @var array<AdaptationSet> $representations;
     */
    private array $representations;

    public function __construct(\DOMElement $dom)
    {
        $this->dom = $dom;
        $this->representations = array();
        foreach ($this->dom->getElementsByTagName('Representation') as $representation) {
            $this->representations[] = new Representation($representation);
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
    public function getRepresentationIds(): array
    {
        $result = array();
        foreach ($this->representations as $representation) {
            $result[] = $representation->getId();
        }
        return $result;
    }
}
