<?php

namespace Tests\Unit\WaveHLSInterop\AddressableMediaObject;

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

    /**
     * @return array<string>
     **/
    public function getTopLevelBoxNames(): array
    {
        switch ($this->case) {
            case CaseEnum::Valid:
                return ['sidx','moof'];
            case CaseEnum::NoSidx:
                return ['moof'];
            case CaseEnum::MultiSidx:
                return ['sidx','sidx','moof'];
            case CaseEnum::SidxPost:
                return ['moof','sidx'];
        }
        return [];
    }
}
