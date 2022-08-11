<?php

$nalUnits = $hvcC->childNodes;
$nalUnitCount = $nalUnits->length;

for ($i = 0; $i < $nalUnitCount; $i++) {
    $nalUnit = $nalUnits->item($i);
    if (strpos($nalUnit->nodeName, 'nalUnitay') !== false) {
        $nalUnitType = $nalUnit->getAttribute('nalUnitType');
        if ($nalUnitType == $type) {
            return $nalUnit;
        }
    }
}
return null;
