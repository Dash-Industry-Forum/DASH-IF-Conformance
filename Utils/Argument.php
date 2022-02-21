<?php

namespace DASHIF;

class Argument
{
    public $label;
    public $short;
    public $long;
    public $desc;
    public function __construct($label, $short, $long, $desc)
    {
        $this->label = $label;
        $this->short = $short;
        $this->long = $long;
        $this->desc = $desc;
    }
}
