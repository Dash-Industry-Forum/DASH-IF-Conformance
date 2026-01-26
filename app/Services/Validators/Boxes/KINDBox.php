<?php

namespace App\Services\Validators\Boxes;

class KINDBox
{
    public function __construct()
    {
        $this->schemeURI = '';
        $this->value = '';
    }
    public string $schemeURI;
    public string $value;
}
