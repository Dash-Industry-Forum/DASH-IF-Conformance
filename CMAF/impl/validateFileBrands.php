<?php

$brands1 = (string)$brand1;
$brands2 = (string)$brand2;
$videoCmaf1 = strpos($brands1, "cfsd") || strpos($brands1, "cfhd") || strpos($brands1, "chdf");
$videoCmaf2 = strpos($brands2, "cfsd") || strpos($brands2, "cfhd") || strpos($brands2, "chdf");
$audioCmaf1 = strpos($brands1, "caac") || strpos($brands1, "caaa");
$audioCmaf2 = strpos($brands2, "caac") || strpos($brands2, "caaa");

if (
    $audioCmaf1 == false &&
    (
      ($videoCmaf1 !== false && $videoCmaf2 == false ) ||
      ($videoCmaf2 !== false && $videoCmaf1 == false )
    )
) {
    $this->careAboutFtyp = true;
}
