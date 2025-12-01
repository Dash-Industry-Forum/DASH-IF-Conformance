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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SEILocations
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $hevcSEICase;
    private TestCase $vvcSEICase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "DVB",
            "v1.4.1",
            []
        ));

        $this->hevcSEICase = $this->v141Reporter->add(
            section: '5.2.4',
            test: "For HEVC streams, SEI messages are placed according to ETSI TS 101 154 - L.3.3.8",
            skipReason: "No HEVC stream found"
        );
        $this->vvcSEICase = $this->v141Reporter->add(
            section: '5.2.4',
            test: "For VVC streams, SEI messages are placed according to ETSI TS 101 154 - 5.15.1",
            skipReason: "No VVC stream found"
        );
    }

    //Public validation functions
    public function validateSEILocations(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $sdType = $segment->getSampleDescriptor();
        if ($sdType === null) {
            return;
        }

        if ($sdType == 'hev1' || $sdType == 'hevc') {
            $this->validateHEVCSEI($representation, $segment, $segmentIndex);
        }
        if ($sdType == 'vvi1' || $sdType == 'vvc1') {
            $this->validateVVCSEI($representation, $segment, $segmentIndex);
        }
    }


    private function validateHEVCSEI(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $allowBeforePrefixCodes = [32,33,34];

        $validPrefix = true;
        $validSuffix = true;

        $samples = $segment->getNalSamples();

        foreach ($samples as $sampleIndex => $sample) {
            $foundPrefix = false;
            $foundSuffix = false;

            foreach ($sample->units as $unit) {
                if ($unit->type == "SEI Suffix") {
                    $foundSuffix = true;
                    continue;
                }
                if ($unit->type == "SEI Prefix") {
                    $foundPrefix = true;
                    continue;
                }

                if (in_array($unit->code, $allowBeforePrefixCodes)) {
                    if ($foundPrefix) {
                        $validPrefix = false;
                    }
                    continue;
                }
                if ($foundSuffix) {
                    $validSuffix = false;
                }
            }
        }

        $this->hevcSEICase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $validPrefix,
            severity: "FAIL",
            pass_message: "All found SEI Prefix units are valid",
            fail_message: "Not all found SEI Prefix units are valid",
        );
        $this->hevcSEICase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $validSuffix,
            severity: "FAIL",
            pass_message: "All found SEI Suffix units are valid",
            fail_message: "Not all found SEI Suffix units are valid",
        );
    }

    private function validateVVCSEI(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        $allowBeforePrefixCodes = [16,17];

        $validPrefix = true;
        $validSuffix = true;

        $samples = $segment->getNalSamples();

        foreach ($samples as $sampleIndex => $sample) {
            $foundPrefix = false;
            $foundSuffix = false;

            foreach ($sample->units as $unit) {
                if ($unit->type == "SEI Suffix") {
                    $foundSuffix = true;
                    continue;
                }
                if ($unit->type == "SEI Prefix") {
                    $foundPrefix = true;
                    continue;
                }

                if (in_array($unit->code, $allowBeforePrefixCodes)) {
                    if ($foundPrefix) {
                        $validPrefix = false;
                    }
                    continue;
                }
                if ($foundSuffix) {
                    $validSuffix = false;
                }
            }
        }

        $this->vvcSEICase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $validPrefix,
            severity: "FAIL",
            pass_message: "All found SEI Prefix units are valid",
            fail_message: "Not all found SEI Prefix units are valid",
        );
        $this->vvcSEICase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $validSuffix,
            severity: "FAIL",
            pass_message: "All found SEI Suffix units are valid",
            fail_message: "Not all found SEI Suffix units are valid",
        );
    }
}
