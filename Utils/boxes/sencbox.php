<?php

namespace DASHIF\Boxes;

class SampleEncryption
{
    public function __construct()
    {
        $this->sampleCount = 0;
        $this->ivSizes = array();
    }
    public $sampleCount;
    public $ivSizes;
}
