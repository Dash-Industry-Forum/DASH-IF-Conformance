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

class SelfInitializingSidx
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $countCase;
    private TestCase $locationCase;
    private TestCase $referenceCase;
    private TestCase $timescaleCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "Low Latency",
            []
        ));

        //TODO: Extract to different spec and create dependency
        $this->countCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "Exactly one 'sidx' box shall be used",
            skipReason: "No self-initializing segment",
        );
        $this->locationCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box shall be placed before any 'moof' boxes",
            skipReason: "No self-initializing segment",
        );
        $this->referenceCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box reference_ID SHALL be equal to the track id",
            skipReason: "No self-initializing segment",
        );
        $this->timescaleCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box timescale SHALL be identical to the one in the 'mdhd' box",
            skipReason: "No self-initializing segment",
        );
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateSidx(Representation $representation, array $segments): void
    {
        if (count($segments) != 1) {
            return;
        }

        $this->validateSegment($representation, $segments[0]);
    }

    //Private helper functions
    private function validateSegment(Representation $representation, Segment $segment): void
    {
        $sidxBoxes = $segment->boxAccess()->sidx();

        $this->countCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($sidxBoxes) == 1,
            severity: "FAIL",
            pass_message: "Single 'sidx' box found",
            fail_message: "0 or multiple 'sidx' boxes found",
        );

        if (empty($sidxBoxes)) {
            return;
        }

        $this->validateSIDXLocation($representation, $segment);


        $this->referenceCase->pathAdd(
            path: $representation->path() . "-init",
            result: $sidxBoxes[0]->referenceId == $segment->getTrackId(),
            severity: "FAIL",
            pass_message: "Reference ID matched track ID",
            fail_message: "Reference ID does not match track ID",
        );

        $this->timescaleCase->pathAdd(
            path: $representation->path() . "-init",
            result: $sidxBoxes[0]->timescale == $segment->getTimescale(),
            severity: "FAIL",
            pass_message: "Reference ID matched track ID",
            fail_message: "Reference ID does not match track ID",
        );
    }

    private function validateSIDXLocation(Representation $representation, Segment $segment): void
    {

        $boxOrder = $segment->getTopLevelBoxNames();
        $sidxIndices = array_keys($boxOrder, 'sidx');
        $moofIndices = array_keys($boxOrder, 'moof');

        $this->locationCase->pathAdd(
            path: $representation->path() . "-init",
            result: !empty($sidxIndices) && !empty($moofIndices) && $sidxIndices[0] < $moofIndices[0],
            severity: "FAIL",
            pass_message: "'sidx' placed before first 'moof'",
            fail_message: "'sidx' placed after first 'moof', or missing 'moof'",
        );
    }
}
