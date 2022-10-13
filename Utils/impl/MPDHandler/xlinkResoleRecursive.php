<?php

if (!$node) {
    return null;
}

$attributes = $node->attributes;
$children = $node->childNodes;

foreach ($attributes as $attribute) {
    if ($attribute->nodeName == "xlink:href") {
        $res = file_get_contents($attribute->nodeValue);
        fwrite(STDERR, "$attribute->nodeValue \n");
        fwrite(STDERR, "$res\n");

        $simpleXML = simplexml_load_string($res);
        if (!$simpleXML) {
                return;
        }

        $domSxe = dom_import_simplexml($simpleXML);
        if (!$domSxe) {
              return;
        }

        $dom = new \DOMDocument('1.0');
        $domSxe = $dom->importNode($domSxe, true);
        if (!$domSxe) {
            return;
        }

        return $domSxe;
    }
}

foreach ($children as $child) {
    if (!empty($child->nodeName) && $child->nodeType == XML_ELEMENT_NODE) {
        $child = $this->xlinkResolveRecursive($child);
    }
}

return $node;
