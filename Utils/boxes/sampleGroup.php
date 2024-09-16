<?php

namespace DASHIF\Boxes;

class SampleGroup
{
    public function __construct()
    {
        $this->sampleCounts = array();
        $this->groupDescriptionIndices = array();
    }
    public $sampleCounts;
    public $groupDescriptionIndices;
}
