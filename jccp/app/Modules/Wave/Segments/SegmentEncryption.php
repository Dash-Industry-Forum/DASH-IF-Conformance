<?php

namespace App\Modules\Wave\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Reporter\TestCase;
use App\Services\Validators\Boxes;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SegmentEncryption
{
    //Private subreporters
    private SubReporter $waveReporter;

    private TestCase $psshCase;
    private TestCase $ivCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));

        $this->psshCase = $this->waveReporter->add(
            section: '4.3.2 - Encrypted Media Presentations',
            test: "Any individual CMAF Segment SHALL have a single encryption key",
            skipReason: 'Stream is not encrypted'
        );
        $this->ivCase = $this->waveReporter->add(
            section: '4.3.2 - Encrypted Media Presentations',
            test: "Any individual CMAF Segment SHALL have a single Initialization Vector",
            skipReason: 'Stream is not encrypted'
        );
    }

    //Public validation functions
    public function validateSegmentEncryption(
        Representation $representation,
        Segment $segment,
        int $segmentIndex
    ): void {

        $this->validatePSSH($representation, $segment, $segmentIndex);
        $this->validateSENC($representation, $segment, $segmentIndex);
    }

    //Private helper functions
    private function validatePSSH(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $pssh = $segment->boxAccess()->pssh();

        if (count($pssh) == 0) {
            return;
        }

        $keyCount = 0;

        foreach ($pssh as $psshBox) {
            $keyCount += count($psshBox->keys);
        }

        $this->psshCase->pathAdd(
            result: $keyCount == 1,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "1 encryption key",
            fail_message: "$keyCount encryption keys",
        );
    }

    private function validateSENC(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $senc = $segment->boxAccess()->senc();

        if (count($senc) == 0) {
            return;
        }
        $totalIvSize = 0;
        foreach ($senc as $sencBox) {
            foreach ($sencBox->ivSizes as $size) {
                $totalIvSize += $size;
            }
        }

        $this->ivCase->pathAdd(
            result: $totalIvSize == 0,
            severity: "FAIL",
            path: $representation->path() . "-$segmentIndex",
            pass_message: "No individual IV found",
            fail_message: $totalIvSize . " bytes of indiviual IV's found"
        );
    }
}
