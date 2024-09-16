<?php

namespace DASHIF\Boxes;

class TrackEncryption
{
    public function __construct()
    {
        $this->isEncrypted = false;
        $this->ivSize = 0;
        $this->iv = '';
        $this->kid = '';
        $this->cryptByteBlock = 0;
        $this->skipByteBlock = 0;
    }
    public $isEncrypted;
    public $ivSize;
    public $iv;
    public $kid;
    public $cryptByteBlock;
    public $skipByteBlock;
}
