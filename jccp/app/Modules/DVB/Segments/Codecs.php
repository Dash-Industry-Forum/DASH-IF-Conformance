<?php

namespace App\Modules\DVB\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Codecs
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private string $section = 'Codec information';

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "DVB",
            []
        ));
    }

    //Public validation functions
    public function validateCodecs(Representation $representation, Segment $segment): void
    {
        $sdType = $segment->getSampleDescriptor();
        $this->legacyReporter->test(
            section: "Internal",
            test: "The segment needs to contain a valid sample descriptor ('sdType')",
            result: $sdType !== null,
            severity: "FAIL",
            pass_message: $representation->path() . " Found ${sdType} sample descriptor",
            fail_message: $representation->path() . " No sample descriptor found",
        );

        if ($sdType === null) {
            return;
        }

        $this->validateSDType($representation, $sdType);

        // We use format to either contain the sdtype, or inferred sdtype from encrypted streams
        $format = $sdType;
        if (str_starts_with($sdType, 'env')) {
            $sinfBox = $segment->getProtectionScheme();
            if ($sinfBox) {
                $format = $sinfBox->originalFormat;
            }
        }

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
    }

    private function validateHEVC(Representation $representation, Segment $segment): void
    {
        $configuration = $segment->getHEVCConfiguration();

        $this->legacyReporter->test(
            section: $this->section,
            test: 'Tier used for HEVC must be suported by the specification in Section 5.2.3',
            result: $configuration['tier_flag'] == '0',
            severity: "FAIL",
            pass_message: $representation->path() . " Tier valid",
            fail_message: $representation->path() . " Tier of " . $configuration['tier_flag'] . " not valid"
        );

        $this->legacyReporter->test(
            section: $this->section,
            test: 'Bit depth used for HEVC must be suported by the specification in Section 5.2.3',
            result: $configuration['luma_bit_depth'] == '8' || $configuration['luma_bit_depth'] == '10',
            severity: "FAIL",
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

        $this->legacyReporter->test(
            section: $this->section,
            test: 'Profile used depth used for HEVC must be suported by the specification in Section 5.2.3',
            result: $profileValid,
            severity: "FAIL",
            pass_message: $representation->path() . " Profile valid",
            fail_message: $representation->path() . " Profile of " . $configuration['profile_idc'] . " not valid " .
                          "for a " . ($lowRes ? "Low" : "High") . " resolution stream"
        );

        $levelValid = intval($configuration['level_idc']) <= ($lowRes ? 123 : 153);
        $this->legacyReporter->test(
            section: $this->section,
            test: 'Level used depth used for HEVC must be suported by the specification in Section 5.2.3',
            result: $levelValid,
            severity: "FAIL",
            pass_message: $representation->path() . " Level valid",
            fail_message: $representation->path() . " Level of " . $configuration['level_idc'] . " not valid " .
                          "for a " . ($lowRes ? "Low" : "High") . " resolution stream"
        );
    }

    private function validateSDType(Representation $representation, string $sdType): void
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
        foreach ($validCodecs as $validCodec) {
            if (str_starts_with($sdType, $validCodec)) {
                $isValidSDType = true;
                break;
            }
        }
        $this->legacyReporter->test(
            section: $this->section,
            test: 'The codec should be supported by the specification',
            result: $isValidSDType,
            severity: "WARN",
            pass_message: $representation->path() . " Codec $sdType in list of valid codecs",
            fail_message: $representation->path() . " Codec $sdType not in list of valid codecs",
        );
    }
}
