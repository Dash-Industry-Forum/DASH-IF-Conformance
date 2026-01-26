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

class Live
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
            test: "SegmentTemplate@media attribute SHALL be present",
            skipReason: "Corresponding profile(s) not signalled",
        );
    }

    //Public validation functions
    public function validateLive(): void
    {
        $mpdCache = app(MPDCache::class);

        if (
            !$mpdCache->hasProfile('http://dashif.org/guidelines/dash') ||
            !$mpdCache->hasProfile('urn:mpeg:dash:profile:iofff-live:2011')
        ) {
            return;
        }

        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach ($adaptationSet->allRepresentations() as $representation) {
                    if (!$representation->hasProfile('http://dashif.org/guidlines/dash-if-ondemand')) {
                        continue;
                    }
                    $segmentTemplate = $representation->getTransientDOMElements('SegmentTemplate');

                    $isValid = !empty($segmentTemplate) && $segmentTemplate[0] != null;
                    if ($isValid) {
                        $isValid = $segmentTemplate[0]->getAttribute('media') != '';
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
