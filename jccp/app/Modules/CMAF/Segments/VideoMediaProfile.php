<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoMediaProfile
{
    //Private subreporters
    private SubReporter $cmafReporter;

    private TestCase $profileCase;
    private TestCase $brandCase;
    private TestCase $cfhdCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->cmafReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "CMAF",
            []
        ));

        $this->profileCase = $this->cmafReporter->add(
            section: 'Section 7.3.4.1',
            test: "All CMAF video tracks in a CMAF Switching Set SHALL conform to one CMAF Media Profile",
            skipReason: 'No video switching set found'
        );
        $this->brandCase = $this->cmafReporter->add(
            section: 'Section A.2 / B.5',
            test: "If a CMAF brand is signalled, it SHALL correspond with the table",
            skipReason: 'No cmaf brands signalled'
        );
        $this->cfhdCase = $this->cmafReporter->add(
            section: 'Section A.1.2/A.1.3/A.1.4',
            test: "Video adaptation sets SHALL include at least one 'cfhd' representation",
            skipReason: 'No video track found with CMAF profile found'
        );
    }

    //Public validation functions
    public function validateVideoMediaProfiles(AdaptationSet $adaptationSet): void
    {
        //TODO: Only if video
        $signalledBrands = [];

        $segmentManager = app(SegmentManager::class);

        $hasCFHD = false;
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentList = $segmentManager->representationSegments($representation);

            $highestBrand = '____'; // Unknown;
            if (count($segmentList)) {
                if (in_array('cfhd', $segmentList[0]->getBrands())) {
                    $hasCFHD = true;
                }
                $highestBrand = $this->validateAndDetermineBrand($representation, $segmentList[0]);
            }
            $signalledBrands[] = $highestBrand; // Unknown
        }

        $this->profileCase->pathAdd(
            result: count(array_unique($signalledBrands)) == 1,
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "All representations signal the same highest brand",
            fail_message: "Not all representations signal the same highest brand"
        );

        $this->cfhdCase->pathAdd(
            result: $hasCFHD,
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "At least one 'cfhd' track found",
            fail_message: "No 'cfhd' tracks found"
        );
    }

    //Private helper functions
    private function validateAndDetermineBrand(Representation $representation, Segment $segment): string
    {
        $sdType = $segment->getSampleDescriptor();

        if ($sdType == 'avc1' || $sdType == 'avc3') {
            return $this->validateAndDetermineBrandAVC($representation, $segment);
        }
        if ($sdType == 'hev1' || $sdType == 'hvc1') {
            return $this->validateAndDetermineBrandHEVC($representation, $segment);
        }


        return '____'; // Unknown
    }

    private function validateAndDetermineBrandAVC(Representation $representation, Segment $segment): string
    {
        $brands = $segment->getBrands();

        $highestBrand = '____'; //Unknown

        if (in_array('cfsd', $brands)) {
            $highestBrand = 'cfsd';
            $this->validateAVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'cfsd',
                targetProfile: "100",
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
            $highestBrand = 'cfhd';
            $this->validateAVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'cfhd',
                targetProfile: "100",
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
            $highestBrand = 'chdf';
            $this->validateAVCParameters(
                representation: $representation,
                segment: $segment,
                brand: 'chdf',
                targetProfile: "100",
                maxLevel: "42",
                validColourPrimaries: ["1"],
                validTransferCharacteristics: ["1"],
                validMatrixCoefficients: ["1"],
                maxHeight: 1080,
                maxWidth: 1920,
                maxFrameRate: 60
            );
        }
        return $highestBrand;
    }
    private function validateAndDetermineBrandHEVC(Representation $representation, Segment $segment): string
    {
        $brands = $segment->getBrands();

        $highestBrand = '____'; //Unknown

        if (in_array('chhd', $brands)) {
            $highestBrand = 'chhd';
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
            $highestBrand = 'chh1';
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
            $highestBrand = 'cud8';
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
            $highestBrand = 'cud1';
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
            $highestBrand = 'chr1';
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
        return $highestBrand;
    }

    /**
     * @param array<string> $validColourPrimaries
     * @param array<string> $validTransferCharacteristics
     * @param array<string> $validMatrixCoefficients
     **/
    private function validateAVCParameters(
        Representation $representation,
        Segment $segment,
        string $brand,
        string $targetProfile,
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
            targetProfile: [$targetProfile],
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
            $signalledTransferCharacteristics = $spsConfiguration['transfer_characteristics'];
            $signalledMatrixCoefficients = $spsConfiguration['matrix_coefficients'];
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
        //NOTE: Should we keep supporting colourPrimaries, transferCharacteristics and matrixCoefficients?

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
            result: $segment->getHeight() < $maxHeight,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum height",
            fail_message: "Signalled brand $brand exceeds maximum height",
        );
        $this->brandCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segment->getWidth() < $maxWidth,
            severity: "FAIL",
            pass_message: "Signalled brand $brand conforms to maximum height",
            fail_message: "Signalled brand $brand exceeds maximum height",
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
