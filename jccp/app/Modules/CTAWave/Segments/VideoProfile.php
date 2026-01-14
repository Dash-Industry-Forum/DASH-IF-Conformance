<?php

namespace App\Modules\CTAWave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\SegmentManager;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes;
use App\Interfaces\ModuleComponents\AdaptationComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoProfile extends AdaptationComponent
{
    private TestCase $validProfileCase;
    private TestCase $singleProfileCase;
    private TestCase $mandatoryProfileCase;
    private TestCase $crossPeriodProfileCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "WAVE Content Spec 2018Ed",
                []
            )
        );

        $this->validProfileCase = $this->reporter->add(
            section: '4.2.1',
            test: "Each WAVE video Media profile SHALL conform to normative ref. listed in Table 1",
            skipReason: "No video track found",
        );
        $this->singleProfileCase = $this->reporter->add(
            section: '4.1',
            test: "Wave content SHALL include one or more switch sets conforming to at least one approved CMAF profile",
            skipReason: "No corresponding adaptations"
        );
        //NOTE: This check seems very conflicting with the above one....
        $this->mandatoryProfileCase = $this->reporter->add(
            section: "5 ",
            test: "If a video tracks is included, the conforming (presentation will at least " .
                  "include AVC (HD) Media profile",
            skipReason: "No corresponding adaptations"
        );
        $this->crossPeriodProfileCase = $this->reporter->add(
            section: "7.2.2",
            test: "Sequential sets SHALL conform to the same CMAF profile",
            skipReason: "Single period found",
        );
    }

    //Public validation functions
    public function validateAdaptationSet(AdaptationSet $adaptationSet): void
    {
        if ($adaptationSet->getAttribute('mimeType') != 'video/mp4') {
            return;
        }
        $segmentManager = app(SegmentManager::class);
        $foundProfiles = [];
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $segmentManager = app(SegmentManager::class);
            $segmentList = $segmentManager->representationSegments($representation);

            if (count($segmentList) == 0) {
                $this->validProfileCase->pathAdd(
                    path: $representation->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "No segments"
                );
                $foundProfiles[] = '';
                continue;
            }
            $foundProfiles[] = $this->getProfile($representation, $segmentList);
        }

        if (count($foundProfiles) == 0) {
            return;
        }
        $this->singleProfileCase->pathAdd(
            path: $adaptationSet->path(),
            result: count(array_unique($foundProfiles)) == 1 && $foundProfiles[0] != '',
            severity: "FAIL",
            pass_message: "Video conforms to a single approved profile",
            fail_message: "Video does not conform to a single approved profile",
        );
        $this->mandatoryProfileCase->pathAdd(
            path: $adaptationSet->path(),
            result: in_array('HD', $foundProfiles),
            severity: "FAIL",
            pass_message: "Mandatory profile found",
            fail_message: "Mandatory profile not found"
        );

        $this->validateCrossPeriod($adaptationSet, $foundProfiles[0]);
    }

    //Private helper functions
    private function validateCrossPeriod(AdaptationSet $adaptationSet, string $profile): void
    {
        $mpdCache = app(MPDCache::class);
        $segmentManager = app(SegmentManager::class);

        foreach ($mpdCache->allPeriods() as $period) {
            if ($period->periodIndex <= $adaptationSet->periodIndex) {
                continue;
            }

            $correspondingAdaptation = $period->getAdaptationSet($adaptationSet->adaptationSetIndex);
            $firstCorrespondingRepresentation = $correspondingAdaptation->getRepresentation(0);

            $segmentList = $segmentManager->representationSegments($firstCorrespondingRepresentation);

            $correspondingProfile = $this->getProfile(
                $firstCorrespondingRepresentation,
                $segmentList
            );

            $this->crossPeriodProfileCase->pathAdd(
                path: $adaptationSet->path() . " - " . $correspondingAdaptation->path(),
                result: $profile == $correspondingProfile,
                severity: "FAIL",
                pass_message: "Profiles correspond",
                fail_message: "Profiles do not correspond"
            );
        }
    }


    /**
     * @param array<Segment> $segments
     **/
    private function getProfile(Representation $representation, array $segments): string
    {
        $sdType = $segments[0]->getSampleDescriptor();

        if ($sdType == 'avc1' || $sdType == 'avc3') {
            return $this->getAVCProfile($representation, $segments);
        }
        if ($sdType == 'hvc1' || $sdType == 'hev1') {
            return $this->getHEVCProfile($representation, $segments);
        }


        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: false,
            severity: "FAIL",
            pass_message: "",
            fail_message: "Unsupported sample descriptor $sdType"
        );
        return '';
    }

    /**
     * @param array<Segment> $segments
     **/
    private function getAVCProfile(Representation $representation, array $segments): string
    {
        $profile = $segments[0]->getProfile();
        if ($profile != "Main" && $profile != "High") {
            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "AVC - Unsupported profile $profile"
            );
            return '';
        }

        $colrBoxes = $segments[0]->boxAccess()->colr();
        $colrValid = true;
        if (count($colrBoxes) >= 1) {
            $colrValid &= $colrBoxes[0]->colourPrimaries == "1";
            $colrValid &= $colrBoxes[0]->transferCharacteristics == "1";
            $colrValid &= $colrBoxes[0]->matrixCoefficients == "1";
        }

        if (!$colrValid) {
            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "AVC - Unsupported 'colr' box parameters"
            );
            return '';
        }


        if (
            !$this->validateDimensions(
                representation: $representation,
                segment: $segments[0],
                maxWidth: 1920,
                maxHeight: 1080,
                maxFrameRate: 60
            )
        ) {
            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "AVC - Unsupported dimensions"
            );
            return '';
        }


        $mediaProfile = '';
        $level = floatval($segments[0]->getLevel());

        if ($level <= 4.0) {
            $mediaProfile = "HD";
        }
        if ($level <= 4.2) {
            $mediaProfile = "AVC_HDHF";
        }


        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: $mediaProfile != '',
            severity: "FAIL",
            pass_message: "AVC - $mediaProfile",
            fail_message: "AVC - Unsupported level $level"
        );
        return $mediaProfile;
    }

    /**
     * @param array<Segment> $segments
     **/
    private function getHEVCProfile(Representation $representation, array $segments): string
    {
        $hvcc = $segments[0]->getHEVCConfiguration();
        $validTier = $hvcc !== null & $hvcc['tier_flag'] == "0";
        if (!$validTier) {
            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "HEVC - Unsupported tier"
            );
            return '';
        }

        $profile = $segments[0]->getProfile();

        if ($profile == "Main10") {
            return $this->getHEVCProfileMain10($representation, $segments);
        }
        if ($profile == "Main") {
            return $this->getHEVCProfileMain($representation, $segments);
        }



        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: false,
            severity: "FAIL",
            pass_message: "",
            fail_message: "HEVC - Unsupported profile $profile"
        );
        return '';
    }

    /**
     * @param array<Segment> $segments
     **/
    private function getHEVCProfileMain10(Representation $representation, array $segments): string
    {
        $level = $segments[0]->getLevel();
        if ($level <= 4.1) {
            if (
                !$this->validateDimensions(
                    representation: $representation,
                    segment: $segments[0],
                    maxWidth: 1920,
                    maxHeight: 1080,
                    maxFrameRate: 60
                )
            ) {
                $this->validProfileCase->pathAdd(
                    path: $representation->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "HEVC (Main10,$level) - Unsupported dimensions"
                );
                return '';
            }

            $colrBoxes = $segments[0]->boxAccess()->colr();
            $colrValid = true;
            if (count($colrBoxes) >= 1) {
                $colrValid &= $colrBoxes[0]->colourPrimaries == "1";
                $colrValid &= $colrBoxes[0]->transferCharacteristics == "1";
                $colrValid &= $colrBoxes[0]->matrixCoefficients == "1";
            }

            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: $colrValid,
                severity: "FAIL",
                pass_message: "HEVC - HHD10",
                fail_message: "HEVC (Main10, $level) - Unsupported 'colr' box parameters"
            );
            return $colrValid ? 'HHD10' : '';
        }
        if ($level <= 5.1) {
            if (
                !$this->validateDimensions(
                    representation: $representation,
                    segment: $segments[0],
                    maxWidth: 3840,
                    maxHeight: 2160,
                    maxFrameRate: 60
                )
            ) {
                $this->validProfileCase->pathAdd(
                    path: $representation->path(),
                    result: false,
                    severity: "FAIL",
                    pass_message: "",
                    fail_message: "HEVC (Main10,$level) - Unsupported dimensions"
                );
                return '';
            }

            $colrBoxes = $segments[0]->boxAccess()->colr();

            $mediaProfile = '';
            if (count($colrBoxes) >= 1) {
                $colrBox = $colrBoxes[0];

                if (
                    in_array($colrBox->colourPrimaries, ['1','9']) &&
                    in_array($colrBox->transferCharacteristics, ['1','14','15']) &&
                    in_array($colrBox->matrixCoefficients, ['1','9','10'])
                ) {
                    $mediaProfile = 'UHD10';
                } elseif (
                    $colrBox->colourPrimaries == "9" &&
                    $colrBox->transferCharacteristics == "16" &&
                    in_array($colrBox->matrixCoefficients, ['9','10'])
                ) {
                    $mediaProfile = "HDR10";
                } elseif (
                    $colrBox->colourPrimaries == "9" &&
                    in_array($colrBox->transferCharacteristics, ['14','18']) &&
                    $colrBox->matrixCoefficients == "9"
                ) {
                    $mediaProfile = 'HLG10';
                }
            }

            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: $mediaProfile != '',
                severity: "FAIL",
                pass_message: "HEVC - $mediaProfile",
                fail_message: "HEVC (Main10, $level) - Unsupported 'colr' box parameters"
            );
            return $mediaProfile;
        }
        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: false,
            severity: "FAIL",
            pass_message: "",
            fail_message: "HEVC (Main10) - Unsupported level $level"
        );
            return '';
    }

    /**
     * @param array<Segment> $segments
     **/
    private function getHEVCProfileMain(Representation $representation, array $segments): string
    {
        $level = $segments[0]->getLevel();

        $colrBoxes = $segments[0]->boxAccess()->colr();
        $colrValid = true;
        if (count($colrBoxes) >= 1) {
            $colrValid &= $colrBoxes[0]->colourPrimaries == "1";
            $colrValid &= $colrBoxes[0]->transferCharacteristics == "1";
            $colrValid &= $colrBoxes[0]->matrixCoefficients == "1";
        }

        if (!$colrValid) {
            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "HEVC (Main) - Unsupported 'colr' box parameters"
            );
            return '';
        }
        if ($level <= 4.1) {
            $mediaProfile = '';
            if (
                $this->validateDimensions(
                    representation: $representation,
                    segment: $segments[0],
                    maxWidth: 1920,
                    maxHeight: 1080,
                    maxFrameRate: 60
                )
            ) {
                $mediaProfile = 'HHD10';
            }


            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: $mediaProfile != '',
                severity: "FAIL",
                pass_message: "HEVC - $mediaProfile",
                fail_message: "HEVC (Main,$level) - Unsupported dimensions"
            );
            return $mediaProfile;
        }
        if ($level <= 5.1) {
            $mediaProfile = '';
            if (
                $this->validateDimensions(
                    representation: $representation,
                    segment: $segments[0],
                    maxWidth: 3840,
                    maxHeight: 2160,
                    maxFrameRate: 60
                )
            ) {
                $mediaProfile = 'UHD10';
            }


            $this->validProfileCase->pathAdd(
                path: $representation->path(),
                result: $mediaProfile != '',
                severity: "FAIL",
                pass_message: "HEVC - $mediaProfile",
                fail_message: "HEVC (Main,$level) - Unsupported dimensions"
            );
            return $mediaProfile;
        }
        $this->validProfileCase->pathAdd(
            path: $representation->path(),
            result: false,
            severity: "FAIL",
            pass_message: "",
            fail_message: "HEVC (Main10) - Unsupported level $level"
        );
            return '';
    }

    private function validateDimensions(
        Representation $representation,
        Segment $segment,
        int $maxWidth,
        int $maxHeight,
        int $maxFrameRate
    ): bool {
        $framerate = $representation->getTransientAttribute('frameRate');
        $validDimensions = $segment->getHeight() <= $maxHeight;
        $validDimensions = $validDimensions && $segment->getWidth() <= $maxWidth;
        $validDimensions = $validDimensions && $framerate != '' && intval($framerate) <= $maxFrameRate;

        return $validDimensions;
    }
}
