<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SegmentEncryption
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.3.2 - Encrypted Media Presentations';

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));
    }

    //Public validation functions
    public function validateSegmentEncryption(Representation $representation, Segment $segment): void
    {

        $this->validatePSSH($representation, $segment);
        $this->validateSENC($representation, $segment);
    }

    //Private helper functions
    private function validatePSSH(Representation $representation, Segment $segment): void
    {
        $pssh = $segment->getPSSHBoxes();

        $singlePSSH = $this->waveReporter->test(
            section: $this->section,
            test: "Any individual CMAF Segment SHALL have a single encryption key and Initialization Vector",
            result: $pssh && count($pssh) == 1,
            severity: "FAIL",
            pass_message: $representation->path() . " - Single 'pssh' box in segment",
            fail_message: $representation->path() . " - " . ($pssh ? count($pssh) : 0 ) . " 'pssh' boxes in segment",
        );

        if ($singlePSSH) {
            $this->waveReporter->test(
                section: $this->section,
                test: "Any individual CMAF Segment SHALL have a single encryption key and Initialization Vector",
                result: count($pssh[0]->keys) == 1,
                severity: "FAIL",
                pass_message: $representation->path() . " - Single key in 'pssh' box",
                fail_message: $representation->path() . " - " . count($pssh[0]->keys) . " keys in 'pssh' box",
            );
        }
    }

    private function validateSENC(Representation $representation, Segment $segment): void
    {
        $senc = $segment->getSENCBoxes();
        $totalIvSize = 0;
        foreach ($senc as $sencBox) {
            foreach ($sencBox->ivSizes as $size) {
                $totalIvSize += $size;
            }
        }

        $this->waveReporter->test(
            section: $this->section,
            test: "Any individual CMAF Segment SHALL have a single encryption key and Initialization Vector",
            result: $totalIvSize == 0,
            severity: "FAIL",
            pass_message: $representation->path() . " - None of the samples contains an indvidual IV",
            fail_message: $representation->path() . " - At least one sample has an individual IV"
        );
    }
}
