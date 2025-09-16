<?php

namespace App\Modules\DVB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ModuleLogger;
use App\Services\MPDCache;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Services\Manifest\Representation;
use App\Services\Segment;
use App\Interfaces\Module;
## Segment checks
use App\Modules\DVB\Segments\Resolution;
use App\Modules\DVB\Segments\Codecs;

class Segments extends Module
{
    public function __construct()
    {
        parent::__construct();
        $this->name = "DVB Segments Module";
    }

    public function validateMPD(): void
    {
        parent::validateMPD();
    }

    /**
     * @param array<Segment> $segments
     **/
    public function validateSegments(Representation $representation, array $segments): void
    {
        foreach ($segments as $segmentIndex => $segment) {
            if ($segmentIndex == 0) {
                $this->validateInitialization($representation, $segment);
            }
            $this->validateSegment($segment);
        }
    }

    private function validateInitialization(Representation $representation, Segment $segment): void
    {
        $sdType = $segment->getSampleDescriptor();
        $validSdType = $sdType !== null;

        $reporter = app(ModuleReporter::class);
        $legacyreporter = $reporter->context(new ReporterContext(
            "Segments",
            "LEGACY",
            "DVB",
            []
        ));

        $legacyreporter->test(
            section: "Unknown",
            test: "The segment needs to contain a valid 'sdType'",
            result: $validSdType,
            severity: "FAIL",
            pass_message: "Check succeeded for Representation " . $representation->path(),
            fail_message: "Check failed for Representation " . $representation->path(),
        );
    }

    private function validateSegment(Segment $segment): void
    {
    }
}
