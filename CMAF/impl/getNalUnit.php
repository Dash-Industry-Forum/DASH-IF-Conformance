<?php

$nalUnits = $nalArray->childnalUnits;
$nalUnitCount = $nalUnits->length;

for ($i = 0; $i < $nalUnitCount; $i++) {
    $nalUnit = $nalUnits->item($i);
    if (strpos($nalUnit->nodeName, 'NALUnit') !== false) {
        return $nalUnit;
    }
}
return null;
