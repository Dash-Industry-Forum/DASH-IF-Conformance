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
use App\Interfaces\ModuleComponents\InitSegmentComponent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DASHProfile extends InitSegmentComponent
{
    private TestCase $codecCase;
    private TestCase $contentTypeCase;
    private TestCase $mimeTypeCase;
    private TestCase $maxWidthCase;
    private TestCase $maxHeightCase;
    private TestCase $encryptionCase;
    private TestCase $timescaleCase;
    private TestCase $alignmentCase;
    private TestCase $eventCase;

    public function __construct()
    {
        parent::__construct(
            self::class,
            new ReporterContext(
                "CrossValidation",
                "LEGACY",
                "Low Latency",
                []
            )
        );

        //TODO: Extract to different spec and create dependency
        $this->contentTypeCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@contentType shall correspond with the hdlr type",
            skipReason: "No representation found",
        );
        $this->mimeTypeCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@mimeType SHALL be either '<@contentType>/mp4' or <#contentType>/mp4, profiles='cmfc'",
            skipReason: "No representation found",
        );
        $this->maxWidthCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@maxWidth SHOULD correspond to the width in the 'tkhd' box",
            skipReason: "No video representation found",
        );
        $this->maxHeightCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@maxHeight SHOULD correspond to the height in the 'tkhd' box",
            skipReason: "No video representation found",
        );
        $this->codecCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@codecs shall correspond with the sample descriptor type",
            skipReason: "No representation found",
        );
        $this->encryptionCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "If the content is protected, a ContentProtection element SHALL be present and set appropriately",
            skipReason: "No protection found",
        );
        $this->timescaleCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "@timescale SHALL correspond to the timescale in the 'mdhd' box",
            skipReason: "No representation found",
        );
        $this->alignmentCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.4',
            test: "Either segmentAlignment or subsegmentAlignment SHALL be set",
            skipReason: "No representation found",
        );
        $this->eventCase = $this->reporter->add(
            section: '9.X.4.5 => MPEG-DASH 8.X.3',
            test: "Event message streams MAY be signalled with InbandEventStream elements",
            skipReason: "No representation found",
        );
    }

    //Public validation functions
    public function validateInitSegment(Representation $representation, Segment $segment): void
    {
        $this->validateContentType($representation, $segment);
        $this->validateMimeType($representation, $segment);
        $this->validateWidthAndHeight($representation, $segment);
        $this->validateCodecs($representation, $segment);
        $this->validateProtection($representation, $segment);
        $this->validateTimescale($representation, $segment);
        $this->validateAlignment($representation, $segment);
        $this->validateEventMessages($representation, $segment);
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

    private function validateWidthAndHeight(Representation $representation, Segment $segment): void
    {
        if ($segment->getHandlerType() != 'vide') {
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

    private function validateCodecs(Representation $representation, Segment $segment): void
    {
        $sdType = $segment->getSampleDescriptor();
        $this->codecCase->pathAdd(
            path: $representation->path() . "-init",
            result: strpos($representation->getTransientAttribute('codecs'), $sdType) !== false,
            severity: "FAIL",
            pass_message: "Matching codecs",
            fail_message: "Mismatched codecs",
        );
    }

    private function validateProtection(Representation $representation, Segment $segment): void
    {
        $protectionScheme = $segment->getProtectionScheme();
        if (!$protectionScheme) {
            return;
        }

        $segmentKID = $protectionScheme->encryption->kid;

        $representationProtection = $representation->getTransientDOMElements('ContentProtection');
        if (empty($representationProtection)) {
            $this->encryptionCase->pathAdd(
                path: $representation->path() . "-init",
                result: false,
                severity: "FAIL",
                pass_message: "",
                fail_message: "Content protection in segment, but not in mpd",
            );
            return;
        }


        $validContentProtection = false;

        foreach ($representationProtection as $protection) {
            $scheme = $protection->getAttribute('schemeIdUri');
            $value = $protection->getAttribute('value');

            $mpdKID = $protection->getAttribute('cenc:default_KID');

            if ($scheme == "urn:mpeg:dash:mp4protection:2011") {
                if ($value != 'cenc' && $value != 'cbcs') {
                    continue;
                }
                if ($mpdKID == '' || $mpdKID == $segmentKID) {
                    $validContentProtection = true;
                    break;
                }
            } else {
                $mpdCenc = $protection->getAttribute('cenc:pssh');
                $psshBoxes = $segment->boxAccess()->pssh();

                $matchingKID = ($mpdKID == '' || $mpdKID == $segmentKID);
                $matchingPSSH = ($mpdCenc == '' || (!empty($psshBoxes) && $psshBoxes[0]->systemId == $mpdCenc));

                if ($matchingKID && $matchingPSSH) {
                    $validContentProtection = true;
                    break;
                }
            }
        }

        $this->encryptionCase->pathAdd(
            path: $representation->path() . "-init",
            result: $validContentProtection,
            severity: "FAIL",
            pass_message: "Valid content protection found",
            fail_message: "Content protection in segment does not match any in MPD",
        );
    }

    private function validateTimescale(Representation $representation, Segment $segment): void
    {
        $mpdTimescale = $representation->getTransientAttribute('timescale');
        $segmentTimescale = $segment->getTimescale();

        $this->timescaleCase->pathAdd(
            path: $representation->path() . "-init",
            result: $mpdTimescale == $segmentTimescale,
            severity: "FAIL",
            pass_message: "Matching timescale",
            fail_message: "Mismatched timescale",
        );
    }

    private function validateAlignment(Representation $representation, Segment $segment): void
    {
        $this->alignmentCase->pathAdd(
            path: $representation->path() . "-init",
            result: $representation->getTransientAttribute('segmentAlignment') != '' ||
                    $representation->getTransientAttribute('subsegmentAlignment') != '',
            severity: "FAIL",
            pass_message: "Signalling found",
            fail_message: "Signalling not found",
        );
    }

    private function validateEventMessages(Representation $representation, Segment $segment): void
    {
        $this->eventCase->pathAdd(
            path: $representation->path() . "-init",
            result: !empty($representation->getTransientDOMElements('InbandEventStream')),
            severity: "INFO",
            pass_message: "Event message stream singnalled",
            fail_message: "Event message stream not singnalled",
        );
    }
}
