<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\Representation;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BandwidthChecks
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
    public function validateBandwidth(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            $this->validateSinglePeriod($period);
        }
    }

    //Private helper functions
    private function validateSinglePeriod(Period $period): void
    {
        $representations = $this->collectRepresentations($period);

        if (count($representations['video']) == 0) {
            return;
        }

        foreach ($representations['video'] as $videoRepresentation) {
            $videoBandwidth = intval($videoRepresentation->getAttribute('bandwidth'));
            foreach ($representations['audio'] as $audioRepresentation) {
                $audioBandwidth = intval($audioRepresentation->getAttribute('bandwidth'));

                $combinedBandwidth = $videoBandwidth + $audioBandwidth;
                if (count($representations['subtitle']) == 0) {
                    $this->v141reporter->test(
                        section: "Section 11.3.0",
                        test: "If the service being delivered is a video service, then audio should be 20% " .
                              "or less of the total stream bandwidth",
                        result: $audioBandwidth <= 0.2 * $combinedBandwidth,
                        severity: "WARN",
                        pass_message: "Valid combination of video " . $videoRepresentation->path() .
                                      " and audio " . $audioRepresentation->path(),
                        fail_message: "Invalid combination of video " . $videoRepresentation->path() .
                                      " and audio " . $audioRepresentation->path(),
                    );
                } else {
                    foreach ($representations['subtitle'] as $subtitleRepresentation) {
                        $totalBandwidth = $combinedBandwidth + intval($subtitleRepresentation->getAttribute('bandwidth'));
                        $this->v141reporter->test(
                            section: "Section 11.3.0",
                            test: "If the service being delivered is a video service, then audio should be 20% " .
                                  "or less of the total stream bandwidth",
                            result: $audioBandwidth <= 0.2 * $totalBandwidth,
                            severity: "WARN",
                            pass_message: "Valid combination of video " . $videoRepresentation->path() .
                                      ", audio " . $audioRepresentation->path() .
                                      ", and subtitle " . $subtitleRepresentation->path(),
                            fail_message: "Invalid combination of video " . $videoRepresentation->path() .
                                      ",audio " . $audioRepresentation->path() .
                                      ", and subtitle " . $subtitleRepresentation->path(),
                        );
                    }
                }
            }
        }
    }

    /**
     * @return array<string, array<Representation>>
    **/
    private function collectRepresentations(Period $period): array
    {
        $representations = [
            'video' => [],
            'audio' => [],
            'subtitle' => []
        ];
        foreach ($period->allAdaptationSets() as $adaptationSet) {
            $contentType = $adaptationSet->getAttribute('contentType');
            if (!array_key_exists($contentType, $representations)) {
                continue;
            }
            foreach ($adaptationSet->allRepresentations() as $representation) {
                $representations[$contentType][] = $representation;
            }
        }
        return $representations;
    }
}
