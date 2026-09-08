<?php

namespace App\Modules\CMAF\Segments;

use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\ModuleComponents\InitSegmentComponent;

class Initialization extends InitSegmentComponent
{
    private TestCase $avcCase;
    private TestCase $hevcCase;
    private TestCase $audioCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "Edition 3",
                "CMAF",
                []
            )
        );

        $this->avcCase = $this->reporter->add(
            section: 'Section 7.3.5',
            test: "Each AVC CMAF Fragment SHALL be independently accessible",
            skipReason: "No AVC track found"
        );
        $this->hevcCase = $this->reporter->add(
            section: 'Section 7.3.5',
            test: "Each HEVC CMAF Fragment SHALL be independently accessible",
            skipReason: "No HEVC track found"
        );
        $this->audioCase = $this->reporter->add(
            section: 'Section 7.3.5',
            test: "Each Audio CMAF Fragment SHALL be independently accessible",
            skipReason: "No Audio track found"
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {

        $sdType = $segment->getSampleDescriptor();

        if ($sdType == 'hvc1' || $sdType == 'hev1') {
            $this->validateHEVCInitialization($representation, $segment);
        }
        if ($sdType == 'avc1' || $sdType == 'avc3') {
            $this->validateAVCInitialization($representation, $segment);
        }

        if ($segment->getHandlerType() == "soun") {
            $this->validateAudioInitialization($representation, $segment);
        }
    }



    //Private helper functions

    private function validateAudioInitialization(Representation $representation, Segment $segment): void
    {
        $audioConfiguration = $segment->getAudioConfiguration();

        $this->audioCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($audioConfiguration) != 0,
            severity: "FAIL",
            pass_message: "Found Configuration",
            fail_message: "Unable to find Audio Configuration",
        );

        $this->audioCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segment->getSampleDescriptor() != '',
            severity: "FAIL",
            pass_message: "Found sdType",
            fail_message: "Unable to find sdType",
        );

        $this->audioCase->pathAdd(
            path: $representation->path() . "-init",
            result: $audioConfiguration['SampleRate'] != '',
            severity: "FAIL",
            pass_message: "Sample rate found",
            fail_message: "Sample rate not found",
        );

        $this->audioCase->pathAdd(
            path: $representation->path() . "-init",
            result: $audioConfiguration['Channels'] != '',
            severity: "FAIL",
            pass_message: "Channel count found",
            fail_message: "Channel count not found",
        );
    }

    private function validateAVCInitialization(Representation $representation, Segment $segment): void
    {
        $avcConfiguration = $segment->getAVCConfiguration();

        $this->avcCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($avcConfiguration) != 0,
            severity: "FAIL",
            pass_message: "Found Configuration",
            fail_message: "Unable to find AVCConfiguration",
        );

        $this->avcCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segment->getWidth() > 0,
            severity: "FAIL",
            pass_message: "Width found",
            fail_message: "Width not found",
        );
        $this->avcCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segment->getHeight() > 0,
            severity: "FAIL",
            pass_message: "Height found",
            fail_message: "Height not found",
        );

        if ($segment->getHandlerType() != "avc1") {
            return;
        }

        $this->avcCase->pathAdd(
            path: $representation->path() . "-init",
            result: $avcConfiguration['AVCProfileIndication'] != '',
            severity: "FAIL",
            pass_message: "AVC Profile found",
            fail_message: "AVC Profile not found",
        );
        $this->avcCase->pathAdd(
            path: $representation->path() . "-init",
            result: $avcConfiguration['AVCLevelIndication'] != '',
            severity: "FAIL",
            pass_message: "AVC Level found",
            fail_message: "AVC Level not found",
        );

        //TODO: Framerate
    }

    private function validateHEVCInitialization(Representation $representation, Segment $segment): void
    {
        $hevcConfiguration = $segment->getHEVCConfiguration();

        $this->hevcCase->pathAdd(
            path: $representation->path() . "-init",
            result: count($hevcConfiguration) != 0,
            severity: "FAIL",
            pass_message: "Found Configuration",
            fail_message: "Unable to find HEVCConfiguration",
        );
    }
}
