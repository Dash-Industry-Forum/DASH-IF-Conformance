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
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AVC
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $codecCase;
    private TestCase $avccBoxCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DASH-IF IOP",
            "4.3",
            []
        ));

        $this->codecCase = $this->legacyReporter->add(
            section: '6.2.5.2',
            test: "All AVC representations SHALL be encoded using avc3",
            skipReason: "@bitstreamSwitching flag not set, or no AVC representation found",
        );
        $this->avccBoxCase = $this->legacyReporter->add(
            section: '6.2.5.2',
            test: "All AVC representations SHALL include an 'avcC' box containing SPS and PPS NALs",
            skipReason: "@bitstreamSwitching flag not set, or no AVC representation found",
        );
    }

    //Public validation functions
    public function validateAVC(Representation $representation, Segment $segment): void
    {
        if ($representation->getTransientAttribute('bitstreamSwitching') == '') {
            return;
        }

        $codecs = $representation->getTransientAttribute('codecs');

        if (strpos($codecs, 'avc') === false) {
            return;
        }
        $this->codecCase->pathAdd(
            path: $representation->path() . "-init",
            result: strpos($codecs, 'avc3') !== false,
            severity: "FAIL",
            pass_message: "AVC data encoded correctly",
            fail_message: "AVC data encoded in a different format",
        );

        $avcDecoder = $segment->getAVCConfiguration();
        $this->avccBoxCase->pathAdd(
            path: $representation->path() . "-init",
            result: !empty($avcDecoder),
            severity: "FAIL",
            pass_message: "'avcC' box found",
            fail_message: "'avcC' box not found",
        );
        if (!empty($avcDecoder)) {
            $this->avccBoxCase->pathAdd(
                path: $representation->path() . "-init",
                result: $avcDecoder['_hasSPS'] == "1" && $avcDecoder['_hasPPS'] == "1",
                severity: "FAIL",
                pass_message: "SPS and PPS found",
                fail_message: "SPS and/or PPS missing",
            );
        }
    }

    //Private helper functions
}
