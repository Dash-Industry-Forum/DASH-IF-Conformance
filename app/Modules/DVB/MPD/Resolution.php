<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Services\ModuleReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Validators\Boxes\DescriptionType;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Resolution
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $resolutionCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "DVB",
            []
        ));

        $this->resolutionCase = $this->legacyReporter->add(
            section: "Codec Information",
            test: "The resolution of the stream should conform to table 10.3",
            skipReason: "No Video stream found"
        );
    }

    //Public validation functions
    public function validateResolution(Representation $representation): void
    {
        $width = $representation->getTransientAttribute('width');
        $height = $representation->getTransientAttribute('height');
        $resolution = "${width}x${height}";

        if ($resolution == "x") {
            //This is not a video track
            return;
        }

        $validResolutions = [];
        $scanType = $representation->getTransientAttribute('scanType');
        if ($scanType == '') {
            $scanType = 'progressive';
        }
        if ($scanType == 'progressive') {
            $validResolutions = $this->validProgressiveResolutions();
        }
        if ($scanType == 'interlaced') {
            $validResolutions = $this->validInterlacedResolutions();
        }


        $this->resolutionCase->pathAdd(
            path: $representation->path(),
            result: in_array($resolution, $validResolutions),
            severity: "WARN",
            pass_message: "Resolution matches the table",
            fail_message: "Resolution $resolution does not match the table",
        );
    }

    //Private helper functions
    /**
     * @return array<string>
     **/
    private function validProgressiveResolutions(): array
    {
        return [
            '3840x2160',
            '3200x1800',
            '2560x1440',
            '1920x1080',
            '1600x900',
            '1280x720',
            '1024x576',
            '960x540',
            '852x480',
            '768x432',
            '720x404',
            '704x396',
            '640x360',
            '512x288',
            '480x270',
            '384x216',
            '320x180',
            '192x108',
        ];
    }

    /**
     * @return array<string>
     **/
    private function validInterlacedResolutions(): array
    {
        return [
            '1920x1080',
            '704x576',
            '533x576',
            '352x288',
        ];
    }
}
