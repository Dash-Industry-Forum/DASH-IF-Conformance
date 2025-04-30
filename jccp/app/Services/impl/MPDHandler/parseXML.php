<?php
use Illuminate\Support\Facades\Log;

if (!$this->mpd) {
    return;
}
$simpleXML = simplexml_load_string($this->mpd);
if (!$simpleXML) {
    Log::error("Invalid xml string as mpd", ['mpd' => $this->mpd]);
    return;
}

$domSxe = dom_import_simplexml($simpleXML);
if (!$domSxe) {
    Log::error("Unable to import xml");
    return;
}

$dom = new \DOMDocument('1.0');
$domSxe = $dom->importNode($domSxe, true);
if (!$domSxe) {
    return;
}

$dom->appendChild($domSxe);
$main_element_nodes = $dom->getElementsByTagName('MPD');
if ($main_element_nodes->length == 0) {
    Log::error("No MPD in xml");
    $this->dom = null;
    return;
}


$this->dom = $main_element_nodes->item(0);
