<?php

namespace App\Modules\IOP\Segments;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Manifest\AdaptationSet;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\ModuleComponents\InitSegmentComponent;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HEVC extends InitSegmentComponent
{
    private TestCase $codecCase;
    private TestCase $hvccBoxCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "Segments",
                "DASH-IF IOP",
                "4.3",
                []
            )
        );

        $this->codecCase = $this->reporter->add(
            section: '6.2.5.2',
            test: "All HEVC representations SHALL be encoded using hev1",
            skipReason: "@bitstreamSwitching flag not set, or no HEVC representation found",
        );
        $this->hvccBoxCase = $this->reporter->add(
            section: '6.2.5.2',
            test: "All HEVC representations SHALL include an 'hvcC' box containing SPS, PPS and VPS NALs",
            skipReason: "@bitstreamSwitching flag not set, or no HEVC representation found",
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
        if ($representation->getTransientAttribute('bitstreamSwitching') == '') {
            return;
        }

        $codecs = $representation->getTransientAttribute('codecs');

        if (strpos($codecs, 'hvc') === false && strpos($codecs, 'hev') === false) {
            return;
        }
        $this->codecCase->pathAdd(
            path: $representation->path() . "-init",
            result: strpos($codecs, 'hev1') !== false,
            severity: "FAIL",
            pass_message: "HEVC data encoded correctly",
            fail_message: "HEVC data encoded in a different format",
        );

        $hevcDecoder = $segment->getHEVCConfiguration();
        $this->hvccBoxCase->pathAdd(
            path: $representation->path() . "-init",
            result: !empty($hevcDecoder),
            severity: "FAIL",
            pass_message: "'hvcC' box found",
            fail_message: "'hvcC' box not found",
        );
        if (!empty($hevcDecoder)) {
            $this->hvccBoxCase->pathAdd(
                path: $representation->path() . "-init",
                result: $hevcDecoder['_hasSPS'] == "1" && $hevcDecoder['_hasPPS'] == "1" && $hevcDecoder['_hasVPS'],
                severity: "FAIL",
                pass_message: "SPS, PPS and VPS found",
                fail_message: "SPS, PPS and/or VPS missing",
            );
        }
    }

    //Private helper functions
}
