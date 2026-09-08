<?php

namespace App\Services\Validators\Boxes;

class TRUNBox
{
    public function __construct()
    {
        $this->sampleCount = 0;
        $this->dataOffset = 0;
        $this->earliestCompositionTime = '';
        $this->sizes = [];
    }

    public int $sampleCount;
    public int $dataOffset;
    public string $earliestCompositionTime;
    /**
     * @var array<int> $sizes;
     **/
    public array $sizes;
}
