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

class Codecs
{
    //Private subreporters
    private SubReporter $v141Reporter;

    private TestCase $inbandStorageCase;
    private TestCase $initCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141Reporter = &$reporter->context(new ReporterContext(
            "Segments",
            "DVB",
            "v1.4.1",
            []
        ));

        $this->inbandStorageCase = $this->v141Reporter->add(
            section: '5.1.2',
            test: "Content SHOULD be offered using 'avc3' or 'avc4'",
            skipReason: "No AVC stream found"
        );
        $this->initCase = $this->v141Reporter->add(
            section: '5.1.2',
            test: 'All information necessary to decode any Segment shall be provided in the init segment',
            skipReason: "No 'avc1' or 'avc2' stream found"
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
        if (!str_starts_with($format, 'avc')) {
            return;
        }

        $this->inbandStorageCase->pathAdd(
            result: str_starts_with($format, 'avc3') || str_starts_with($format, 'avc4'),
            severity: "WARN",
            path: $representation->path(),
            pass_message: "'$format' found",
            fail_message: "'$format' found"
        );

        if (!str_starts_with($format, 'avc1') && !str_starts_with($format, 'avc2')) {
            return;
        }

        $this->initCase->pathAdd(
            result: $segment->AVCConfigurationHasSPSPPS(),
            severity: "FAIL",
            path: $representation->path(),
            pass_message: "Both 'sps' and 'pps' units found",
            fail_message: "Either 'sps' or 'pps' units missing",
        );
    }

    //Private helper functions
}
