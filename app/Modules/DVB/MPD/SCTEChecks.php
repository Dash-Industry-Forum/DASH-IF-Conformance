<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\TestCase;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SCTEChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $valueCase;
    private TestCase $presentationTimeOffsetCase;
    private TestCase $timeScaleCase;
    private TestCase $eventPresentationTimeCase;
    private TestCase $eventDurationCase;
    private TestCase $eventMessageDataCase;
    private TestCase $eventContentEncodingCase;

    private int $frameRate = 0;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->valueCase = $this->v141reporter->add(
            section: "Section 9.1.9",
            test: "The @value attribute SHOULD be absent",
            skipReason: 'No SCTE-35 event stream found'
        );
        $this->presentationTimeOffsetCase = $this->v141reporter->add(
            section: "Section 9.1.9",
            test: "The @presentationTimeOffset attribute MAY be present",
            skipReason: 'No SCTE-35 event stream found'
        );
        $this->timeScaleCase = $this->v141reporter->add(
            section: "Section 9.1.9",
            test: "The @timeScale attribute shall be set to an integer multiple of the video framerate",
            skipReason: 'No SCTE-35 event stream found'
        );
        $this->eventPresentationTimeCase = $this->v141reporter->add(
            section: "Section 9.1.9",
            test: "The @presentationTime of an event SHALL be set to the splice time of the enclosed marker",
            skipReason: 'No SCTE-35 event stream found'
        );
        $this->eventDurationCase = $this->v141reporter->add(
            section: "Section 9.1.9",
            test: "The @duration of an event SHALL be defined [..] and SHALL NOT be 0xFFFFFFFF",
            skipReason: 'No SCTE-35 event stream found'
        );
        $this->eventMessageDataCase = $this->v141reporter->add(
            section: "Section 9.1.9",
            test: "The @messageData of an event SHALL be absent",
            skipReason: 'No SCTE-35 event stream found'
        );
        $this->eventContentEncodingCase = $this->v141reporter->add(
            section: "Section 9.1.9",
            test: "The @contentEncoding of an event is absent",
            skipReason: 'No SCTE-35 event stream found'
        );
    }

    //Public validation functions
    public function validateSCTE(): void
    {
        $mpdCache = app(MPDCache::class);
        foreach ($mpdCache->allPeriods() as $period) {
            $this->validateSinglePeriod($period);
        }
    }

    //Private helper functions
    private function validateSinglePeriod(Period $period): void
    {
        $eventStreams = $period->getDOMElements('EventStream');
        if (!$eventStreams) {
            return;
        }

        foreach ($period->allAdaptationSets() as $adaptationSet) {
            $adaptationSetFrameRate = $adaptationSet->getAttribute('frameRate');
            if ($adaptationSetFrameRate != '') {
                $this->frameRate = intval($adaptationSetFrameRate);
            }
        }

        foreach ($eventStreams as $streamIdx => $eventStream) {
            if ($eventStream->getAttribute('schemeIdUri') != 'urn:scte:scte35:2014:xml+bin') {
                continue;
            }

            $this->validateSCTEEventStream($period, $eventStream, $streamIdx);
        }
    }

    private function validateSCTEEventStream(Period $period, \DOMElement $eventStream, int $streamIdx): void
    {
        $this->valueCase->pathAdd(
            path: $period->path() . "-EventStream@$streamIdx",
            result: !$eventStream->hasAttribute('value'),
            severity: "WARN",
            pass_message: "No @value attribute",
            fail_message: "Contains @value attribute"
        );

        $this->presentationTimeOffsetCase->pathAdd(
            path: $period->path() . "-EventStream@$streamIdx",
            result: $eventStream->hasAttribute('presentationTimeOffset'),
            severity: "INFO",
            pass_message: "Contains @presentationTimeOffset attribute",
            fail_message: "Does not contain @presentationTimeOffset attribute"
        );

        $timeScaleValid = ($eventStream->getAttribute('timescale') != '');
        if ($timeScaleValid && $this->frameRate != 0) {
            $divResult = intval($eventStream->getAttribute('timescale')) / $this->frameRate;
            $timeScaleValid = is_int($divResult);
        }

        $this->timeScaleCase->pathAdd(
            path: $period->path() . "-EventStream@$streamIdx",
            result: $timeScaleValid,
            severity: "FAIL",
            pass_message: "@timeScale multiple of found frameRate value",
            fail_message: "@timeScale missing or non-multiple of found frameRate value"
        );

        if ($timeScaleValid && $this->frameRate == 0) {
            $this->timeScaleCase->pathAdd(
                path: $period->path() . "-Event@$streamIdx",
                result: true,
                severity: "INFO",
                pass_message: "No frameRate found to compare against",
                fail_message: ""
            );
        }

        foreach ($eventStream->getElementsByTagName('Event') as $eventIdx => $event) {
            $this->validateSCTEEvent($period, $streamIdx, $event, $eventIdx);
        }
    }

    private function validateSCTEEvent(Period $period, int $streamIdx, \DOMElement $event, int $eventIdx): void
    {
        $this->eventPresentationTimeCase->pathAdd(
            path: $period->path() . "-Event@$streamIdx-Event$eventIdx",
            result: $event->getAttribute('presentationTime') != '',
            severity: "FAIL",
            pass_message: "@presentationTime attribute found",
            fail_message: "@presentationTime attribute not found",
        );

        $this->eventDurationCase->pathAdd(
            path: $period->path() . "-Event@$streamIdx-Event$eventIdx",
            //Made this check a concatenated value as hex values are no longer dicectly allowed in a string
            result: $event->getAttribute('duration') != '' && $event->getAttribute('duration') != '0x' . 'FFFFFFFF',
            severity: "FAIL",
            pass_message: "Valid @duration attribute found",
            fail_message: "No or indefinite @duration attribute found",
        );

        $this->eventMessageDataCase->pathAdd(
            path: $period->path() . "-Event@$streamIdx-Event$eventIdx",
            result: !$event->hasAttribute('messageData'),
            severity: "FAIL",
            pass_message: "@messageData attribute is absent",
            fail_message: "Found @messageData attribute",
        );

        $this->eventContentEncodingCase->pathAdd(
            path: $period->path() . "-Event@$streamIdx-Event$eventIdx",
            result: !$event->hasAttribute('contentEncoding'),
            severity: "FAIL",
            pass_message: "@contentEncoding attribute is absent",
            fail_message: "Found @contentEncoding attribute",
        );
    }
}
