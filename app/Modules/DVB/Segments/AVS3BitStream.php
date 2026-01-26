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

class AVS3BitStream extends InitSegmentComponent
{
    private TestCase $avs3ColourPrimariesCase;
    private TestCase $avs3MatrixCoefficientsCase;
    private TestCase $avs3TransferCharacteriticsCase;
    private TestCase $avs3HLG10Case;
    private TestCase $avs3PQ10Case;

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

        $this->avs3ColourPrimariesCase = $this->reporter->add(
            section: '5.4.6',
            test: "The value of the signalled ColourPrimaries Property in the MPD SHALL match " .
                  "colour_primaries in the VUI'",
            skipReason: "No AVS3 stream found, or no ColourPrimaries property found"
        );
        $this->avs3MatrixCoefficientsCase = $this->reporter->add(
            section: '5.4.6',
            test: "The value of the signalled MatrixCoefficients Property in the MPD SHALL match " .
                  "matrix_coefficients in the VUI'",
            skipReason: "No AVS3 stream found, or no MatrixCoefficients property found"
        );
        $this->avs3TransferCharacteriticsCase = $this->reporter->add(
            section: '5.4.6',
            test: "The value of the signalled TransferCharacteristics Property in the MPD SHALL match " .
                  "transfer_characteristics in the VUI'",
            skipReason: "No AVS3 stream found, or no TransferCharacteristics property found"
        );
        $this->avs3HLG10Case = $this->reporter->add(
            section: '5.4.7',
            test: "Use of HLG10 within in an AdapationSet shall be signalled according to this section",
            skipReason: "No AVS3 stream found"
        );
        $this->avs3PQ10Case = $this->reporter->add(
            section: '5.4.8',
            test: "Use of PQ10 within in an AdapationSet shall be signalled according to this section",
            skipReason: "No AVS3 stream found"
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

        if ($sdType == 'avs3') {
            $this->validateColourProperties($representation, $segment);
            $this->checkHLG10($representation, $segment);
            $this->checkPQ10($representation, $segment);
        }
    }

    private function checkHLG10(Representation $representation, Segment $segment): void
    {
        $essentialProperties = $representation->getAdaptationSet()->getDOMElements('EssentialProperty');
        $supplementalProperties = $representation->getAdaptationSet()->getDOMElements('SupplementalProperty');

        $colourPrimariesScheme = 'urn:avs:avs3:p6:2022:ColourPrimaries';
        $matrixCoefficientsScheme = 'urn:avs:avs3:p6:2022:MatrixCoefficients';
        $transferCharacteristicsScheme = 'urn:avs:avs3:p6:2022:TransferCharacteristics';

        $isHLG10 = $this->avs3HLG10Case->pathAdd(
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
                $validEssentialMatrix = $property->getAttribute('value') == '8';
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $validEssentialTransfer = $property->getAttribute('value') == '14';
            }
        }

        $isHLG10 = $this->avs3HLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialColour,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for ColourPrimaries",
            fail_message: "Missing or incorrect essentialProperties value for ColourPrimaries",
        ) && $isHLG10;
        $isHLG10 = $this->avs3HLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialMatrix,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for MatrixCoefficients",
            fail_message: "Missing or incorrect essentialProperties value for MatrixCoefficients",
        ) && $isHLG10;
        $isHLG10 = $this->avs3HLG10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialTransfer,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for TransferCharacteristics",
            fail_message: "Missing or incorrect essentialProperties value for TransferCharacteristics",
        ) && $isHLG10;
        $this->avs3HLG10Case->pathAdd(
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

        $colourPrimariesScheme = 'urn:avs:avs3:p6:2022:ColourPrimaries';
        $matrixCoefficientsScheme = 'urn:avs:avs3:p6:2022:MatrixCoefficients';
        $transferCharacteristicsScheme = 'urn:avs:avs3:p6:2022:TransferCharacteristics';

        $isPQ10 = $this->avs3PQ10Case->pathAdd(
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
                $validEssentialMatrix = $property->getAttribute('value') == '8';
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $validEssentialTransfer = $property->getAttribute('value') == '12';
            }
        }

        $isPQ10 = $this->avs3PQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialColour,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for ColourPrimaries",
            fail_message: "Missing or incorrect essentialProperties value for ColourPrimaries",
        ) && $isPQ10;
        $isPQ10 = $this->avs3PQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialMatrix,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for MatrixCoefficients",
            fail_message: "Missing or incorrect essentialProperties value for MatrixCoefficients",
        ) && $isPQ10;
        $isPQ10 = $this->avs3PQ10Case->pathAdd(
            path: $representation->path() . "-init",
            result: $validEssentialTransfer,
            severity: "INFO",
            pass_message: "Correct essentialProperties value for TransferCharacteristics",
            fail_message: "Missing or incorrect essentialProperties value for TransferCharacteristics",
        ) && $isPQ10;
        $this->avs3PQ10Case->pathAdd(
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

        $colourPrimariesScheme = 'urn:avs:avs3:p6:2022:ColourPrimaries';
        $matrixCoefficientsScheme = 'urn:avs:avs3:p6:2022:MatrixCoefficients';
        $transferCharacteristicsScheme = 'urn:avs:avs3:p6:2022:TransferCharacteristics';

        foreach ($essentialProperties as $property) {
            if ($property->getAttribute('schemeIdUri') == $colourPrimariesScheme) {
                $this->avs3ColourPrimariesCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->colourPrimaries,
                    severity: "FAIL",
                    pass_message: "Signalled essential value matches VUI",
                    fail_message: "Signalled essential value does not match VUI"
                );
            }
            if ($property->getAttribute('schemeIdUri') == $matrixCoefficientsScheme) {
                $this->avs3MatrixCoefficientsCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->matrixCoefficients,
                    severity: "FAIL",
                    pass_message: "Signalled essential value matches VUI",
                    fail_message: "Signalled essential value does not match VUI"
                );
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $this->avs3TransferCharacteriticsCase->pathAdd(
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
                $this->avs3ColourPrimariesCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->colourPrimaries,
                    severity: "INFO",
                    pass_message: "Signalled supplemental value matches VUI",
                    fail_message: "Signalled supplemental value does not match VUI"
                );
                $this->avs3ColourPrimariesCase->add(
                    result: false,
                    severity: "WARN",
                    pass_message: "",
                    fail_message: "Use as supplemental property 'not defined' in spec",
                );
            }
            if ($property->getAttribute('schemeIdUri') == $matrixCoefficientsScheme) {
                $this->avs3MatrixCoefficientsCase->pathAdd(
                    path: $representation->path() . "-init",
                    result: count($colrBoxes) &&
                            $property->getAttribute('value') == $colrBoxes[0]->matrixCoefficients,
                    severity: "INFO",
                    pass_message: "Signalled supplemental value matches VUI",
                    fail_message: "Signalled supplemental value does not match VUI"
                );
                $this->avs3MatrixCoefficientsCase->add(
                    result: false,
                    severity: "WARN",
                    pass_message: "",
                    fail_message: "Use as supplemental property 'not defined' in spec",
                );
            }
            if ($property->getAttribute('schemeIdUri') == $transferCharacteristicsScheme) {
                $this->avs3TransferCharacteriticsCase->pathAdd(
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
}
