<?php

if (!$assembly) {
    return;
}

$toAppend = file_get_contents($source);
fwrite($assembly, $toAppend);
fwrite($sizeFile, "$index " . strlen($toAppend) . "\n");
