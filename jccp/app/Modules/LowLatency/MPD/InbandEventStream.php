<?php

namespace App\Modules\LowLatency\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class InbandEventStream
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $presentCase;
    private TestCase $validValueCase;


    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "Low Latency",
            []
        ));

        $this->presentCase = $this->legacyReporter->add(
            section: '9.X.4.2',
            test: 'Inband Event streams carrying MPD validity expiration events SHOULD be present',
            skipReason: "",
        );
        $this->validValueCase = $this->legacyReporter->add(
            section: '9.X.4.2',
            test: 'If Inband Event streams carrying MPD validity expiration events are used, @value SHALL be set to 1',
            skipReason: "No Inband Event Stream found",
        );
    }

    //Public validation functions
    public function validateInbandEventStream(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                foreach($adaptationSet->allRepresentations() as $representation){
                    $this->validateForRepresentation($representation);
                }
            }
        }
    }
    public function validateForRepresentation(Representation $representation): void {
        $isPresent = false;


        $inBandStreams = $representation->getDOMElements('InbandEventStream');

        foreach ($inBandStreams as $streamIndex => $inBandStream){
            if ($inBandStream->getAttribute('schemeIdUri') != 'urn:mpeg:dash:event:2012'){
                continue;
            }
            $isPresent = true;

            $this->validValueCase->pathAdd(
                path: $representation->path() . "-Stream$streamIndex",
                result: $inBandStream->getAttribute('value') == "1",
                severity: "FAIL",
                pass_message: "Correct value found",
                fail_message: "Incorrect value found",
            );


        }

        $this->presentCase->pathAdd(
            path: $representation->path(),
            result: $isPresent,
            severity: "WARN",
            pass_message: "At least one stream found",
            fail_message: "No streams found",
        );
    }

    //Private helper functions
}
