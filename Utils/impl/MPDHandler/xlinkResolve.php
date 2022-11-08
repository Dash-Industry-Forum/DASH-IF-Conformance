<?php

if (!$this->dom) {
    return;
}

$this->resolved = $this->dom->cloneNode(true);

$this->resolved = $this->xlinkResolveRecursive($this->resolved);


$printable = new \DOMDocument('1.0');
$node = $printable->importNode($this->resolved, true);
$printable->appendChild($node);
//fwrite(STDERR, "Resolved:\n" . $printable->saveXML() . "\n\n");
