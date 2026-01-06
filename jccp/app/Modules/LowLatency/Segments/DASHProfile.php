<?php

namespace App\Modules\LowLatency\Segments;

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

class DASHProfile
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $codecCase;
    private TestCase $contentTypeCase;
    private TestCase $mimeTypeCase;
    private TestCase $maxWidthCase;
    private TestCase $maxHeightCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "CrossValidation",
            "LEGACY",
            "Low Latency",
            []
        ));

        //TODO: Extract to different spec and create dependency
        $this->contentTypeCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@contentType shall correspond with the hdlr type",
            skipReason: "No representation found",
        );
        $this->mimeTypeCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@mimeType SHALL be either '<@contentType>/mp4' or <#contentType>/mp4, profiles='cmfc'",
            skipReason: "No representation found",
        );
        $this->maxWidthCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@maxWidth SHOULD correspond to the width in the 'tkhd' box",
            skipReason: "No video representation found",
        );
        $this->maxHeightCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@maxHeight SHOULD correspond to the height in the 'tkhd' box",
            skipReason: "No video representation found",
        );
        $this->codecCase = $this->legacyReporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@codecs shall correspond with the sample descriptor type",
            skipReason: "No representation found",
        );
    }

    //Public validation functions
    public function validateCMAFProfile(Representation $representation, Segment $segment): void
    {
        $this->validateContentType($representation, $segment);
        $this->validateMimeType($representation, $segment);
        $this->validateWidthAndHeight($representation, $segment);
        $this->validateCodecs($representation, $segment);
    }

    //Private helper functions
    private function validateContentType(Representation $representation, Segment $segment): void
    {
        $hdlr = $segment->getHandlerType();
        $contentType = $representation->getTransientAttribute('contentType');

        $contentTypeMatches = false;
        if ($hdlr == "vide" && strpos($contentType, 'video') !== false) {
            $contentTypeMatches = true;
        }
        if ($hdlr == "soun" && strpos($contentType, 'audio') !== false) {
            $contentTypeMatches = true;
        }
        if (($hdlr == "text" || $hdlr == "subt") && strpos($contentType, 'text') !== false) {
            $contentTypeMatches = true;
        }
        $this->contentTypeCase->pathAdd(
            path: $representation->path() . "-init",
            result: $contentTypeMatches,
            severity: "FAIL",
            pass_message: "Matching content type",
            fail_message: "Mismatched content type",
        );
    }

    private function validateMimeType(Representation $representation, Segment $segment): void
    {
        $contentType = $representation->getTransientAttribute('contentType');
        $mimeType = $representation->getTransientAttribute('mimeType');

        $matches = ($mimeType == $contentType . "/mp4" || $mimeType == $contentType . "/mp4, profiles='cmfc'");

        $this->mimeTypeCase->pathAdd(
            path: $representation->path() . "-init",
            result: $matches,
            severity: "FAIL",
            pass_message: "Matching mime type",
            fail_message: "Mismatched mime type",
        );
    }

    private function validateWidthAndHeight(Representation $representation, Segment $segment): void {
        if ($segment->getHandlerType() != 'vide'){
            return;
        }

        $segmentWidth = $segment->getWidth();
        $segmentHeight = $segment->getHeight();

        $this->maxWidthCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segmentWidth == $representation->getTransientAttribute('maxWidth'),
            severity: "WARN",
            pass_message: "Matching maxWidth",
            fail_message: "Mismatched maxWidth",
        );
        $this->maxHeightCase->pathAdd(
            path: $representation->path() . "-init",
            result: $segmentHeight == $representation->getTransientAttribute('maxHeight'),
            severity: "WARN",
            pass_message: "Matching maxHeight",
            fail_message: "Mismatched maxHeight",
        );
    }

    private function validateCodecs(Representation $representation, Segment $segment): void {
        $sdType = $segment->getSampleDescriptor();
        $this->codecCase->pathAdd(
            path: $representation->path() . "-init",
            result: strpos($representation->getTransientAttribute('codecs'), $sdType) !== false,
            severity: "FAIL",
            pass_message: "Matching codecs",
            fail_message: "Mismatched codecs",
        );

    }
}
