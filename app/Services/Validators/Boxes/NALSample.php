<?php

namespace App\Services\Validators\Boxes;

class NALSample
{
    public function __construct()
    {
        $this->units = [];
        $this->size = 0;
    }

    /**
     * @var array<NALUnit> $units
     **/
    public array $units;
    public int $size;
}
