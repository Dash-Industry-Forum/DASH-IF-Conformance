<?php

namespace App\Services\Validators\Boxes;

class TFDTBox
{
    public function __construct()
    {
        $this->decodeTime = 0;
    }

    public int $decodeTime;
}
