<?php

namespace App\Modules\IOP\MPD;

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

class Common
{
    //Private subreporters
    private SubReporter $legacyReporter;

    private TestCase $yearMonthCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->legacyReporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DASH-IF IOP",
            "4.3",
            []
        ));

        $this->yearMonthCase = $this->legacyReporter->add(
            section: '3.2.7.4',
            test: 'MPD fields having datatype xs:duration shall not use year or month units',
            skipReason: "Corresponding profile(s) not signalled",
        );
    }

    //Public validation functions
    public function validateCommon(): void
    {
        $mpdCache = app(MPDCache::class);

        if (!$mpdCache->hasProfile('http://dashif.org/guidelines/dash')) {
            return;
        }

        $this->validateMPDFields($mpdCache);

        foreach ($mpdCache->allPeriods() as $period) {
            $this->validateValue($period->path() . "@start", $period->getAttribute('start'));
            $this->validateValue($period->path() . "@duration", $period->getAttribute('duration'));
        }

        foreach ($mpdCache->getDOMElements('RandomAccess') as $element) {
            $this->validateValue(
                "RandomAccess@minBufferTime",
                $element->getAttribute('minBufferTime')
            );
        }

        foreach ($mpdCache->getDOMElements('SegmentTemplate') as $element) {
            $this->validateValue(
                "SegmentTemplate@timeShiftBufferDepth",
                $element->getAttribute('timeShiftBufferDepth')
            );
        }
        foreach ($mpdCache->getDOMElements('SegmentBase') as $element) {
            $this->validateValue(
                "SegmentBase@timeShiftBufferDepth",
                $element->getAttribute('timeShiftBufferDepth')
            );
        }
        foreach ($mpdCache->getDOMElements('SegmentList') as $element) {
            $this->validateValue(
                "SegmentList@timeShiftBufferDepth",
                $element->getAttribute('timeShiftBufferDepth')
            );
        }

        foreach ($mpdCache->getDOMElements('Range') as $element) {
            $this->validateValue("Range@start", $element->getAttribute('start'));
            $this->validateValue("Range@duration", $element->getAttribute('duration'));
        }
    }

    //Private helper functions
    private function validateMPDFields(MPDCache $mpdCache): void
    {
        $mpdFields = [
            'mediaPresentationDuration',
            'minimumUpdatePeriod',
            'minBufferTime',
            'timeShiftBufferDepth',
            'suggestedPresentationDelay',
            'maxSegmentDuration',
            'maxSubSegmentDuration',
        ];

        foreach ($mpdFields as $field) {
            $this->validateValue("MPD@${field}", $mpdCache->getAttribute($field));
        }
    }

    private function validateValue(string $location, string $value): void
    {
        if ($value == '') {
            return;
        }
        $yPos = strpos($value, 'Y');
        $mPos = strpos($value, 'M');
        $tPos = strpos($value, 'T');

        //Note: The previous implementation also used to consider values of 0 as valid,
        //but the statement suggest this is the correct implementation.
        $valid = ($yPos === false && ($mPos === false || $mPos > $tPos));

        $this->yearMonthCase->pathAdd(
            path: $location,
            result: $valid,
            severity: "FAIL",
            pass_message: "Valid",
            fail_message: "Invalid",
        );
    }
}
