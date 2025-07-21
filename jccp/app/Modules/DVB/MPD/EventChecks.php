<?php

namespace App\Modules\DVB\MPD;

use App\Services\MPDCache;
use App\Services\Manifest\Period;
use App\Services\ModuleReporter;
use App\Services\Reporter\SubReporter;
use App\Services\Reporter\Context as ReporterContext;
use App\Interfaces\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EventChecks
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

        foreach ($eventStreams as $eventStream) {
            $this->validateTVAnytimeEventStream($period, $eventStream);
        }
    }

    private function validateTVAnytimeEventStream(Period $period, \DOMElement $eventStream): void
    {
        if (
            $eventStream->getAttribute('schemeIdUri') != 'urn:dvb:iptv:cpm:2014' ||
            $eventStream->getAttribute('value') != '1'
        ) {
            return;
        }

        foreach ($eventStream->getElementsByTagName('Event') as $eventIdx => $event) {
            $this->v141reporter->test(
                section: "Section 9.1.2.1",
                test: "Events associated with [this stream] SHALL have @presentationTime set",
                result: $event->getAttribute('presentationTime') != '',
                severity: "FAIL",
                pass_message: "Presentation time for event $eventIdx is set in Period " . $period->path(),
                fail_message: "Presentation time for event $eventIdx not set in Period " . $period->path(),
            );

            if ($event->nodeValue == '') {
                continue;
            }

            $eventXML = "<doc>" . $event->nodeValue . "</doc>";

            $eventDocument = new \DOMDocument();
            $loadResult = $eventDocument->loadXML($eventXML);

            $this->v141reporter->test(
                section: "Section 9.1.2.1",
                test: "In order to carry XML structured data within the string value of an MPD Event element, " .
                "the data shall be escaped or placed in a CDATA section in accordance with the XML specification 1.0",
                result: $loadResult,
                severity: "FAIL",
                pass_message: "Parsed valid XML for event $eventIdx in Period " . $period->path(),
                fail_message: "Invalid XML for event $eventIdx in Period " . $period->path(),
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
