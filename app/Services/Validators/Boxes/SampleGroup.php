<?php

namespace App\Services\Validators\Boxes;

class SampleGroup
{
    public function __construct()
    {
        $this->sampleCounts = array();
        $this->groupDescriptionIndices = array();
    }
    /**
     * @var array<string> $sampleCounts
     **/
    public array $sampleCounts;
    /**
     * @var array<string> $groupDescriptionIndices
     **/
    public array $groupDescriptionIndices;
}
