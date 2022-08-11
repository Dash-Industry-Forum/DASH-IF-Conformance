<?php

global $logger;

$representationCount = count($files);


$logger->test(
    "HbbTV-DVB DASH Validation Requirements",
    "Section 'Init Segment(s)'",
    "Informative",
    true,
    "PASS",
    "There are " . $representationCount . " Representation in the AdaptationSet",
    ""
);

for ($i = 0; $i < $representationCount; $i++) {
    $xml = get_DOM($files[$i], 'atomlist');
    if ($xml) {
        $avcCCount = $xml->getElementsByTagName('avcC')->length;
        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "Section 'Init Segment(s)'",
            "Informative",
            true,
            "PASS",
            "With $avcCCount 'avcC' in Representation $i",
            ""
        );
    }
}
