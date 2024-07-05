<?php

if (!$this->mpd) {
    return;
}
$simpleXML = simplexml_load_string($this->mpd);
if (!$simpleXML) {
    fwrite(STDERR, "Invalid xml string as mpd\n");
    fwrite(STDERR, "$this->mpd\n");
    return;
}

$domSxe = dom_import_simplexml($simpleXML);
if (!$domSxe) {
    fwrite(STDERR, "Unable to import xml");
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
    fwrite(STDERR, "No MPD in xml");
    $this->dom = null;
    return;
}

fwrite(STDERR, "Valid mpd\n");

$this->dom = $main_element_nodes->item(0);
