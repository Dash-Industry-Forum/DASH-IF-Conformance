<?php

namespace DASHIF\Boxes;

class ProtectionSystem
{
    public function __construct()
    {
        $this->systemId = "";
        $this->keys = array();
        $this->data = array();
    }
    public $systemId;
    public $keys;
    public $data;
}
