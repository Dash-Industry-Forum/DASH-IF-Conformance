<?php

//Check for encrypted tracks
if ($xml->getElementsByTagName('tenc')->length) {
    $schm = $xml->getElementsByTagName('schm');
    if ($schm->length) {
         return $schm->item(0)->getAttribute('scheme');
    }
}
return null;
