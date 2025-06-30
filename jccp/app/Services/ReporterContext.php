<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ReporterContext
{
    private string $element;
    private string $spec;
    private string $version;
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
}
