<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $fontCase;
    private TestCase $attributeCase;
    private TestCase $scanTypeCase;
    private TestCase $frameRateCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->fontCase = $this->v141reporter->add(
            section: "Section 7.2.1.1",
            test: "A fontdownload descriptor SHALL only be placed in AdaptationSets containing subtitles",
            skipReason: "",
        );
        $this->attributeCase = $this->v141reporter->add(
            section: 'Section 4.4',
            test: 'Elements and attributes are expected to be present [..] to enable selection and switching.',
            skipReason: "No video track found"
        );
        $this->scanTypeCase = $this->v141reporter->add(
            section: "Section 4.4",
            test: "@scanType SHALL be present [..] if interlaced pictures are used in any [..representation]",
            skipReason: "No video track found"
        );

        $this->frameRateCase = $this->v141reporter->add(
            section: "Section 11.2.2",
            test: "The frame rates used should be integer multiples of eachother",
            skipReason: "No video track, or a single video track found"
        );
    }

    //Public validation functions
    public function validateVideo(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            foreach ($period->allAdaptationSets() as $adaptationSet) {
                if ($adaptationSet->getAttribute('contentType') != 'video') {
                    continue;
                }
                $this->validateFontProperties($adaptationSet);
                $this->validateAttributePresence($adaptationSet);
                $this->validateFrameRates($adaptationSet);

                //NOTE: We have removed the checks for valid avc, as they require an external specification
            }
        }
    }

    //Private helper functions
    private function validateFontProperties(AdaptationSet $adaptationSet): void
    {
        //TODO Move font checks to validateSubtitles() only!
        $hasDownloadableFont = false;
        foreach ($adaptationSet->getDOMElements('SupplementalProperty') as $propertyElement) {
            if ($this->isFontProperty($propertyElement)) {
                $hasDownloadableFont = true;
            }
        }
        foreach ($adaptationSet->getDOMElements('EssentialProperty') as $propertyElement) {
            if ($this->isFontProperty($propertyElement)) {
                $hasDownloadableFont = true;
            }
        }
        $this->fontCase->pathAdd(
            result: !$hasDownloadableFont,
            severity: "FAIL",
            path: $adaptationSet->path(),
            pass_message: "No downloadable fonts found",
            fail_message: "At least one downloadable font found",
        );
    }

    private function isFontProperty(\DOMElement $propertyElement): bool
    {
        return $propertyElement->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
               $propertyElement->getAttribute('value') == '1';
    }

    private function validateAttributePresence(AdaptationSet $adaptationSet): void
    {
        $resultMessage = "present, or present in all children";

        /** WIDTH **/
        $this->attributeCase->pathAdd(
            result: $adaptationSet->getAttribute('maxWidth') != '' ||
                    $adaptationSet->getAttribute('width') != '',
            severity: "WARN",
            path: $adaptationSet->path(),
            pass_message: "Either @maxWidth or @width found",
            fail_message: "Neither @maxWidth nor @width found",
        );

        $this->attributeCase->pathAdd(
            path: $adaptationSet->path(),
            result: $this->attributeInAdaptationOrAllRepresentations('width', $adaptationSet),
            severity: "FAIL",
            pass_message: "@width $resultMessage",
            fail_message: "Missing at least one @width",
        );

        /** HEIGHT **/
        $this->attributeCase->pathAdd(
            path: $adaptationSet->path(),
            result: $adaptationSet->getAttribute('maxHeight') != '' ||
                    $adaptationSet->getAttribute('height') != '',
            severity: "WARN",
            pass_message: "Either @maxHeight or @height found",
            fail_message: "Neither @maxHeight nor @height found"
        );

        $this->attributeCase->pathAdd(
            path: $adaptationSet->path(),
            result: $this->attributeInAdaptationOrAllRepresentations('height', $adaptationSet),
            severity: "FAIL",
            pass_message: "@height $resultMessage",
            fail_message: "Missing at least one @height",
        );

        /** FRAME RATE **/
        $this->attributeCase->pathAdd(
            path: $adaptationSet->path(),
            result: $adaptationSet->getAttribute('maxFrameRate') != '' ||
                    $adaptationSet->getAttribute('frameRate') != '',
            severity: "WARN",
            pass_message: "Either @maxFrameRate or @frameRate found",
            fail_message: "Neither @maxFrameRate nor @frameRate found",
        );
        $this->attributeCase->pathAdd(
            path: $adaptationSet->path(),
            result: $this->attributeInAdaptationOrAllRepresentations('frameRate', $adaptationSet),
            severity: "FAIL",
            pass_message: "@frameRate $resultMessage",
            fail_message: "Missing at least one @frameRate $resultMessage",
        );

        /** PICTURE ASPECT RATIO **/
        $this->attributeCase->pathAdd(
            path: $adaptationSet->path(),
            result: $adaptationSet->getAttribute('par') != '',
            severity: "WARN",
            pass_message: "@par found",
            fail_message: "@par not found",
        );


        /** SCAN TYPE **/

        $this->validateScanType($adaptationSet);
    }

    private function attributeInAdaptationOrAllRepresentations(string $attribute, AdaptationSet $adaptationSet): bool
    {
        if ($adaptationSet->getAttribute($attribute) != '') {
            return true;
        }

        foreach ($adaptationSet->allRepresentations() as $representation) {
            if ($representation->getAttribute($attribute) == '') {
                return false;
            }
        }
        return true;
    }

    private function validateScanType(AdaptationSet $adaptationSet): void
    {
        $allHaveScanType = true;
        $hasInterlaced = false;

        foreach ($adaptationSet->allRepresentations() as $representation) {
            if ($representation->getAttribute('scanType') == '') {
                $allHaveScanType = false;
            }
            if ($representation->getAttribute('scanType') == 'interlaced') {
                $hasInterlaced = true;
            }
        }

        $this->scanTypeCase->pathAdd(
            path: $adaptationSet->path(),
            result: !$hasInterlaced || $allHaveScanType,
            severity: "FAIL",
            pass_message: "Scan type signalling valid",
            fail_message: "At least one missing scan type",
        );
    }

    private function validateFrameRates(AdaptationSet $adaptationSet): void
    {
        //NOTE: This function depends on behaviour of integer conversion
        //TODO: Make this function correct for fractional notations
        $frameRates = [];
        foreach ($adaptationSet->allRepresentations() as $representation) {
            $frameRate = $representation->getAttribute('frameRate');
            if ($frameRate != '') {
                $frameRates[] = $frameRate;
            }
        }

        $frameRateCount = count($frameRates);

        for ($baseIndex = 0; $baseIndex < $frameRateCount; $baseIndex++) {
            $baseFrameRate = (int)$frameRates[$baseIndex];
            for ($compareIndex = $baseIndex + 1; $compareIndex < $frameRateCount; $compareIndex++) {
                $compareFrameRate = (int)$frameRates[$compareIndex];

                $remainder = (
                    $baseFrameRate > $compareFrameRate ?
                    $baseFrameRate % $compareFrameRate :
                    $compareFrameRate % $baseFrameRate
                );

                $this->frameRateCase->pathAdd(
                    path: $adaptationSet->path(),
                    result: $remainder == 0,
                    severity: "WARN",
                    pass_message: "Indices $baseIndex and $compareIndex are valid",
                    fail_message: "Indices $baseIndex and $compareIndex are not valid",
                );
            }
        }
    }
}
