<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ContentProtectionChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $contentProtectionCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->contentProtectionCase = $this->v141reporter->add(
            section: "Section 8.3",
            test: "ContentProtection descriptor(s) shall be placed at the AdaptationSet level",
            skipReason: "No ContentProtection descriptor found"
        );
    }

    //Public validation functions
    public function validateContentProtection(): void
    {

        //TODO: Rebuild checks based on section 8.4, as they were invalid

        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                $this->extractValidProtection($adaptationSet);
            }
        }
    }

    private function extractValidProtection(AdaptationSet $adaptationSet): void
    {
        $protectionElements = $adaptationSet->getDOMElements('ContentProtection');
        foreach ($protectionElements as $elementIdx => $protectionElement) {
            $validLocation = $protectionElement->parentNode->nodeName == 'AdaptationSet';
            $this->contentProtectionCase->pathAdd(
                path: $adaptationSet->path() . "@$elementIdx",
                result: $validLocation,
                severity: "FAIL",
                pass_message: "Valid location",
                fail_message: "Invalid parent - " . $protectionElement->parentNode->nodeName,
            );
        }
    }
}
