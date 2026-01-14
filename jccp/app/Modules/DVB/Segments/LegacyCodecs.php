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
use App\Interfaces\ModuleComponents\InitSegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LegacyCodecs extends InitSegmentComponent
{
    private string $section = 'Codec information';
    private string $noAVC = "No AVC stream found";
    private string $noHEVC = "No HEVC stream found";

    private TestCase $codecCase;
    private TestCase $avcProfileCase;
    private TestCase $avcLevelCase;
    private TestCase $hevcTierCase;
    private TestCase $hevcBitDepthCase;
    private TestCase $hevcLevelCase;
    private TestCase $hevcProfileCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "DVB",
                []
            )
        );

        $this->codecCase = $this->reporter->add(
            section: $this->section,
            test: 'The codec should be supported by the specification',
            skipReason: "No valid sample descriptor found"
        );
        $this->avcProfileCase = $this->reporter->add(
            section: $this->section,
            test: 'Profile used for AVC must be suported by the specification',
            skipReason: $this->noAVC
        );
        $this->avcLevelCase = $this->reporter->add(
            section: $this->section,
            test: 'Level used for AVC must be suported by the specification',
            skipReason: $this->noAVC
        );
        $this->hevcTierCase = $this->reporter->add(
            section: $this->section,
            test: 'Tier used for HEVC must be suported by the specification',
            skipReason: $this->noHEVC
        );
        $this->hevcBitDepthCase = $this->reporter->add(
            section: $this->section,
            test: 'Bit depth used for HEVC must be suported by the specification',
            skipReason: $this->noHEVC
        );
        $this->hevcProfileCase = $this->reporter->add(
            section: $this->section,
            test: 'Profile used for HEVC must be suported by the specification',
            skipReason: $this->noHEVC
        );
        $this->hevcLevelCase = $this->reporter->add(
            section: $this->section,
            test: 'Level used for HEVC must be suported by the specification',
            skipReason: $this->noHEVC
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
        $sdType = $segment->getSampleDescriptor();
        if ($sdType === null) {
            return;
        }


        // We use format to either contain the sdtype, or inferred sdtype from encrypted streams
        $format = $sdType;
        if (str_starts_with($sdType, 'enc')) {
            $sinfBox = $segment->getProtectionScheme();
            if ($sinfBox) {
                $format = $sinfBox->originalFormat;
            }
        }
        $this->validateSDType($representation, $sdType, $format);

        if (str_starts_with($format, 'avc')) {
            $this->validateAVC($representation, $segment);
        } elseif (str_starts_with($format, 'hev1') || str_starts_with($format, 'hvc1')) {
            $this->validateHEVC($representation, $segment);
        }
    }

    //Private helper functions
    //
    private function validateAVC(Representation $representation, Segment $segment): void
    {
        $configuration = $segment->getAVCConfiguration();

        $this->avcProfileCase->pathAdd(
            result: $configuration['AVCProfileIndication'] == '100',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Profile valid",
            fail_message: "Profile value of " . $configuration['AVCProfileIndication'] . " not valid"
        );

        $level = $configuration['AVCLevelIndication'];
        $this->avcLevelCase->pathAdd(
            result: $level == '30' || $level == '32' || $level == '40' || $level == '41',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Level valid",
            fail_message: "Level value of $level not valid"
        );
    }

    private function validateHEVC(Representation $representation, Segment $segment): void
    {
        $configuration = $segment->getHEVCConfiguration();

        $this->hevcTierCase->pathAdd(
            result: $configuration['tier_flag'] == '0',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: $representation->path() . " Tier valid",
            fail_message: $representation->path() . " Tier of " . $configuration['tier_flag'] . " not valid"
        );

        $this->hevcBitDepthCase->pathAdd(
            result: $configuration['luma_bit_depth'] == '8' || $configuration['luma_bit_depth'] == '10',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: $representation->path() . " Bit depth valid",
            fail_message: $representation->path() . " Bit depth of " . $configuration['luma_bit_depth'] . " not valid"
        );

        $width = $segment->getWidth();
        $height = $segment->getHeight();

        $lowRes = false;
        if ($width <= 1920 && $height <= 1080) {
            $lowRes = true;
        }

        $profileValid = $configuration['profile_idc'] == '2' || ($lowRes && $configuration['profile_idc'] == '1');

        $this->hevcProfileCase->pathAdd(
            result: $profileValid,
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Profile valid",
            fail_message: "Profile value of " . $configuration['profile_idc'] . " not valid " .
                          "for a " . ($lowRes ? "Low" : "High") . " resolution stream"
        );

        $levelValid = intval($configuration['level_idc']) <= ($lowRes ? 123 : 153);
        $this->hevcLevelCase->pathAdd(
            result: $levelValid,
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Level valid",
            fail_message: "Level value of " . $configuration['level_idc'] . " not valid " .
                          "for a " . ($lowRes ? "Low" : "High") . " resolution stream"
        );
    }

    private function validateSDType(Representation $representation, string $sdType, string $resolved): void
    {
        // NOTE: This is the same as the MPD, with the addition of 'enc' for encrypted streams
        $validCodecs = [
            'avc', 'hev1', 'hvc1',
            'mp4a', 'ec-3', 'ac-4',
            'dtsc', 'dtsh', 'dtse', 'dtsl',
            'stpp',
            'enc'
        ];
        $isValidSDType = false;
        $isValidResolvedType = false;
        foreach ($validCodecs as $validCodec) {
            if (str_starts_with($sdType, $validCodec)) {
                $isValidSDType = true;
            }
            if (str_starts_with($resolved, $validCodec)) {
                $isValidResolvedType = true;
            }
        }


        $this->codecCase->pathAdd(
            result: $isValidSDType,
            severity: "WARN",
            path: $representation->path() . "-init",
            pass_message: "Codec $sdType in list of valid codecs",
            fail_message: "Codec $sdType not in list of valid codecs",
        );

        if (str_starts_with($sdType, 'enc')) {
            $this->codecCase->pathAdd(
                result: true,
                severity: "INFO",
                path: $representation->path() . "-init",
                pass_message: "Original format $resolved is " .
                          ($isValidResolvedType ? "also" : "not") . " in list of valid codecs",
                fail_message: '',
            );
        }
    }
}
