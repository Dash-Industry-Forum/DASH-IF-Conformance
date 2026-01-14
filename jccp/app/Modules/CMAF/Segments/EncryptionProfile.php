<?php

namespace App\Modules\CMAF\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\SegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EncryptionProfile extends SegmentComponent
{
    private TestCase $cmfhdCase;
    private TestCase $cmfhdcCase;
    private TestCase $cmfhdsCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "LEGACY",
                "CMAF",
                []
            )
        );

        $this->cmfhdCase = $this->reporter->add(
            section: 'Section A.1.2',
            test: "Tracks SHALL NOT contain a track encryption box",
            skipReason: "No 'cmfhd' track found"
        );
        $this->cmfhdcCase = $this->reporter->add(
            section: 'Section A.1.3',
            test: "Tracks SHALL be available in 'cenc' encryption",
            skipReason: "No 'cmfhdc' track found"
        );
        $this->cmfhdsCase = $this->reporter->add(
            section: 'Section A.1.4',
            test: "Tracks SHALL be available in 'cbcs' encryption",
            skipReason: "No 'cmfhds' track found"
        );
    }

    //Public validation functions
    public function validateSegment(Representation $representation, Segment $segment, int $segmentIndex): void
    {

        if ($representation->hasProfile("urn:mpeg:cmaf:representation_profile:cmfhd:2017")) {
            $this->validateCMFHD($representation, $segment, $segmentIndex);
        }
        if ($representation->hasProfile("urn:mpeg:cmaf:representation_profile:cmfhdc:2017")) {
            $this->validateCMFHDC($representation, $segment, $segmentIndex);
        }
        if ($representation->hasProfile("urn:mpeg:cmaf:representation_profile:cmfhds:2017")) {
            $this->validateCMFHDS($representation, $segment, $segmentIndex);
        }
    }

    //Private helper functions
    public function validateCMFHD(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $this->cmfhdCase->pathAdd(
            path: $representation->path() . "-$segmentIndex",
            result: $segment->getProtectionScheme() === null,
            severity: "FAIL",
            pass_message: "Non-encrypted track",
            fail_message: "Encrypted track",
        );
    }
    public function validateCMFHDC(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $protectionScheme = $segment->getProtectionScheme();
        if ($protectionScheme) {
            $this->cmfhdcCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: $protectionScheme->scheme->schemeType == 'cenc',
                severity: "FAIL",
                pass_message: "'cenc' encrypted track",
                fail_message: "Otherwise encrypted track",
            );
        }
    }
    public function validateCMFHDS(Representation $representation, Segment $segment, int $segmentIndex): void
    {
        $protectionScheme = $segment->getProtectionScheme();
        if ($protectionScheme) {
            $this->cmfhdsCase->pathAdd(
                path: $representation->path() . "-$segmentIndex",
                result: $protectionScheme->scheme->schemeType == 'cbcs',
                severity: "FAIL",
                pass_message: "'cbcs' encrypted track",
                fail_message: "Otherwise encrypted track",
            );
        }
    }
}
