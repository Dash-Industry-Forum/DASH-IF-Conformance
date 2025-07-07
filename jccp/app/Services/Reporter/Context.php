<?php

namespace App\Services\Reporter;

use Illuminate\Support\Facades\Log;

class Context
{
    public readonly string $element;
    public readonly string $spec;
    public readonly string $version;
    /**
        * @var array<string,string> $context
    **/
    private array $context = [];

    /**
        * @param array<string,string> $context
    **/
    public function __construct(string $element, string $spec, string $version, array $context)
    {
        $this->element = $element;
        $this->spec = $spec;
        $this->version = $version;
        $this->context = $context;
    }

    public function toString(): string
    {
        print_r($this->context);
        return "$this->element - $this->spec ($this->version)";
    }

    public function getElement(): string
    {
        return $this->element;
    }
}
