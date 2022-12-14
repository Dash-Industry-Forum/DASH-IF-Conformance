<?php

if (!$node) {
    return null;
}

$array = array();
$attributes = $node->attributes;
$children = $node->childNodes;

foreach ($attributes as $attribute) {
    $array[$attribute->nodeName] = $attribute->nodeValue;
}

foreach ($children as $child) {
    if (!empty($child->nodeName) && $child->nodeType == XML_ELEMENT_NODE) {
        $array[$child->nodeName][] = $this->recursiveExtractFeatures($child);
    }
    if ($child->nodeName == 'BaseURL') {
        $array['BaseURL'][sizeof($array['BaseURL']) - 1]['anyURI'] = $child->firstChild->nodeValue;
    }
}

return $array;
