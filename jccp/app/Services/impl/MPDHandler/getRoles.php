<?php

$res = array();

$allPeriods = $this->dom->getElementsByTagName('Period');

if (count($allPeriods) >= $period) {
    return $res;
}

$periodAdaptations = $allPeriods->item($period)->getElementsByTagName('AdaptationSet');

if (count($periodAdaptations) >= $adaptation) {
    return $res;
}

$adaptationRoles = $periodAdaptations->item($adaptation)->getElementsByTagName('Role');

foreach ($adaptationRoles as $role) {
    $res[] = array(
      'schemeIdUri' => $role->getAttribute('schemeIdUri'),
      'value' => $role->getAttribute('value'),
    );
}

return $res;
