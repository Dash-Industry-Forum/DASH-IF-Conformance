<?php

namespace DASHIF\Boxes;

class HVCCBox
{
    public function __construct()
    {
        $this->NALUnits = array(); // array of NALUnit objects
    }
    public $NALUnits;
}