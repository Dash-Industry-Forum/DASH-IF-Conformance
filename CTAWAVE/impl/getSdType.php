<?php

$sdType = 0;
$SampleDescr = "";
$hdlr = $xml->getElementsByTagName("hdlr")->item(0);
$hdlrType = $hdlr->getAttribute("hdlrType");
if ($hdlrType == "vide") {
    $SampleDescr = $xml->getElementsByTagName("vide_sampledescription")->item(0);
} elseif ($hdlrType == "soun") {
    $SampleDescr = $xml->getElementsByTagName("soun_sampledescription")->item(0);
}
if ($SampleDescr !== "") {
    $sdType = $SampleDescr->getAttribute("sdType");
}

return $sdType;
