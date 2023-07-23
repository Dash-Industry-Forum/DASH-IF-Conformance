<?php

global $logger;

$protectionCount = 0;
$defaultKIDs = array();
$contentProtection = $adaptation->getElementsByTagName('ContentProtection');
foreach ($contentProtection as $protection) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 8.3",
        "ContentProtection descriptor SHALL be placed at he AdaptationSet level",
        $protection->parentNode->nodeName == 'AdaptationSet',
        "FAIL",
        "Protection element found for $this->periodCount",
        "Protection element found at wrong location for $this->periodCount"
    );
    if ($protection->parentNode->nodeName == 'AdaptationSet') {
        $protectionCount++;
        $defaultKIDs[] = $protection->getAttribute('cenc:default_KID');
    }
}

$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "DVB: Section 8.4",
    "Any Adaptation Set containing protected content SHALL contain one \"mp4protection\" ContentProtection " .
    "descriptor with @schemeIdUri=\"urn:mped:dash:mp4protection:2011\" and @value=\"cenc\"",
    count($contentProtection) == 0 || $protectionCount > 0,
    "FAIL",
    "Non protected content or protected content with at least one correct ContentProtection descriptor in $this->periodCount, adaptation $i",
    "Not found in $this->periodCount, adaptation $i"
);
if (!empty($contentProtection) && $protectionCount == 0) {
    $logger->test(
        "HbbTV-DVB DASH Validation Requirements",
        "DVB: Section 8.4",
        "\"mp4protection\" ContentProtection descriptor SHOULD include the extension defined in ISO/IEC 23001-7 " .
        "clause 11.2",
        $cenc != '' && !empty($defaultKIDs),
        "WARN",
        "Found at least one element in $this->periodCount, adaptation $i",
        "Not found in $this->periodCount, adaptation $i"
    );
}
