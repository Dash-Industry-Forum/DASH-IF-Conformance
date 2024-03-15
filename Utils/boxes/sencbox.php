<?php

namespace DASHIF\Boxes;

class SampleEncryption
{
    public function __construct()
    {
        $this->sampleCount = 0;
        $this->ivSizes = array();
        $this->clearBytes = array();
        $this->encryptedByte = array();
    }
    public $sampleCount;
    public $ivSizes;
    public $clearBytes;
    public $encryptedBytes;
}
