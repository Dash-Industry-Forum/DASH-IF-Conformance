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

class TrackRoles
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.7.2 - Carriage of Track Role';

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
    public function validateTrackRoles(Representation $representation, Segment $segment): void
    {
        $roles = [];
        foreach ($representation->getTransientDOMElements('Role') as $roleElement) {
            $roles[] = [
                'scheme' => $roleElement->getAttribute('schemeIdUri'),
                'value' => $roleElement->getAttribute('value'),
            ];
        }

        $kindBoxes = $segment->getKindBoxes();

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

            $this->waveReporter->test(
                section: $this->section,
                test: "Track roles SHALL be stored in one or more 'kind' boxes [..]",
                result: $foundInMPD,
                severity: "FAIL",
                pass_message: $representation->path() . " - Kind box with scheme " .
                          $kindBox->schemeURI . " reflected in MPD",
                fail_message: $representation->path() . " - Kind box with scheme " .
                          $kindBox->schemeURI . " not reflected in MPD",
            );

            $this->waveReporter->test(
                section: $this->section,
                test: "Track roles SHALL be representaed by the DASH Role scheme when possible",
                result: $kindBox->schemeURI == "urn:mpeg:dash:role:2011",
                severity: "WARN",
                pass_message: $representation->path() . " - Kind box with scheme " .
                              $kindBox->schemeURI . " has expected scheme",
                fail_message: $representation->path() . " - Kind box with scheme " .
                              $kindBox->schemeURI . " does not have the expected scheme",
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

            $this->waveReporter->test(
                section: $this->section,
                test: "Track roles SHALL be stored in one or more 'kind' boxes [..]",
                result: $foundInKind,
                severity: "FAIL",
                pass_message: $representation->path() . " - MPD Role with scheme " .
                          $mpdRole['scheme'] . " reflected in a 'kind' box",
                fail_message: $representation->path() . " - MPD Role with scheme " .
                          $mpdRole['scheme'] . " not reflected in a 'kind' box",
            );
        }
    }

    //Private helper functions
}
