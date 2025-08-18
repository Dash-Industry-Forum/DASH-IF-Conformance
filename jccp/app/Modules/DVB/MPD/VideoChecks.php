<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\Manifest\AdaptationSet;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VideoChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));
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
        $this->v141reporter->test(
            section: "Section 7.2.1.1",
            test: "A fontdownload descriptor SHALL only be placed in AdaptationSets containing subtitles",
            result: !$hasDownloadableFont,
            severity: "FAIL",
            pass_message: "No downloadable fonts found for AdaptationSet " . $adaptationSet->path(),
            fail_message: "At least one downloadable font found for AdaptationSet " . $adaptationSet->path(),
        );
    }

    private function isFontProperty(\DOMElement $propertyElement): bool
    {
        return $propertyElement->getAttribute('schemeIdUri') == 'urn:dvb:dash:fontdownload:2014' &&
               $propertyElement->getAttribute('value') == '1';
    }

    private function validateAttributePresence(AdaptationSet $adaptationSet): void
    {
        $section = 'Section 4.4';
        $test = 'Elements and attributes are expected to be present for certain Adaptation Sets and Representations ' .
                'to enable suitable initial selection and switching.';
        $resultMessage = "in AdaptationSet or all Representations for AdaptationSet " . $adaptationSet->path();

        /** WIDTH **/
        $this->v141reporter->test(
            section: $section,
            test: $test,
            result: $adaptationSet->getAttribute('maxWidth') != '' ||
                    $adaptationSet->getAttribute('width') != '',
            severity: "WARN",
            pass_message: "Either @maxWidth or @width found for AdaptationSet " . $adaptationSet->path(),
            fail_message: "Neither @maxWidth nor @width found for AdaptationSet " . $adaptationSet->path(),
        );

        $this->v141reporter->test(
            section: $section,
            test: $test,
            result: $this->attributeInAdaptationOrAllRepresentations('width', $adaptationSet),
            severity: "FAIL",
            pass_message: "@width $resultMessage",
            fail_message: "Missing at least one @width $resultMessage",
        );

        /** HEIGHT **/
        $this->v141reporter->test(
            section: $section,
            test: $test,
            result: $adaptationSet->getAttribute('maxHeight') != '' ||
                    $adaptationSet->getAttribute('height') != '',
            severity: "WARN",
            pass_message: "Either @maxHeight or @height found for AdaptationSet " . $adaptationSet->path(),
            fail_message: "Neither @maxHeight nor @height found for AdaptationSet " . $adaptationSet->path(),
        );

        $this->v141reporter->test(
            section: $section,
            test: $test,
            result: $this->attributeInAdaptationOrAllRepresentations('height', $adaptationSet),
            severity: "FAIL",
            pass_message: "@height $resultMessage",
            fail_message: "Missing at least one @height $resultMessage",
        );

        /** FRAME RATE **/
        $this->v141reporter->test(
            section: $section,
            test: $test,
            result: $adaptationSet->getAttribute('maxFrameRate') != '' ||
                    $adaptationSet->getAttribute('frameRate') != '',
            severity: "WARN",
            pass_message: "Either @maxFrameRate or @frameRate found for AdaptationSet " . $adaptationSet->path(),
            fail_message: "Neither @maxFrameRate nor @frameRate found for AdaptationSet " . $adaptationSet->path(),
        );
        $this->v141reporter->test(
            section: $section,
            test: $test,
            result: $this->attributeInAdaptationOrAllRepresentations('frameRate', $adaptationSet),
            severity: "FAIL",
            pass_message: "@frameRate $resultMessage",
            fail_message: "Missing at least one @frameRate $resultMessage",
        );

        /** PICTURE ASPECT RATIO **/
        $this->v141reporter->test(
            section: $section,
            test: $test,
            result: $adaptationSet->getAttribute('par') != '',
            severity: "WARN",
            pass_message: "@par found for AdaptationSet " . $adaptationSet->path(),
            fail_message: "@par not found for AdaptationSet " . $adaptationSet->path(),
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

        $this->v141reporter->test(
            section: "Section 4.4",
            test: "@scanType SHALL be present in all Representations, if interlaced pictures are used in any of them",
            result: !$hasInterlaced || $allHaveScanType,
            severity: "FAIL",
            pass_message: "Scan types valid for AdaptationSet " . $adaptationSet->path(),
            fail_message: "At least one missing scan type for AdaptationSet " . $adaptationSet->path(),
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

                $this->v141reporter->test(
                    section: "Section 11.2.2",
                    test: "The frame rates used should be integer multiples of eachother",
                    result: $remainder == 0,
                    severity: "WARN",
                    pass_message: "Exact multiples at indexes $baseIndex and $compareIndex are for AdaptationSet " .
                          $adaptationSet->path(),
                    fail_message: "No exact multiples at indexes $baseIndex and $compareIndex are for AdaptationSet " .
                          $adaptationSet->path(),
                );
            }
        }
    }
}
