<?php

namespace App\Services\Validators\Boxes;

class TRUNBox
{
    public function __construct()
    {
        $this->sampleCount = 0;
        $this->dataOffset = 0;
    }

    public int $sampleCount;
    public int $dataOffset;
}
