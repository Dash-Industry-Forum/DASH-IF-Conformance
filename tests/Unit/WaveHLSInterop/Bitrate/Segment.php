<?php

namespace Tests\Unit\WaveHLSInterop\Bitrate;

use Tests\TestCase;
use App\Modules\Wave\Segments\Bitrate;
use App\Services\ModuleReporter;
use App\Services\Manifest\Representation;

class Segment extends \App\Services\Segment
{
    private CaseEnum $case;
    public function __construct(CaseEnum $testCase)
    {
        $this->case = $testCase;
    }

    public function getSize(): int
    {
        switch ($this->case) {
            case CaseEnum::Valid1:
                return 1000;
            case CaseEnum::Valid2:
                return 2000;
            case CaseEnum::Invalid1:
                return 2000;
            case CaseEnum::Invalid2:
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
            case CaseEnum::Valid1:
                return [1];
            case CaseEnum::Valid2:
                return [2];
            case CaseEnum::Invalid1:
                return [1];
            case CaseEnum::Invalid2:
                return [0];
        }
        return [];
    }
}
