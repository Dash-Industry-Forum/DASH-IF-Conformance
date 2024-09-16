<?php

namespace DASHIF\Boxes;

class ProtectionScheme
{
    public $originalFormat;
    public $scheme;
    public $encryption;

    public function __construct()
    {
        $this->originalFormat = '';
        $this->scheme = new SCHMBox();
        $this->encryption = new TrackEncryption();
    }
}
