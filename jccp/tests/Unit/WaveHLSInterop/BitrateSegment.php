<?php

namespace Tests\Unit\WaveHLSInterop;

use Tests\TestCase;
use App\Modules\Wave\Segments\Bitrate;
use App\Services\ModuleReporter;
use App\Services\Segment;
use App\Services\Manifest\Representation;

class BitrateSegment extends Segment
{
    private BitrateEnum $case;
    public function __construct(BitrateEnum $testCase)
    {
        $this->case = $testCase;
    }

    public function getSize(): int
    {
        switch ($this->case) {
            case BitrateEnum::Valid1:
                return 1000;
            case BitrateEnum::Valid2:
                return 2000;
            case BitrateEnum::Invalid1:
                return 2000;
            case BitrateEnum::Invalid2:
                return 2000;
        }
        return 0;
    }

    /**
     * @return array<float>
     **/
    public function getSegmentDurations(): array
    {
        switch ($this->case) {
            case BitrateEnum::Valid1:
                return [1];
            case BitrateEnum::Valid2:
                return [2];
            case BitrateEnum::Invalid1:
                return [1];
            case BitrateEnum::Invalid2:
                return [0];
        }
        return [];
    }
}
