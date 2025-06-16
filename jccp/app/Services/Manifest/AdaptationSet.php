<?php

namespace App\Services\Manifest;

use App\Services\Manifest\Representation;

class AdaptationSet
{
    private \DOMElement $dom;

    /**
     * @var array<Representation> $representations;
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
     * @return array<Representation>
     **/
    public function getRepresentations(): array
    {
        return $this->representations;
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
