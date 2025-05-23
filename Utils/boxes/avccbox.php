<?php

namespace DASHIF\Boxes;

class AVCCBox
{
    public function __construct()
    {
        $this->NALUnits = array(); // array of NALUnit objects
    }
    public $NALUnits;
}