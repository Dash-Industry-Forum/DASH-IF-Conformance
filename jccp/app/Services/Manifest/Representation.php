<?php

namespace App\Services\Manifest;

class Representation
{
    private \DOMElement $dom;


    public function __construct(\DOMElement $dom)
    {
        $this->dom = $dom;
    }

    public function getId(): string
    {
        return $this->dom->getAttribute('id');
    }

    public function getAttribute(string $attribute): string
    {
        return $this->dom->getAttribute($attribute);
    }
}
