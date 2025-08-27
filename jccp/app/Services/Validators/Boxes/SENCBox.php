<?php

namespace App\Services\Validators\Boxes;

class SENCBox
{
    public function __construct()
    {
        $this->sampleCount = 0;
        $this->ivSizes = array();
    }
    public int $sampleCount;
    /**
     * @var array<int> $ivSizes;
     **/
    public array $ivSizes;
}
