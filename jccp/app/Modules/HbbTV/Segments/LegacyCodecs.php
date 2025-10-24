<?php

namespace App\Modules\HbbTV\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LegacyCodecs
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private string $section = 'Codec information';
    private string $noAVC = "No AVC stream found";

    private TestCase $codecCase;
    private TestCase $avcProfileCase;
    private TestCase $avcLevelCase;
    private TestCase $audioInitCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "HbbTV",
            []
        ));

        $this->codecCase = $this->legacyReporter->add(
            section: $this->section,
            test: 'The codec should be supported by the specification',
            skipReason: "No valid sample descriptor found"
        );
        $this->avcProfileCase = $this->legacyReporter->add(
            section: $this->section,
            test: 'Profile used for AVC must be suported by the specification',
            skipReason: $this->noAVC
        );
        $this->avcLevelCase = $this->legacyReporter->add(
            section: $this->section,
            test: 'Level used for AVC must be suported by the specification',
            skipReason: $this->noAVC
        );
        $this->audioInitCase = $this->legacyReporter->add(
            section: $this->section,
            test: 'All info necessary to decode any audio segment shall be provided in the Initialization segment',
            skipReason: "No audio stream found"
        );
    }

    //Public validation functions
    public function validateCodecs(Representation $representation, Segment $segment): void
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
        }
        if (str_starts_with($format, 'mp4a')){
            $this->validateAudio($representation, $segment);
        }
    }

    //Private helper functions
    //
    private function validateAudio(Representation $representation, Segment $segment): void
    {
        $configuration = $segment->getAudioConfiguration();
        $this->audioInitCase->pathAdd(
            result: $configuration['SampleRate'] != '',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Sample rate found",
            fail_message: "Sample rate not found",
        );
        $this->audioInitCase->pathAdd(
            result: $configuration['Channels'] != '',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Channel configuration found",
            fail_message: "Channel configuration not found",
        );
    }

    private function validateAVC(Representation $representation, Segment $segment): void
    {
        $configuration = $segment->getAVCConfiguration();

        $profile = $configuration['AVCProfileIndication'];
        $this->avcProfileCase->pathAdd(
            result: $profile == '77' || $profile == '100',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Profile valid",
            fail_message: "Profile value of " . $profile . " not valid"
        );

        $level = $configuration['AVCLevelIndication'];
        $this->avcLevelCase->pathAdd(
            result: $level == '30' || $level == '31' || $level == '32' || $level == '40',
            severity: "FAIL",
            path: $representation->path() . "-init",
            pass_message: "Level valid",
            fail_message: "Level value of $level not valid"
        );
    }


    private function validateSDType(Representation $representation, string $sdType, string $resolved): void
    {
        // NOTE: This is the same as the MPD, with the addition of 'enc' for encrypted streams
        $validCodecs = [
            'avc',
            'mp4a', 'ec-3',
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
