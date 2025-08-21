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

class ContentProtectionChecks
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
    public function validateContentProtection(): void
    {

        //TODO: Rebuild checks based on section 8.4, as they were invalid

        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                $protectionElements = $this->extractValidProtection($adaptationSet);
                if (count($protectionElements) == 0) {
                    continue;
                }
            }
        }
    }

    /**
     * @return array<\DOMElement>
     **/
    private function extractValidProtection(AdaptationSet $adaptationSet): array
    {
        $res = [];
        $protectionElements = $adaptationSet->getDOMElements('ContentProtection');
        foreach ($protectionElements as $protectionElement) {
            $validLocation = $protectionElement->parentNode->nodeName == 'AdaptationSet';
            $this->v141reporter->test(
                section: "Section 8.3",
                test: "[..] the ContentProtection descriptor shall be placed at the AdaptationSet level",
                result: $validLocation,
                severity: "FAIL",
                pass_message: "Valid ContentProtection location found for AdaptationSet " . $adaptationSet->path(),
                fail_message: "Invalid ContentProtection location found for AdaptationSet " . $adaptationSet->path(),
            );
            if ($validLocation) {
                $res[] = $protectionElement;
            }
        }
        return $res;
    }
}
