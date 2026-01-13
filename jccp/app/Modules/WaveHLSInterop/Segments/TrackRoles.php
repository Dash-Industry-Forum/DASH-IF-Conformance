<?php

namespace App\Modules\WaveHLSInterop\Segments;

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

class TrackRoles
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.7.2 - Carriage of Track Role';

    private TestCase $kindCase;
    private TestCase $roleNameCase;

    public function __construct()
    {
        $this->registerChecks();
    }

    private function registerChecks(): void
    {
        $reporter = app(ModuleReporter::class);
        $this->waveReporter = &$reporter->context(new ReporterContext(
            "Segments",
            "CTA-5005-A",
            "Final",
            []
        ));

        $this->kindCase = $this->waveReporter->add(
            section: $this->section,
            test: "Track roles SHALL be stored in one or more 'kind' boxes [..]",
            skipReason: "No track role found in MPD and no 'kind' boxes found"
        );
        $this->roleNameCase = $this->waveReporter->add(
            section: $this->section,
            test: "Track roles SHALL be representaed by the DASH Role scheme when possible",
            skipReason: "No 'kind' boxes found"
        );
    }

    //Public validation functions
    public function validateTrackRoles(Representation $representation, Segment $segment): void
    {
        $roles = [];
        foreach ($representation->getTransientDOMElements('Role') as $roleElement) {
            $roles[] = [
                'scheme' => $roleElement->getAttribute('schemeIdUri'),
                'value' => $roleElement->getAttribute('value'),
            ];
        }

        $kindBoxes = $segment->boxAccess()->kind();

        foreach ($kindBoxes as $kindBox) {
            $foundInMPD = false;
            foreach ($roles as $mpdRole) {
                if (
                    $mpdRole['scheme'] == $kindBox->schemeURI &&
                    $mpdRole['value'] == $kindBox->value
                ) {
                    $foundInMPD = true;
                    break;
                }
            }

            $this->kindCase->pathAdd(
                result: $foundInMPD,
                severity: "FAIL",
                path: $representation->path() . "-init",
                pass_message: "Box with scheme $kindBox->schemeURI reflected in MPD",
                fail_message: "Box with scheme $kindBox->schemeURI not reflected in MPD",
            );

            $this->roleNameCase->pathAdd(
                result: $kindBox->schemeURI == "urn:mpeg:dash:role:2011",
                severity: "WARN",
                path: $representation->path() . "-init",
                pass_message: "Box with scheme $kindBox->schemeURI has expected scheme",
                fail_message: "Box with scheme $kindBox->schemeURI does not have the expected scheme",
            );
        }

        foreach ($roles as $mpdRole) {
            $foundInKind = false;
            foreach ($kindBoxes as $kindBox) {
                if (
                    $mpdRole['scheme'] == $kindBox->schemeURI &&
                    $mpdRole['value'] == $kindBox->value
                ) {
                    $foundInKind = true;
                    break;
                }
            }

            $this->kindCase->pathAdd(
                result: $foundInKind,
                severity: "FAIL",
                path: $representation->path() . "-init",
                pass_message: "MPD Role with scheme " . $mpdRole['scheme'] . " reflected in a 'kind' box",
                fail_message: "MPD Role with scheme " . $mpdRole['scheme'] . " not reflected in a 'kind' box",
            );
        }
    }

    //Private helper functions
}
