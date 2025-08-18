<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));
    }

    //Public validation functions
    public function validateVideo(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                $this->validateFontProperties($adaptationSet);
            }
        }
    }

    //Private helper functions
    private function validateFontProperties(AdaptationSet $adaptationSet): void
    {
        $hasDownloadableFont = false;
        foreach ($adaptationSet->getDOMElements('SupplementalProperty') as $propertyElement) {
            if ($this->isFontProperty($propertyElement)) {
                $hasDownloadableFont = true;
            }
        }
        foreach ($adaptationSet->getDOMElements('EssentialProperty') as $propertyElement) {
            if ($this->isFontProperty($propertyElement)) {
                $hasDownloadableFont = true;
            }
        }
        $this->v141reporter->test(
            section: "Section 7.2.1.1",
            test: "A fontdownload descriptor SHALL only be placed in AdaptationSets containing subtitles",
            result: !$hasDownloadableFont,
            severity: "FAIL",
            pass_message: "No downloadable fonts found for AdaptationSet " . $adaptationSet->path(),
            fail_message: "At least one downloadable font found for AdaptationSet " . $adaptationSet->path(),
        );
    }

    private function isFontProperty(\DOMElement $propertyElement): bool
    {
        return $propertyElement->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
               $propertyElement->getAttribute('value') == '1';
    }
}
