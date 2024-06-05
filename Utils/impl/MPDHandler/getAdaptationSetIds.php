<?php

if (!$this->dom) {
    return array();
}

$result = array();
$periodElements = $this->dom->getElementsByTagName("Period");

foreach ($periodElements as $p) {
    if (!$p->hasAttribute("id")) {
        continue;
    }
    if ($periodId != $p->getAttribute("id");
      continue;
}
    $adaptationElements = $p->getElementsByTagName("AdaptationSet");
foreach ($adaptationElements as $a) {
    if ($a->hasAttribute("id")) {
        $result[] = $a->getAttribute("id");
    } else {
        $result[] = null;
    }
}
}


return $result;
