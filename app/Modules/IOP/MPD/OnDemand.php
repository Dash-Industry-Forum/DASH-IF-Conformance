<?php

namespace App\Modules\IOP\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OnDemand
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $attributePresentCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DASH-IF IOP",
            "4.3",
            []
        ));

        $this->attributePresentCase = $this->legacyReporter->add(
            section: '3.10.3',
            test: "SegmentTemplate@indexRange attribute SHALL be present",
            skipReason: "Corresponding profile(s) not signalled",
        );
    }

    //Public validation functions
    public function validateOnDemand(): void
    {
        $mpdCache = app(MPDCache::class);

        if (!$mpdCache->hasProfile('http://dashif.org/guidelines/dash-if-ondemand')) {
            return;
        }

        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    $segmentTemplate = $representation->getTransientDOMElements('SegmentTemplate');

                    $isValid = !empty($segmentTemplate) && $segmentTemplate[0] != null;
                    if ($isValid) {
                        $isValid = $segmentTemplate[0]->getAttribute('indexRange') != '';
                    }

                    $this->attributePresentCase->pathAdd(
                        path: $representation->path(),
                        result: $isValid,
                        severity: "FAIL",
                        pass_message: "Attribute present",
                        fail_message: "Attribute not present",
                    );
                }
            }
        }
    }

    //Private helper functions
}
