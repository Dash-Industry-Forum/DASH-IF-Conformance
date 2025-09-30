<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Services\Reporter\TestCase;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AddressableMediaObject
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $sidxCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));

        $this->sidxCase = $this->waveReporter->add(
            section: '4.1.2 - Basic On-Demand and Live Streaming',
            test: "CMAF Track Files [..] SHALL contain a single 'sidx' following the CMAF header " .
            "and preceding any CMAF fragments",
            skipReason: ''
        );
    }

    //Public validation functions
    public function validateAddressableMediaObject(
        Representation $representation,
        Segment $segment,
        int $segmentIndex
    ): void {
        $boxOrder = $segment->getTopLevelBoxNames();

        $sidxIndices = array_keys($boxOrder, 'sidx');
        $moofIndices = array_keys($boxOrder, 'moof');
        $sidxCount = count($sidxIndices);


        $this->sidxCase->pathAdd(
            result: $sidxCount == 1,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "1 'sidx' box",
            fail_message: "$sidxCount 'sidx' boxes",
        );

        if (count($moofIndices) > 0 && $sidxCount > 0) {
            $this->sidxCase->pathAdd(
                result: (int)$sidxIndices[0] < (int)$moofIndices[0],
                severity: "FAIL",
                path: $representation->path() . "-$segmentIndex",
                pass_message: "'sidx' precedes first fragment",
                fail_message: "'sidx' doesn't precede first fragment"
            );
        }
    }

    //Private helper functions
}
