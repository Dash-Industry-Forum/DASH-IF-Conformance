<?php

global $logger;

## Section 4.3 checks for sample entry type and track_ID
$hdlr1 = $xmlDom1->getElementsByTagName('hdlr')->item(0);
$hdlrType1 = $hdlr1->getAttribute('handler_type');

$hdlr2 = $xmlDom2->getElementsByTagName('hdlr')->item(0);
$hdlrType2 = $hdlr2->getAttribute('handler_type');


## Section 10.4 check for audio switching
if ($hdlrType1 == 'soun' && $hdlrType2 == 'soun') {
    $this->crossValidationDVBAudio($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2);
}

## Section 10.4 check for video switching
if ($hdlrType1 == 'vide' && $hdlrType2 == 'vide') {
    $this->crossValidationDVBVideo($xmlDom1, $xmlDom2, $adaptationIndex, $xmlIndex1, $xmldIndex2);
}
