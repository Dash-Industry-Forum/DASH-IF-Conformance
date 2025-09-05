<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AddressableMediaObject
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.1.2 - Basic On-Demand and Live Streaming';

    private string $explanation = "CMAF Track Files [..] SHALL contain a single 'sidx' following the CMAF header " .
        "and preceding any CMAF fragments";


    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));
    }

    //Public validation functions
    public function validateAddressableMediaObject(Representation $representation, Segment $segment): void
    {
        $boxOrder = $segment->getTopLevelBoxNames();

        $sidxIndices = array_keys($boxOrder, 'sidx');
        $moofIndices = array_keys($boxOrder, 'moof');
        $sidxCount = count($sidxIndices);


        $this->waveReporter->test(
            section: $this->section,
            test: $this->explanation,
            result: $sidxCount == 1,
            severity: "FAIL",
            pass_message: $representation->path() . " - Segment contains 1 'sidx' box",
            fail_message: $representation->path() . " - Segment contains $sidxCount 'sidx' boxes",
        );

        if (count($moofIndices) > 0 && $sidxCount > 0) {
            $this->waveReporter->test(
                section: $this->section,
                test: $this->explanation,
                result: (int)$sidxIndices[0] < (int)$moofIndices[0],
                severity: "FAIL",
                pass_message: $representation->path() . " - 'sidx' precedes first fragment",
                fail_message: $representation->path() . " - 'sidx' doesn't precede first fragment"
            );
        }
    }

    //Private helper functions
}
