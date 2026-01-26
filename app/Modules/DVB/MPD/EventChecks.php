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

class EventChecks
{
    //Private subreporters
    private SubReporter $v141reporter;

    private TestCase $presentationTimeCase;
    private TestCase $xmlCase;

    public function __construct()
    {
        $reporter = app(ModuleReporter::class);
        $this->v141reporter = &$reporter->context(new ReporterContext(
            "MPD",
            "DVB",
            "v1.4.1",
            ["document" => "ETSI TS 103 285"]
        ));

        $this->presentationTimeCase = $this->v141reporter->add(
            section: "Section 9.1.2.1",
            test: "Events SHALL have @presentationTime set",
            skipReason: 'No TV Anytime events found'
        );

        $this->xmlCase = $this->v141reporter->add(
            section: "Section 9.1.2.1",
            test: "The event data shall be valid XML, either escaped or place in a CDATA section",
            skipReason: 'No TV Anytime events found'
        );
    }

    //Public validation functions
    public function validateEvents(): void
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

        foreach ($eventStreams as $streamIdx => $eventStream) {
            $this->validateTVAnytimeEventStream($period, $eventStream, $streamIdx);
        }
    }

    private function validateTVAnytimeEventStream(Period $period, \DOMElement $eventStream, int $streamIdx): void
    {
        if (
            $eventStream->getAttribute('schemeIdUri') != 'urn:dvb:iptv:cpm:2014' ||
            $eventStream->getAttribute('value') != '1'
        ) {
            return;
        }

        foreach ($eventStream->getElementsByTagName('Event') as $eventIdx => $event) {
            $this->presentationTimeCase->pathAdd(
                path: $period->path() . "-Event@$streamIdx::$eventIdx",
                result: $event->getAttribute('presentationTime') != '',
                severity: "FAIL",
                pass_message: "Presentation time set",
                fail_message: "Presentation time not set"
            );

            if ($event->nodeValue == '') {
                continue;
            }

            $eventXML = "<doc>" . $event->nodeValue . "</doc>";

            $eventDocument = new \DOMDocument();
            $loadResult = $eventDocument->loadXML($eventXML);

            $this->xmlCase->pathAdd(
                path: $period->path() . "-Event@$streamIdx::$eventIdx",
                result: $loadResult,
                severity: "FAIL",
                pass_message: "Valid XML",
                fail_message: "Invalid XML"
            );

            //TODO: Re-enable check for TVAnytime broadcast messages.
            /*
            $allInternalEventsValid = true;
            foreach ($eventDoccment as $internalEvent) {
                if ($internalEvent->getName() != 'BroadcastEvent') {
                    $allInternalEventsValid = false;
                }
            }

            $this->v141reporter->test(
                "DVB: Section 9.1.2.2",
                "The format of the event payload carrying content programme metadata SHALL be one or more TV-Anytime " .
                "BroadcastEvent elements that form a valid TVAnytime XML document",
                $allInternalEventsValid,
                "FAIL",
                "All xml elements in the metadata have a valid name",
                "At least one invalid metadata element found"
            );
             */
        }
    }
}
