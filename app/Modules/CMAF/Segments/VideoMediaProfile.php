<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\AdaptationComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoMediaProfile extends AdaptationComponent
{
    private TestCase $brandCase;
    private TestCase $cfhdCase;

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

        $this->brandCase = $this->reporter->add(
            section: 'Section A.2 (AVC) / B.6 (HEVC)',
            test: "The maximum encoding parameters [..as per the table..] SHALL not be exceeded",
            skipReason: 'No cmaf brands signalled'
        );
        $this->cfhdCase = $this->reporter->add(
            section: 'Section A.1.2',
            test: "Video adaptation sets SHALL include at least one 'cfhd' representation",
            skipReason: 'No video track found, or no CMFHD profile signalled'
        );
    }

    //Public validation functions
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
        if (!str_starts_with($adaptationSet->getTransientAttribute('mimeType'), 'video/')) {
            return;
        }

        $segmentManager = app(SegmentManager::class);

        $hasCFHD = false;
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);

            if (count($segmentList)) {
                if (in_array('cfhd', $segmentList[0]->getBrands())) {
                    $hasCFHD = true;
                }
                $this->validateAndDetermineBrand($representation, $segmentList[0]);
            }
        }

        $mpdCache = app(MPDCache::class);


        if ($mpdCache->hasProfile("urn:mpeg:cmaf:presentation_profile:cmfhd:2016")) {
            $this->cfhdCase->pathAdd(
                result: $hasCFHD,
                severity: "FAIL",
                path: $adaptationSet->path(),
                pass_message: "At least one 'cfhd' track found",
                fail_message: "No 'cfhd' tracks found"
            );
        }
    }

    //Private helper functions
    private function validateAndDetermineBrand(Representation $representation, Segment $segment): void
    {
        $sdType = $segment->getSampleDescriptor();

        if ($sdType == 'avc1' || $sdType == 'avc3') {
            $this->validateAndDetermineBrandAVC($representation, $segment);
        }
        if ($sdType == 'hev1' || $sdType == 'hvc1') {
            $this->validateAndDetermineBrandHEVC($representation, $segment);
        }
    }

    private function validateAndDetermineBrandAVC(Representation $representation, Segment $segment): void
    {
        $brands = $segment->getBrands();


        if (in_array('cfsd', $brands)) {
            $this->validateAVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'cfsd',
                targetProfile: ["66","77","100"],
                maxLevel: "31",
                validColourPrimaries: ["1","5","6"],
                validTransferCharacteristics: ["1","6"],
                validMatrixCoefficients: ["1","5","6"],
                maxHeight: 576,
                maxWidth: 864,
                maxFrameRate: 60
            );
        }
        if (in_array('cfhd', $brands)) {
            $this->validateAVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'cfhd',
                targetProfile: ["66","77","100"],
                maxLevel: "40",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 1080,
                maxWidth: 1920,
                maxFrameRate: 60
            );
        }
        if (in_array('chdf', $brands)) {
            $this->validateAVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'chdf',
                targetProfile: ["66","77","100"],
                maxLevel: "42",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 1080,
                maxWidth: 1920,
                maxFrameRate: 60
            );
        }
    }
    private function validateAndDetermineBrandHEVC(Representation $representation, Segment $segment): void
    {
        $brands = $segment->getBrands();

        if (in_array('chhd', $brands)) {
            $this->validateHEVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'chhd',
                targetProfile:["Main"],
                maxLevel: "123",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 1080,
                maxWidth: 1920,
                maxFrameRate: 60
            );
        }
        if (in_array('chh1', $brands)) {
            $this->validateHEVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'chh1',
                targetProfile:["Main10"],
                maxLevel: "123",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 1080,
                maxWidth: 1920,
                maxFrameRate: 60
            );
        }
        if (in_array('cud8', $brands)) {
            $this->validateHEVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'cud8',
                targetProfile:["Main8"],
                maxLevel: "150",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 2160,
                maxWidth: 3840,
                maxFrameRate: 60
            );
        }
        if (in_array('cud1', $brands)) {
            $this->validateHEVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'cud1',
                targetProfile:["Main10"],
                maxLevel: "153",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 2160,
                maxWidth: 3840,
                maxFrameRate: 60
            );
        }
        if (in_array('chr1', $brands)) {
            $this->validateHEVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'chr1',
                targetProfile:["Main10"],
                maxLevel: "153",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 2160,
                maxWidth: 3840,
                maxFrameRate: 60
            );
        }
    }

    /**
     * @param array<string> $validColourPrimaries
     * @param array<string> $targetProfile
     * @param array<string> $validTransferCharacteristics
     * @param array<string> $validMatrixCoefficients
     **/
    private function validateAVCParameters(
        Representation $representation,
        Segment $segment,
        string $brand,
        array $targetProfile,
        string $maxLevel,
        array $validColourPrimaries,
        array $validTransferCharacteristics,
        array $validMatrixCoefficients,
        int $maxHeight,
        int $maxWidth,
        int $maxFrameRate,
    ): void {
        //TODO: ValidateFramerate
        $avcConfiguration = $segment->getAVCConfiguration();
        $spsConfiguration = $segment->getSPSConfiguration();

        $signalledColourPrimaries = "1";
        $signalledTransferCharacteristics = "1";
        $signalledMatrixCoefficients = "1";

        if ($spsConfiguration["vui_colour_description_present_flag"] == "1") {
            $signalledColourPrimaries = $spsConfiguration['vui_colour_primaries'];
            $signalledTransferCharacteristics = $spsConfiguration['vui_transfer_characteristics'];
            $signalledMatrixCoefficients = $spsConfiguration['vui_matrix_coefficients'];
        }

        $this->validateParameters(
            representation: $representation,
            segment: $segment,
            brand: $brand,
            targetProfile: $targetProfile,
            signalledProfile: $avcConfiguration['AVCProfileIndication'],
            maxLevel: $maxLevel,
            signalledLevel: $avcConfiguration['AVCLevelIndication'],
            validColourPrimaries: $validColourPrimaries,
            signalledColourPrimaries: $signalledColourPrimaries,
            validTransferCharacteristics: $validTransferCharacteristics,
            signalledTransferCharacteristics: $signalledTransferCharacteristics,
            validMatrixCoefficients: $validMatrixCoefficients,
            signalledMatrixCoefficients: $signalledMatrixCoefficients,
            maxHeight: $maxHeight,
            signalledHeight: $segment->getHeight(),
            maxWidth: $maxWidth,
            signalledWidth: $segment->getWidth(),
            maxFrameRate: $maxFrameRate,
            signalledFrameRate: 0,
        );
    }

    /**
     * @param array<string> $validColourPrimaries
     * @param array<string> $validTransferCharacteristics
     * @param array<string> $validMatrixCoefficients
     * @param array<string> $targetProfile
     **/
    private function validateHEVCParameters(
        Representation $representation,
        Segment $segment,
        string $brand,
        array $targetProfile,
        string $maxLevel,
        array $validColourPrimaries,
        array $validTransferCharacteristics,
        array $validMatrixCoefficients,
        int $maxHeight,
        int $maxWidth,
        int $maxFrameRate,
    ): void {
        //TODO: ValidateFramerate
        $hevcConfiguration = $segment->getHEVCConfiguration();
        $spsConfiguration = $segment->getSPSConfiguration();

        $signalledProfile = 'Other';
        if ($hevcConfiguration["profile_idc"] == "1") {
            $signalledProfile = "Main";
        }
        if ($hevcConfiguration["profile_idc"] == "2") {
            $signalledProfile = "Main10";
        }



        $signalledColourPrimaries = "1";
        $signalledTransferCharacteristics = "1";
        $signalledMatrixCoefficients = "1";

        if ($spsConfiguration["colour_description_present_flag"] == "1") {
            $signalledColourPrimaries = $spsConfiguration['colour_primaries'];
            $signalledTransferCharacteristics = $spsConfiguration['transfer_characteristic'];
            $signalledMatrixCoefficients = $spsConfiguration['matrix_coeffs'];
        }

        //As tier is only hevc based, we handle it separately
        //NOTE: Has always been hardcoded to a value of 0
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $hevcConfiguration["tier_flag"] == "0",
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to tier_flag",
            fail_message: "Signalled brand $brand but does not conform to tier_flag",
        );

        $this->validateParameters(
            representation: $representation,
            segment: $segment,
            brand: $brand,
            targetProfile: $targetProfile,
            signalledProfile: $signalledProfile,
            maxLevel: $maxLevel,
            signalledLevel: $hevcConfiguration['level_idc'],
            validColourPrimaries: $validColourPrimaries,
            signalledColourPrimaries: $signalledColourPrimaries,
            validTransferCharacteristics: $validTransferCharacteristics,
            signalledTransferCharacteristics: $signalledTransferCharacteristics,
            validMatrixCoefficients: $validMatrixCoefficients,
            signalledMatrixCoefficients: $signalledMatrixCoefficients,
            maxHeight: $maxHeight,
            signalledHeight: $segment->getHeight(),
            maxWidth: $maxWidth,
            signalledWidth: $segment->getWidth(),
            maxFrameRate: $maxFrameRate,
            signalledFrameRate: 0,
        );
    }

    /**
     * @param array<string> $validColourPrimaries
     * @param array<string> $validTransferCharacteristics
     * @param array<string> $validMatrixCoefficients
     * @param array<string> $targetProfile
     **/
    private function validateParameters(
        Representation $representation,
        Segment $segment,
        string $brand,
        array $targetProfile,
        string $signalledProfile,
        string $maxLevel,
        string $signalledLevel,
        array $validColourPrimaries,
        string $signalledColourPrimaries,
        array $validTransferCharacteristics,
        string $signalledTransferCharacteristics,
        array $validMatrixCoefficients,
        string $signalledMatrixCoefficients,
        int $maxHeight,
        int $signalledHeight,
        int $maxWidth,
        int $signalledWidth,
        int $maxFrameRate,
        int $signalledFrameRate,
    ): void {
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: in_array($signalledProfile, $targetProfile),
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to targetProfile",
            fail_message: "Signalled brand $brand but does not conform to targetProfile",
        );

        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $signalledLevel <= $maxLevel,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum level",
            fail_message: "Signalled brand $brand but exceeds maximum level",
        );


        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: in_array($signalledColourPrimaries, $validColourPrimaries),
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to colour primaries",
            fail_message: "Signalled brand $brand does not conform to colour primaries",
        );
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: in_array($signalledTransferCharacteristics, $validTransferCharacteristics),
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to transfer characteristics",
            fail_message: "Signalled brand $brand does not conform to transfer characteristics",
        );
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: in_array($signalledMatrixCoefficients, $validMatrixCoefficients),
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to matrix coefficients",
            fail_message: "Signalled brand $brand does not conform to matrix coefficients",
        );

        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segment->getHeight() <= $maxHeight,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum height",
            fail_message: "Signalled brand $brand exceeds maximum height",
        );
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segment->getWidth() <= $maxWidth,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum width",
            fail_message: "Signalled brand $brand exceeds maximum width",
        );
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $signalledFrameRate < $maxFrameRate,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum frameRate",
            fail_message: "Signalled brand $brand exceeds maximum frameRate",
        );
    }
}
