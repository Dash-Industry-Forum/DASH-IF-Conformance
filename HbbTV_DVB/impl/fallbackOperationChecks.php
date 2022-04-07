<?php

$adaptationCount = sizeof($audioAdaptations);
for ($i = 0; $i < $len; $i++) {
    $adaptation = $audioAdaptations[$i];
    $supplementalProperties = $adaptation->getElementsByTagName('SupplementalProperty');

    $value = "";
    foreach ($supplementalProperties as $property) {
        if ($property->getAttribute('schemeIdUri') == 'urn:dvb:dash:fallback_adaptation_set:2014') {
            $value = $property->getAttribute('value');
        }
    }

    if ($value == '') {
        continue;
    }

    $infoString = '';

    for ($j = 0; $j < $len; $j++) {
        if ($j == $i) {
            continue;
        }
        $adaptation2 = $audioAdaptations[$j];
        if ($value == $adaptation2->getAttribute('id')) {
            $infoString .= 'yes ';
        }
    }

    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 6.6.3",
        "The (SupplementalProperty) descriptor SHALL have the @schemeIdUri attibute set to " .
        "\"urn:dvb:dash:fallback_adaptation_set:2014\" and the @value attribute equal to the " .
        "@id attribute of the Adaptation Set for which it supports the falling back operation",
        $infoString != '',
        "FAIL",
        "Fallback signalled and valid in period $this->periodCount",
        "Fallback signalled but corresponding id not found in period $this->periodCount",
    );
    if ($infoString == '') {
        continue;
    }
    ///\Discuss Scoping issues and check not doing what it should do?
    $firstRole = $adaptation->getElementsByTagName('Role')->item(0);
    $secondRole = $adaptation2->getElementsByTagName('Role')->item(0);

    $schemeMatches = ($firstRole->getAttribute('schemeIdUri') == $secondRole->getAttribute('schemeIdUri'));
    $valueMatches = ($firstRole->getAttribute('schemeIdUri') == $secondRole->getAttribute('schemeIdUri'));
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 6.6.3",
        "An additional low bit rate fallback Adaptation Set SHALL also be tagged with the same role as the " .
        "Adaptation Set which it provides the fallback option for",
        $schemeMatches && $valueMatches,
        "FAIL",
        "Roles match in period $this->periodCount",
        "Roles don't match in period $this->periodCount"
    );
}
