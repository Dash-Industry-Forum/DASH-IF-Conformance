<?php

namespace App\Services\Validators\Boxes;

class SCHMBox
{
    public function __construct()
    {
        $this->schemeType = '';
        $this->schemeVersion = '';
    }
    public string $schemeType;
    public string $schemeVersion;
}
