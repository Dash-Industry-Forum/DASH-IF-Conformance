<?php

namespace App\Services\Validators\Boxes;

class NALSample
{
    public function __construct()
    {
        $this->units = [];
    }

    /**
     * @var array<NALUnit> $units
     **/
    public array $units;
}
