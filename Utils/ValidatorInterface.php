<?php

namespace DASHIF;

class ValidatorInterface
{
    public $name;
    public $detected;

    public function __construct()
    {
        $this->name = "INTERFACE_UNINITIALIZED";
        $this->enabled = false;
    }

    public function enableFeature($featureName)
    {
    }

    public function run($period, $adaptation, $representation)
    {
    }
}
