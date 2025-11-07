<?php

namespace App\Services\Validators\Boxes;

class MOOFBox
{
    public function __construct()
    {
        $this->boxSize = 0;
        $this->sequenceNumber = 0;
    }

    public int $boxSize;
    public int $sequenceNumber;
}
