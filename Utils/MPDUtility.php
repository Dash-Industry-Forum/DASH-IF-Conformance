<?php

namespace DASHIF\Utility;

function mpdContainsProfile($profile)
{
    global $mpd_dom;
    $mpdProfiles = explode(',', $mpd_dom->getAttribute('profiles'));
    return in_array($profile, $mpdProfiles);
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

function mpdProfilesContainsAtLeastOne($profiles)
{
    foreach ($profiles as $profile) {
        if (mpdContainsProfile($profile)) {
            return true;
        }
    }
    return false;
}

function media_types()
{
    global $mpd_dom;
    $media_types = array();

    $adapts = $mpd_dom->getElementsByTagName('AdaptationSet');
    $reps = $mpd_dom->getElementsByTagName('Representation');
    $subreps = $mpd_dom->getElementsByTagName('SubRepresentation');

    if ($adapts->length != 0) {
        for ($i = 0; $i < $adapts->length; $i++) {
            $adapt = $adapts->item($i);
            $adapt_contentType = $adapt->getAttribute('contentType');
            $adapt_mimeType = $adapt->getAttribute('mimeType');

            if ($adapt_contentType == 'video' || strpos($adapt_mimeType, 'video') !== false) {
                $media_types[] = 'video';
            }
            if ($adapt_contentType == 'audio' || strpos($adapt_mimeType, 'audio') !== false) {
                $media_types[] = 'audio';
            }
            if ($adapt_contentType == 'text' || strpos($adapt_mimeType, 'application') !== false) {
                $media_types[] = 'subtitle';
            }

            $contentcomps = $adapt->getElementsByTagName('ContentComponent');
            foreach ($contentcomps as $contentcomp) {
                $contentcomp_contentType = $contentcomp->getAttribute('contentType');

                if ($contentcomp_contentType == 'video') {
                    $media_types[] = 'video';
                }
                if ($contentcomp_contentType == 'audio') {
                    $media_types[] = 'audio';
                }
                if ($contentcomp_contentType == 'text') {
                    $media_types[] = 'subtitle';
                }
            }
        }
    }

    if ($reps->length != 0) {
        for ($i = 0; $i < $reps->length; $i++) {
            $rep = $reps->item($i);
            $rep_mimeType = $rep->getAttribute('mimeType');

            if (strpos($rep_mimeType, 'video') !== false) {
                $media_types[] = 'video';
            }
            if (strpos($rep_mimeType, 'audio') !== false) {
                $media_types[] = 'audio';
            }
            if (strpos($rep_mimeType, 'application') !== false) {
                $media_types[] = 'subtitle';
            }
        }
    }

    if ($subreps->length != 0) {
        for ($i = 0; $i < $subreps->length; $i++) {
            $subrep = $subreps->item($i);
            $subrep_mimeType = $subrep->getAttribute('mimeType');

            if (strpos($subrep_mimeType, 'video') !== false) {
                $media_types[] = 'video';
            }
            if (strpos($subrep_mimeType, 'audio') !== false) {
                $media_types[] = 'audio';
            }
            if (strpos($subrep_mimeType, 'application') !== false) {
                $media_types[] = 'subtitle';
            }
        }
    }

    return array_unique($media_types);
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
