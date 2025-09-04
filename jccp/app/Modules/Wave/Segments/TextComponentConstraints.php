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

class TextComponentConstraints
{
    //Private subreporters
    private SubReporter $waveReporter;

    private string $section = '4.1.2 - Basic On-Demand and Live Streaming';
    private string $explanation = 'Text components SHALL be packaged in ISMC1, ISMC1.1 or WebVTT Tracks';

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
    public function validateTextComponentConstraints(Representation $representation, Segment $segment): void
    {
        $sampleDescription = $segment->getSampleDescription();

        if (
            $sampleDescription &&
            $sampleDescription->type == Boxes\DescriptionType::Subtitle &&
            $sampleDescription instanceof Boxes\STPPBox
        ) {
            $this->validateISMC($representation, $segment, $sampleDescription);
        }

        if ($sampleDescription &&  $sampleDescription->type == Boxes\DescriptionType::Text) {
            $this->validateWebvtt($representation, $segment, $sampleDescription);
        }
    }

    //Private helper functions
    private function validateISMC(
        Representation $representation,
        Segment $segment,
        Boxes\STPPBox $sampleDescription
    ): void {
        $ismcNamespace = str_contains($sampleDescription->namespace, 'http://www.w3.org/ns/ttml');
        $this->waveReporter->test(
            section: $this->section,
            test: $this->explanation,
            result: $ismcNamespace,
            severity: "FAIL",
            pass_message: $representation->path() . " - Has ismc namespace",
            fail_message: $representation->path() . " - Invalid namespace : " . $sampleDescription->namespace,
        );

        $ismc1Location =  str_contains(
            $sampleDescription->schemaLocation,
            'http://www.w3.org/ns/ttml/profile/ismc1/text'
        );
        $ismc11Location =  str_contains(
            $sampleDescription->schemaLocation,
            'http://www.w3.org/ns/ttml/profile/ismc1.1/text'
        );
        $this->waveReporter->test(
            section: $this->section,
            test: $this->explanation,
            result: $ismc1Location || $ismc11Location,
            severity: "FAIL",
            pass_message: $representation->path() . " - Has ISMC1 or ISMC1.1 Schema location",
            fail_message: $representation->path() . " - Invalid schema location : " .
                          $sampleDescription->schemaLocation,
        );

        $mimeTypeTTML = str_contains($sampleDescription->auxiliaryMimeTypes, 'application/ttml+xml');
        $hasCodecs = str_contains($sampleDescription->auxiliaryMimeTypes, ';codecs=');
        $isIm1t = $ismc1Location &&
              $hasCodecs &&
              str_contains($sampleDescription->auxiliaryMimeTypes, 'im1t');
        $isIm2t = $ismc11Location &&
              $hasCodecs &&
              str_contains($sampleDescription->auxiliaryMimeTypes, 'im2t');
        $this->waveReporter->test(
            section: $this->section,
            test: $this->explanation,
            result:  $mimeTypeTTML && ($isIm1t || $isIm2t),
            severity: "FAIL",
            pass_message: $representation->path() . " - With allowed mimetype",
            fail_message: $representation->path() . " - Invalid mimetype: " .
                          $sampleDescription->auxiliaryMimeTypes,
        );
    }

    private function validateWebvtt(
        Representation $representation,
        Segment $segment,
        Boxes\SampleDescription $sampleDescription
    ): void {
        $this->waveReporter->test(
            section: $this->section,
            test: $this->explanation,
            result:  $sampleDescription->codec == 'wvtt',
            severity: "FAIL",
            pass_message: $representation->path() . " - WebVTT track detected",
            fail_message: $representation->path() . " - Textt rack detected, but signalled as " .
                          $sampleDescription->codec,
        );
    }
}
