<?php

namespace App\Modules\LowLatency\MPD;

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

class ServiceDescription
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $presentCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "LEGACY",
            "Low Latency",
            []
        ));

        $this->presentCase = $this->legacyReporter->add(
            section: '9.X.4.2',
            test: 'At least one valid ServiceDescription element SHALL be present',
            skipReason: "",
        );
    }

    //Public validation functions
    public function validateServiceDescription(): void
    {
        $mpdCache = app(MPDCache::class);

        $serviceDescriptions = $mpdCache->getDOMElements('ServiceDescription');

        if (count($serviceDescriptions) == 0) {
            $this->presentCase->add(
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "No ServiceDescription elements found"
            );
            return;
        }

        //NOTE: These checks used to run once per period, but no period data was used, so moved to MPD-level
        $atLeastOneValid = false;
        foreach ($serviceDescriptions as $descriptionIndex => $serviceDescription) {
            $atLeastOneValid |= $this->validateSingleDescription($serviceDescription, $descriptionIndex);
        }
        if (!$atLeastOneValid) {
            $this->presentCase->add(
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "No valid ServiceDescription elements found"
            );
        }
    }

    //Private helper functions
    private function validateSingleDescription(\DOMElement $serviceDescription, int $descriptionIndex): bool
    {
        $latencyElements = $serviceDescription->getElementsByTagName('Latency');
        if (count($latencyElements) == 0) {
            $this->presentCase->pathAdd(
                path: "ServiceDescription $descriptionIndex",
                result: false,
                severity: "INFO",
                pass_message: "",
                fail_message: "Missing Latency information"
            );
            return false;
        }

        $hasLatencyTarget = false;
        $hasLatencyOptional = false;
        foreach ($latencyElements as $latencyElement) {
            if ($latencyElement->getAttribute('target') != '') {
                $hasLatencyTarget = true;
            }
            if ($latencyElement->getAttribute('min') != '' || $latencyElement->getAttribute('max') != '') {
                $hasLatencyOptional = true;
            }
        }
        if (!$hasLatencyTarget) {
            $this->presentCase->pathAdd(
                path: "ServiceDescription $descriptionIndex",
                result: false,
                severity: "INFO",
                pass_message: "",
                fail_message: "Incomplete Latency information"
            );
            return false;
        }

        $this->presentCase->pathAdd(
            path: "ServiceDescription $descriptionIndex",
            result: true,
            severity: "PASS",
            pass_message: "Valid ServiceDescription",
            fail_message: ""
        );


        //Optional elements
        $this->presentCase->pathAdd(
            path: "ServiceDescription $descriptionIndex",
            result: $hasLatencyOptional,
            severity: "INFO",
            pass_message: "Optional Latency information available",
            fail_message: "Optional Latency information not available",
        );

        $playbackSpeeds = $serviceDescription->getElementsByTagName('PlaybackSpeed');
        $completePlaybackSpeedInfo = false;
        foreach ($playbackSpeeds as $playbackSpeed) {
            if ($playbackSpeed->getAttribute('max') != '' && $playbackSpeed->getAttribute('min') != '') {
                $completePlaybackSpeedInfo = true;
            }
        }

        $this->presentCase->pathAdd(
            path: "ServiceDescription $descriptionIndex",
            result: $completePlaybackSpeedInfo,
            severity: "INFO",
            pass_message: "Optional PlaybackSpeed information available",
            fail_message: "Optional PlaybackSpeed information not available",
        );

        $scopeElements = $serviceDescription->getElementsByTagName('Scope');
        $this->presentCase->pathAdd(
            path: "ServiceDescription $descriptionIndex",
            result: count($scopeElements) > 0,
            severity: "INFO",
            pass_message: "Optional Scope information available",
            fail_message: "Optional Scope information not available",
        );
        return true;
    }
}
