<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use App\Interfaces\ModuleComponents\InitSegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BitStream extends InitSegmentComponent
{
    private TestCase $hevcSignallingCase;
    private TestCase $hevcColourPrimariesCase;
    private TestCase $hevcMatrixCoefficientsCase;
    private TestCase $hevcTransferCharacteriticsCase;
    private TestCase $hevcHLG10Case;
    private TestCase $hevcPQ10Case;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "CrossValidation",
                "DVB",
                "v1.4.1",
                []
            )
        );

        $this->hevcSignallingCase = $this->reporter->add(
            section: '5.2.2',
            test: "The value of the @codecs attribute shall be set in accordance with ISO/IEC 14496-15",
            skipReason: "No HEVC stream found"
        );

        $this->hevcColourPrimariesCase = $this->reporter->add(
            section: '5.2.2',
            test: "The value of the signalled ColourPrimaries Property in the MPD SHALL match " .
                  "colour_primaries in the VUI'",
            skipReason: "No HEVC stream found, or no ColourPrimaries property found"
        );
        $this->hevcMatrixCoefficientsCase = $this->reporter->add(
            section: '5.2.2',
            test: "The value of the signalled MatrixCoefficients Property in the MPD SHALL match " .
                  "matrix_coefficients in the VUI'",
            skipReason: "No HEVC stream found, or no MatrixCoefficients property found"
        );
        $this->hevcTransferCharacteriticsCase = $this->reporter->add(
            section: '5.2.2',
            test: "The value of the signalled TransferCharacteristics Property in the MPD SHALL match " .
                  "transfer_characteristics in the VUI'",
            skipReason: "No HEVC stream found, or no TransferCharacteristics property found"
        );
        $this->hevcHLG10Case = $this->reporter->add(
            section: '5.2.6',
            test: "Use of HLG10 within in an AdapationSet shall be signalled according to this section",
            skipReason: "No HEVC stream found"
        );
        $this->hevcPQ10Case = $this->reporter->add(
            section: '5.2.7',
            test: "Use of PQ10 within in an AdapationSet shall be signalled according to this section",
            skipReason: "No HEVC stream found"
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
        $this->validateSignalling($representation, $segment);
    }

    //Private helper functions
    private function validateSignalling(Representation $representation, Segment $segment): void
    {
        $sdType = $segment->getSampleDescriptor();
        if ($sdType === null) {
            return;
        }

        if ($sdType == 'hev1' || $sdType == 'hvc1') {
            $this->validateHEVCSignalling($representation, $segment);
            $this->validateColourProperties($representation, $segment);
            $this->checkHLG10($representation, $segment);
            $this->checkPQ10($representation, $segment);
        }
    }

    private function checkHLG10(Representation $representation, Segment $segment): void
    {
        $essentialProperties = $representation->getAdaptationSet()->getDOMElements('EssentialProperty');
        $supplementalProperties = $representation->getAdaptationSet()->getDOMElements('SupplementalProperty');

        $colourPrimariesScheme = 'urn:mpeg:mpegB:cicp:ColourPrimaries';
        $matrixCoefficientsScheme = 'urn:mpeg:mpegB:cicp:MatrixCoefficients';
        $transferCharacteristicsScheme = 'urn:mpeg:mpegB:cicp:TransferCharacteristics';

        $isHLG10 = $this->hevcHLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $representation->getAdaptationSet()->hasProfile('urn:dvb:dash:profile:dvb-dash:2017'),
            severity: "INFO",
            pass_message: "dvb-dash 2017 profile found",
            fail_message: "dvb-dash 2017 profile not found",
        );


        $validEssentialColour = false;
        $validEssentialMatrix = false;
        $validEssentialTransfer = false;
        $validSupplementalTransfer = false;

        foreach ($essentialProperties as $property) {
            if ($property->getAttribute('schemeIdUri') == $colourPrimariesScheme) {
                $validEssentialColour = $property->getAttribute('value') == '9';
            }
            if ($property->getAttribute('schemeIdUri') == $matrixCoefficientsScheme) {
                $validEssentialMatrix = $property->getAttribute('value') == '9';
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $validEssentialTransfer = $property->getAttribute('value') == '14';
            }
        }
        foreach ($supplementalProperties as $property) {
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $validSupplementalTransfer = $property->getAttribute('value') == '18';
            }
        }

        $isHLG10 = $this->hevcHLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialColour,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for ColourPrimaries",
            fail_message: "Missing or incorrect essentialProperties value for ColourPrimaries",
        ) && $isHLG10;
        $isHLG10 = $this->hevcHLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialMatrix,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for MatrixCoefficients",
            fail_message: "Missing or incorrect essentialProperties value for MatrixCoefficients",
        ) && $isHLG10;
        $isHLG10 = $this->hevcHLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialTransfer,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for TransferCharacteristics",
            fail_message: "Missing or incorrect essentialProperties value for TransferCharacteristics",
        ) && $isHLG10;
        $isHLG10 = $this->hevcHLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validSupplementalTransfer,
            severity: "INFO",
            pass_message: "Correct supplementalProperties value for TransferCharacteristics",
            fail_message: "Missing or incorrect supplementalProperties value for TransferCharacteristics",
        ) && $isHLG10;
        $this->hevcHLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $isHLG10,
            severity: $isHLG10 ? "FAIL" : "INFO",
            pass_message: "AdaptationSet marked as using HLG10",
            fail_message: "AdaptationSet not marked as using HLG10",
        );
    }
    private function checkPQ10(Representation $representation, Segment $segment): void
    {
        $essentialProperties = $representation->getAdaptationSet()->getDOMElements('EssentialProperty');

        $colourPrimariesScheme = 'urn:mpeg:mpegB:cicp:ColourPrimaries';
        $matrixCoefficientsScheme = 'urn:mpeg:mpegB:cicp:MatrixCoefficients';
        $transferCharacteristicsScheme = 'urn:mpeg:mpegB:cicp:TransferCharacteristics';

        $isPQ10 = $this->hevcPQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $representation->getAdaptationSet()->hasProfile('urn:dvb:dash:profile:dvb-dash:2017'),
            severity: "INFO",
            pass_message: "dvb-dash 2017 profile found",
            fail_message: "dvb-dash 2017 profile not found",
        );


        $validEssentialColour = false;
        $validEssentialMatrix = false;
        $validEssentialTransfer = false;

        foreach ($essentialProperties as $property) {
            if ($property->getAttribute('schemeIdUri') == $colourPrimariesScheme) {
                $validEssentialColour = $property->getAttribute('value') == '9';
            }
            if ($property->getAttribute('schemeIdUri') == $matrixCoefficientsScheme) {
                $validEssentialMatrix = $property->getAttribute('value') == '9';
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $validEssentialTransfer = $property->getAttribute('value') == '16';
            }
        }

        $isPQ10 = $this->hevcPQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialColour,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for ColourPrimaries",
            fail_message: "Missing or incorrect essentialProperties value for ColourPrimaries",
        ) && $isPQ10;
        $isPQ10 = $this->hevcPQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialMatrix,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for MatrixCoefficients",
            fail_message: "Missing or incorrect essentialProperties value for MatrixCoefficients",
        ) && $isPQ10;
        $isPQ10 = $this->hevcPQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialTransfer,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for TransferCharacteristics",
            fail_message: "Missing or incorrect essentialProperties value for TransferCharacteristics",
        ) && $isPQ10;
        $this->hevcPQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $isPQ10,
            severity: $isPQ10 ? "FAIL" : "INFO",
            pass_message: "AdaptationSet marked as using PQ10",
            fail_message: "AdaptationSet not marked as using PQ10",
        );
    }

    private function validateColourProperties(Representation $representation, Segment $segment): void
    {
        $essentialProperties = $representation->getAdaptationSet()->getDOMElements('EssentialProperty');
        $supplementalProperties = $representation->getAdaptationSet()->getDOMElements('SupplementalProperty');

        $colrBoxes = $segment->boxAccess()->colr();

        $colourPrimariesScheme = 'urn:mpeg:mpegB:cicp:ColourPrimaries';
        $matrixCoefficientsScheme = 'urn:mpeg:mpegB:cicp:MatrixCoefficients';
        $transferCharacteristicsScheme = 'urn:mpeg:mpegB:cicp:TransferCharacteristics';

        foreach ($essentialProperties as $property) {
            if ($property->getAttribute('schemeIdUri') == $colourPrimariesScheme) {
                $this->hevcColourPrimariesCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->colourPrimaries,
                    severity: "FAIL",
                    pass_message: "Signalled essential value matches VUI",
                    fail_message: "Signalled essential value does not match VUI"
                );
            }
            if ($property->getAttribute('schemeIdUri') == $matrixCoefficientsScheme) {
                $this->hevcMatrixCoefficientsCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->matrixCoefficients,
                    severity: "FAIL",
                    pass_message: "Signalled essential value matches VUI",
                    fail_message: "Signalled essential value does not match VUI"
                );
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $this->hevcTransferCharacteriticsCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->transferCharacteristics,
                    severity: "FAIL",
                    pass_message: "Signalled essential value matches VUI",
                    fail_message: "Signalled essential value does not match VUI"
                );
            }
        }

        foreach ($supplementalProperties as $property) {
            if ($property->getAttribute('schemeIdUri') == $colourPrimariesScheme) {
                $this->hevcColourPrimariesCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->colourPrimaries,
                    severity: "INFO",
                    pass_message: "Signalled supplemental value matches VUI",
                    fail_message: "Signalled supplemental value does not match VUI"
                );
                $this->hevcColourPrimariesCase->add(
                    result: false,
                    severity: "WARN",
                    pass_message: "",
                    fail_message: "Use as supplemental property 'not defined' in spec",
                );
            }
            if ($property->getAttribute('schemeIdUri') == $matrixCoefficientsScheme) {
                $this->hevcMatrixCoefficientsCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->matrixCoefficients,
                    severity: "INFO",
                    pass_message: "Signalled supplemental value matches VUI",
                    fail_message: "Signalled supplemental value does not match VUI"
                );
                $this->hevcMatrixCoefficientsCase->add(
                    result: false,
                    severity: "WARN",
                    pass_message: "",
                    fail_message: "Use as supplemental property 'not defined' in spec",
                );
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $this->hevcTransferCharacteriticsCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->transferCharacteristics,
                    severity: "INFO",
                    pass_message: "Signalled supplemental value matches VUI",
                    fail_message: "Signalled supplemental value does not match VUI"
                );
            }
        }
    }

    private function validateHEVCSignalling(Representation $representation, Segment $segment): void
    {
        $mpdCodec = $representation->getTransientAttribute('codecs');
        $segmentCodec = $this->computeHEVCSignalling($segment);

        //NOTE: This does not check for 'general_constraint_indicator_flags'
        //      as these are at time of writing not emitted in a usable format by MP4Box
        if (
            $this->hevcSignallingCase->pathAdd(
                path: $representation->path() . "-init",
                result: str_starts_with($mpdCodec, $segmentCodec),
                severity: "FAIL",
                pass_message: "Signalling in MPD corresponds to init segment",
                fail_message: "Signalling in MPD does not correspond to init segment",
            )
        ) {
            $this->hevcSignallingCase->add(
                result: true,
                severity: "INFO",
                pass_message: "Check does not include 'general_constraint_indicator_flags'",
                fail_message: "",
            );
        } else {
            $this->hevcSignallingCase->pathAdd(
                path: $representation->path() . "-init",
                result: true,
                severity: "INFO",
                pass_message: "MPD: $mpdCodec, Segment: $segmentCodec",
                fail_message: "",
            );
        }
    }

    private function computeHEVCSignalling(Segment $segment): string
    {

        $hevcDecoderConfiguration = $segment->getHEVCConfiguration();

        $codecString = $segment->getSampleDescriptor() . ".";


        //PROFILE_SPACE
        switch ($hevcDecoderConfiguration['profile_space']) {
            case '1':
                $codecString .= "A";
                break;
            case '2':
                $codecString .= "B";
                break;
            case '3':
                $codecString .= "C";
                break;
            default:
                break;
        }

        $codecString .= $hevcDecoderConfiguration['profile_idc'] . ".";
        $codecString .= $this->reverseBitFlags($hevcDecoderConfiguration['general_profile_compatibility_flags']) . ".";

        if ($hevcDecoderConfiguration['tier_flag'] == '1') {
            $codecString .= "H";
        } else {
            $codecString .= "L";
        }
        $codecString .= $hevcDecoderConfiguration['level_idc'] . ".";

        return $codecString;
    }

    private function reverseBitFlags(string $hexRep): string
    {
        $binary = decbin(hexdec($hexRep));
        $padded = str_pad($binary, strlen($hexRep) * 4, '0', STR_PAD_LEFT);
        $reversed = strrev($padded);
        return dechex(bindec($reversed));
    }
}
