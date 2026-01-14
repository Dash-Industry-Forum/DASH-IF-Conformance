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
use App\Services\Validators\Boxes\SIDXBox;
use App\Interfaces\Module;
use App\Interfaces\ModuleComponents\SegmentListComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SelfInitializingSidx extends SegmentListComponent
{
    private TestCase $countCase;
    private TestCase $locationCase;
    private TestCase $referenceCase;
    private TestCase $timescaleCase;
    private TestCase $eptCase;
    private TestCase $moofCountCase;

    private TestCase $referenceTypeCase;
    private TestCase $referenceStartSAPCase;
    private TestCase $referenceSAPTypeCase;
    private TestCase $referenceDeltaTimeCase;

    private TestCase $decodeTimeCase;
    private TestCase $brandCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "Low Latency",
                []
            )
        );

        //TODO: Extract to different spec and create dependency
        $this->countCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "Exactly one 'sidx' box shall be used",
            skipReason: "No self-initializing segment",
        );
        $this->locationCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box shall be placed before any 'moof' boxes",
            skipReason: "No self-initializing segment",
        );
        $this->referenceCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box reference_ID SHALL be equal to the track id",
            skipReason: "No self-initializing segment",
        );
        $this->timescaleCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box timescale SHALL be identical to the one in the 'mdhd' box",
            skipReason: "No self-initializing segment",
        );
        $this->eptCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box earliest presentation time SHALL match the segment EPT",
            skipReason: "No self-initializing segment",
        );
        $this->moofCountCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box reference count SHALL match the number of 'mmof' boxes",
            skipReason: "No self-initializing segment",
        );


        $this->referenceTypeCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box reference_type shall be set to 0",
            skipReason: "No self-initializing segment",
        );
        $this->referenceStartSAPCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box startwithsap shall be set to 1",
            skipReason: "No self-initializing segment",
        );
        $this->referenceSAPTypeCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box sapType shall be set to 1 or 2",
            skipReason: "No self-initializing segment",
        );
        $this->referenceDeltaTimeCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The 'sidx' box sapDeltaTime shall be set to 0",
            skipReason: "No self-initializing segment",
        );


        //Derived from old code, different wording
        $this->decodeTimeCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The baseMediaDecodeTime shall be 0",
            skipReason: "No self-initializing segment",
        );
        $this->brandCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "The list of compatible brands SHALL contain 'dash'",
            skipReason: "No self-initializing segment",
        );
    }

    //Public validation functions
    /**
     * @param array<Segment> $segments
     **/
    public function validateSegmentList(Representation $representation, array $segments): void
    {
        if (count($segments) != 1) {
            return;
        }

        $this->validateSingleSegment($representation, $segments[0]);
    }

    //Private helper functions
    private function validateSingleSegment(Representation $representation, Segment $segment): void
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


        $this->eptCase->pathAdd(
            path: $representation->path() . "-init",
            result: $sidxBoxes[0]->earliestPresentationTime == $segment->getEPT(),
            severity: "FAIL",
            pass_message: "Earliest presentation time matches",
            fail_message: "Earliest presentation time mismatched",
        );

        $this->moofCountCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($sidxBoxes[0]->references) == count($segment->boxAccess()->moof()),
            severity: "FAIL",
            pass_message: "Matched counts",
            fail_message: "Mismatched counts",
        );

        $this->validateReferences($representation, $segment, $sidxBoxes[0]);

        $tfdt = $segment->boxAccess()->tfdt();
        if (!empty($tfdt)) {
            $this->decodeTimeCase->pathAdd(
                path: $representation->path() . "-init",
                result: $tfdt[0]->decodeTime == 0,
                severity: "FAIL",
                pass_message: "Valid baseMediaDecodeTime",
                fail_message: "Invalid baseMediaDecodeTime",
            );
        }

        $brands = $segment->getBrands();
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: in_array('dash', $brands),
            severity: "FAIL",
            pass_message: "'dash' brand found",
            fail_message: "'dash' brand not found",
        );
    }

    private function validateReferences(Representation $representation, Segment $segment, SIDXBox $sidx): void
    {
        $validReferenceTypes = true;
        $validStartWithSAP = true;
        $validSAPType = true;
        $validDelta = true;
        foreach ($sidx->references as $reference) {
            if ($reference->referenceType != "0") {
                $validReferenceTypes = false;
            }
            if (!$reference->startsWithSAP) {
                $validStartWithSAP = false;
            }
            if ($reference->sapType != "1" && $reference->sapType != "2") {
                $validSAPType = false;
            }
            if ($reference->sapDeltaTime > 0) {
                $validDelta = false;
            }
        }

        $this->referenceTypeCase->pathAdd(
            path: $representation->path() . "-init",
            result: $validReferenceTypes,
            severity: "FAIL",
            pass_message: "Valid for all references",
            fail_message: "At least one invalid reference",
        );
        $this->referenceStartSAPCase->pathAdd(
            path: $representation->path() . "-init",
            result: $validStartWithSAP,
            severity: "FAIL",
            pass_message: "Valid for all references",
            fail_message: "At least one invalid reference",
        );
        $this->referenceSAPTypeCase->pathAdd(
            path: $representation->path() . "-init",
            result: $validSAPType,
            severity: "FAIL",
            pass_message: "Valid for all references",
            fail_message: "At least one invalid reference",
        );
        $this->referenceDeltaTimeCase->pathAdd(
            path: $representation->path() . "-init",
            result: $validDelta,
            severity: "FAIL",
            pass_message: "Valid for all references",
            fail_message: "At least one invalid reference",
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
