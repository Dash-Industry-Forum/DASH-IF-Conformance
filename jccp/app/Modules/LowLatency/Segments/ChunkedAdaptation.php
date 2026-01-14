<?php

namespace App\Modules\LowLatency\Segments;

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

class ChunkedAdaptation
{
    //Private subreporters
    private SubReporter $legacyReporter;

    //Depenedencies
    private TestCase $emsgCase;
    private TestCase $cmafCase;

    //Actual checks
    private TestCase $moofCase;
    private TestCase $resyncCase;
    private TestCase $availabilityOffsetCase;
    private TestCase $availabilityCompleteCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "Low Latency",
            []
        ));

        //TODO: Update to reflect the correct section etc after implementation
        $this->cmafCase = $this->legacyReporter->dependencyAdd(
            section: '9.X.4.5',
            test: "Each Segment SHALL Conform to a CMAF Fragment",
            dependentModule: "CMAF Segments Module",
            dependentSpec: "LEGACY - CMAF",
            dependentSection: "Section 7.3.2.3",
            skipReason: "No Chunked Adaptation Set Found",
        );
        $this->emsgCase = $this->legacyReporter->dependencyAdd(
            section: '9.X.4.5',
            test: "EMSG box constraints",
            dependentModule: "Wave HLS Interop Segments Module",
            dependentSpec: "CTA-5005-A - Final",
            dependentSection: "4.5.2 - Carriage of Timed Event Data",
            skipReason: "No Chunked Adaptation Set Found",
        );

        $this->moofCase = $this->legacyReporter->add(
            section: '9.X.4.5',
            test: "Each Segment MAY (and typically SHOULD) contain more than one CMAF chunk",
            skipReason: "No Chunked Adaptation Set Found",
        );
        //NOTE: This is a MPD check only
        $this->resyncCase = $this->legacyReporter->add(
            section: '9.X.4.5',
            test: "A Resync element SHOULD be assigned to each Representation",
            skipReason: "No Chunked Adaptation Set Found",
        );
        //NOTE: This is a MPD check only
        $this->availabilityOffsetCase = $this->legacyReporter->add(
            section: '9.X.4.5',
            test: '@availabilityTimeOffset SHALL be present for each represention',
            skipReason: "No Chunked Adaptation Set Found",
        );
        //NOTE: This is a MPD check only
        $this->availabilityCompleteCase = $this->legacyReporter->add(
            section: '9.X.4.5',
            test: "@availabilityTimeComple SHALL be present and set to 'FALSE' for each representation",
            skipReason: "No Chunked Adaptation Set Found",
        );
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateChunkedAdaptation(Representation $representation, array $segments): void
    {
        $this->cmafCase->pathAdd(
            path: $representation->path(),
            result: true,
            severity: "DEPENDENCY",
            pass_message: "Each segment SHALL conform to a CMAF Fragment",
            fail_message: ""
        );
        $this->emsgCase->pathAdd(
            path: $representation->path(),
            result: true,
            severity: "DEPENDENCY",
            pass_message: "Inherit 'emsg' checks",
            fail_message: ""
        );


        $this->validateResync($representation);
        $this->validateAvailabilityOffset($representation);


        foreach ($segments as $segmentIndex => $segment) {
            $this->validateSegment($representation, $segment, $segmentIndex);
        }
    }

    //Private helper functions
    private function validateAvailabilityOffset(Representation $representation): void
    {
        $availabilityOffset = $representation->getTransientAttribute('availabilityTimeOffset');
        $availabilityComplete = $representation->getTransientAttribute('availabilityTimeComplete');

        //TODO: Re-implement subchecks from this commit
        $this->availabilityOffsetCase->pathAdd(
            path: $representation->path(),
            result: $availabilityOffset != '',
            severity: "FAIL",
            pass_message: "@availabilityTimeOffset found",
            fail_message: "@availabilityTimeOffset not found",
        );

        $this->availabilityCompleteCase->pathAdd(
            path: $representation->path(),
            result: $availabilityComplete == 'FALSE',
            severity: "FAIL",
            pass_message: "Valid value found",
            fail_message: "Not set or invalid value",
        );
    }
    private function validateResync(Representation $representation): void
    {
        $resyncElements = $representation->getDOMElements('Resync');
        if (!count($resyncElements)) {
            $resyncElements = $representation->getAdaptationSet()->getDOMElements('Resync');
        }

        $this->resyncCase->pathAdd(
            path: $representation->path(),
            result: count($resyncElements) > 0,
            severity: "WARN",
            pass_message: "Resync element found",
            fail_message: "No Resync element found",
        );
    }
    private function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $topLevelBoxes = $segment->getTopLevelBoxNames();

        $boxCounts = array_count_values($topLevelBoxes);

        $moofCount = 0;
        if (array_key_exists('moof', $boxCounts)) {
            $moofCount = $boxCounts['moof'];
        }


        $this->moofCase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $moofCount > 0,
            severity: ($moofCount == 1 ? "INFO" : "FAIL"),
            pass_message: "$moofCount fragment(s) found",
            fail_message: "No fragments found"
        );
    }
}
