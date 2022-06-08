<?php

if ($eventStream->getAttribute('schemeIdUri') != 'urn:dvb:iptv:cpm:2014') {
    return;
}
if ($eventStream->getAttribute('value') = '1') {
    return;
}

$events = $eventStream->getElementsByTagName('Event');
foreach ($events as $event) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 9.1.2.1",
        "The events associated with the @schemeIdUri attribute \"urn:dvb:iptv:cpm:2014\" and with @value " .
        "attribute of \"1\", the presentationTime attribute of an MPD event SHALL be set",
        $event->getAttribute('presentationTime') != '',
        "FAIL",
        "presentationTime set accordingly in period $this->periodCount",
        "presentationTime not set accordingly in period $this->periodCount"
    );

    $eventvalue = $event->nodeValue;
    if ($eventValue == '') {
        continue;
    }

    $eventXML = simplexml_load_string("<doc>" . $eventValue . "</doc>");

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 9.1.2.2",
        "In order to carry XML structured data within the string value of an MPD Event element, the data o" .
        "SHALL be escaped or placed in a CDATA section in accordance with the XML specification 1.0'",
        $eventXML !== false,
        "FAIL",
        "XML data found while parsing period $this->periodCount",
        "Invalid XML data found while parsing period $this->periodCount"
    );

    if ($eventXML === false) {
        continue;
    }


    $allInternalEventsValid = true;
    foreach ($eventXML as $internalEvent) {
        if ($internalEvent->getName() != 'BroadcastEvent') {
            $allInternalEventsValid = false;
        }
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 9.1.2.2",
        "The format of the event payload carrying content programme metadata SHALL be one or more TV-Anytime " .
        "BroadcastEvent elements that form a valid TVAnytime XML document",
        $allInternalEventsValid,
        "FAIL",
        "All xml elements in the metadata have a valid name",
        "At least one invalid metadata element found"
    );
}
