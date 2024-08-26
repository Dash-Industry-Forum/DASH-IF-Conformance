<?php

if (!$this->dom) {
    return array();
}

$result = array();
$periodElements = $this->dom->getElementsByTagName("Period");

foreach ($periodElements as $p) {
    if ($p->hasAttribute("id")) {
        $result[] = $p->getAttribute("id");
    } else {
        $result[] = null;
    }
}


return $result;
