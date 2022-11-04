<?php

$str = $node->getAttribute("comparedIds");
$part = explode(" ", $str);
$firstId = explode("=", $part[0]);
$secondId = explode("=", substr($part[1], 0, strlen($part[1]) - 1));

return array($firstId[1], $secondId[1]);
