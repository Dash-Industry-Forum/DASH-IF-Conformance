<?php

namespace DASHIF\Utility;

function profileListContainsProfile($list, $profile)
{
    $profiles = explode(',', $list);
    return in_array($profile, $profiles);
}

function in_array_at_least_one($options, $array)
{
    foreach ($options as $option) {
        if (in_array($option, $array)) {
            return true;
        }
    }
    return false;
}

function isAbsoluteURL($URL)
{
    $parsedUrl = parse_url($URL);
    return array_key_exists("scheme", $parsedUrl) && array_key_exists("host", $parsedUrl);
}

function inString($option, $string)
{
    return strpos($string, $option) !== false;
}

function inStringAtLeastOne($options, $string)
{
    foreach ($options as $option) {
        if (inString($option, $string)) {
            return true;
        }
    }
    return false;
}

function mpdContainsProfile($profile)
{
    global $mpdHandler;
    return profileListContainsProfile($mpdHandler->getDom()->getAttribute('profiles'), $profile);
}

function mpdProfilesContainsAll($profiles)
{
    foreach ($profiles as $profile) {
        if (!mpdContainsProfile($profile)) {
            return false;
        }
    }
    return true;
}

function mpdProfilesContainsNone($profiles)
{
    foreach ($profiles as $profile) {
        if (mpdContainsProfile($profile)) {
            return false;
        }
    }
    return true;
}

function profileListContainsAtLeastOne($list, $profiles)
{
    foreach ($profiles as $profile) {
        if (profileListContainsProfile($list, $profile)) {
            return true;
        }
    }
    return false;
}

function mpdProfilesContainsAtLeastOne($profiles)
{
    global $mpdHandler;
    return profileListContainsAtLeastOne($mpdHandler->getDom()->getAttribute('profiles'), $profiles);
}


function recursive_generate($node, &$domDocument, &$domElement, $profile)
{
    foreach ($node->childNodes as $child) {
        if ($child->nodeType == XML_ELEMENT_NODE) {
            if (
                $child->getAttribute('profiles') == '' ||
                strpos($child->getAttribute('profiles'), $profile) !== false
            ) {
                $domchild = $domDocument->createElement($child->nodeName);
                $domchild = $child->cloneNode();

                $domchild = recursive_generate($child, $domDocument, $domchild, $profile);
                $domElement->appendChild($domchild);
            }
        }
    }

    return $domElement;
}

//Helper function to find next sibling. Php function doesn't do what we want
function nextElementSibling($node)
{
    while ($node && ($node = $node->nextSibling)) {
        if ($node instanceof DOMElement) {
            break;
        }
    }
    return $node;
}

function checkYearMonth($str)
{
    $y = str_replace("P", "", $str);

    if (strpos($y, 'Y') !== false) { // Year
        $Y = explode("Y", $y);

        $y = substr($y, strpos($y, 'Y') + 1);
    } else {
        $Y[0] = 0;
    }

    if (strpos($y, 'M') !== false && strpos($y, 'M') < strpos($y, 'T')) { // Month
        $Mo = explode("M", $y);
        $y = substr($y, strpos($y, 'M') + 1);
    }

    $duration = ($Y[0] * 365 * 24 * 60 * 60) +
        ($Mo[0] * 30 * 24 * 60 * 60);

    return ($duration > 0);
}

function mergeSegmentAccess($highLevel, $lowLevel)
{
    if (!$highLevel && !$lowLevel) {
        return null;
    }
    if (!$highLevel) {
        return $lowLevel;
    }
    if (!$lowLevel) {
        return $highLevel;
    }
    return formSegmentAccess($highLevel, $lowLevel);
}

function formSegmentAccess($highLevel, $lowLevel)
{
    foreach ($highLevel as $key => $highValue) {
        $lowValue = array_key_exists($key, $lowLevel) ? $lowLevel[$key] : array();
        foreach ($highValue as $k => $v) {
            if (!array_key_exists($k, $lowValue) || !$lowValue[$k]) {
                $lowValue[$k] = $v;
            } elseif (gettype($lowValue[$k]) == 'array') {
                //$v would also work, but this is more clear in meaning
                $lowValue[$k] = formSegmentAccess($highValue[$k], $lowValue[$k]);
            }
        }
        $lowLevel[$key] = $lowValue;
    }
    return $lowLevel;
}

function parseDoc($path)
{
    $return_val = false;

    $contents = file_get_contents($path);

    $loaded = simplexml_load_file($path);
    if (!$loaded) {
        return $return_val;
    }

    $dom_sxe = dom_import_simplexml($loaded);
    if (!$dom_sxe) {
        return $return_val;
    }

    $dom_doc = new \DOMDocument('1.0');
    $dom_sxe = $dom_doc->importNode($dom_sxe, true);
    if (!$dom_sxe) {
        return $return_val;
    }

    $dom_doc->appendChild($dom_sxe);
    return $dom_doc;
}

function xmlStringAsDoc($str)
{
    $xml = simplexml_load_string($str);
    $dom_sxe = dom_import_simplexml($xml);
    if (!$dom_sxe) {
        return null;
    }
    $doc = new \DOMDocument('1.0');
    return $doc->importNode($dom_sxe, true);
}

function parseDOM($path, $main_element)
{
    $return_val = false;

    $dom_doc = parseDoc($path);
    if (!$dom_doc) {
        return $return_val;
    }

    $main_element_nodes = $dom_doc->getElementsByTagName($main_element);
    if ($main_element_nodes->length == 0) {
        return $return_val;
    }

    return $main_element_nodes->item(0);
}
