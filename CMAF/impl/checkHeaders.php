<?php

global $current_adaptation_set, $logger;

$this->compare($xml1, $xml2, $id1, $id2, $currentAdaptionDir, $index, $path);

$fileExists = $logger->test(
    "CMAF",
    "Switching Sets Validation - Check Headers",
    "Attempting to open file $path",
    file_exists($path),
    "FAIL",
    "Files exist",
    "Files don't exist: Possible cause: Representations are not valid and no " .
    "file/directory for box info is created.)"
);

if (!$fileExists) {
    return;
}

$first = true;
$xml = DASHIF\Utility\parseDOM($path, 'compInfo');

if ($xml) {
    //if any attribute in the xml file contains "No", then this will be considered as an error
    foreach ($xml->childNodes as $child) {
        if ($first) {
            $ids = $this->getIds($xml);
            $first = false;
        }

        $childName = $child->nodeName;
        if ($childName == "elst" && !$this->careAboutElst) {
            continue;
        }
        if ($childName == "mdhd" && !$this->careAboutMdhd) {
            continue;
        }
        if ($childName == "ftyp" && !$this->careAboutFtyp) {
            continue;
        }
        foreach ($child->attributes as $attribute) {
            $logger->test(
                "CMAF",
                "Section 7.3.4",
                "CMAF header parameters SHALL NOT differ between CMAF tracks, except as allowed in Table 11",
                !in_array("No", explode(" ", $attribute->nodeValue)),
                "FAIL",
                "Attribute $attribute->nodeName in box $childName equal between representations " .
                "$ids[0] and $ids[1] in Switching set $current_adaptation_set",
                "Attribute $attribute->nodeName in box $childName not equal between representations " .
                "$ids[0] and $ids[1] in Switching set $current_adaptation_set",
            );
        }
    }
}
