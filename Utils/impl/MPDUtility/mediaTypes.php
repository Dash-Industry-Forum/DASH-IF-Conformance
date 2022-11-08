<?php

global $mpd_dom;
$mediaTypes = array();

$adapts = $mpd_dom->getElementsByTagName('AdaptationSet');
$reps = $mpd_dom->getElementsByTagName('Representation');
$subreps = $mpd_dom->getElementsByTagName('SubRepresentation');

if ($adapts->length != 0) {
    for ($i = 0; $i < $adapts->length; $i++) {
        $adapt = $adapts->item($i);
        $adapt_contentType = $adapt->getAttribute('contentType');
        $adaptationSetMimeType = $adapt->getAttribute('mimeType');

        if ($adapt_contentType == 'video' || strpos($adaptationSetMimeType, 'video') !== false) {
            $mediaTypes[] = 'video';
        }
        if ($adapt_contentType == 'audio' || strpos($adaptationSetMimeType, 'audio') !== false) {
            $mediaTypes[] = 'audio';
        }
        if ($adapt_contentType == 'text' || strpos($adaptationSetMimeType, 'application') !== false) {
            $mediaTypes[] = 'subtitle';
        }

        $contentcomps = $adapt->getElementsByTagName('ContentComponent');
        foreach ($contentcomps as $contentcomp) {
            $contentcomp_contentType = $contentcomp->getAttribute('contentType');

            if ($contentcomp_contentType == 'video') {
                $mediaTypes[] = 'video';
            }
            if ($contentcomp_contentType == 'audio') {
                $mediaTypes[] = 'audio';
            }
            if ($contentcomp_contentType == 'text') {
                $mediaTypes[] = 'subtitle';
            }
        }
    }
}

if ($reps->length != 0) {
    for ($i = 0; $i < $reps->length; $i++) {
        $rep = $reps->item($i);
        $rep_mimeType = $rep->getAttribute('mimeType');

        if (strpos($rep_mimeType, 'video') !== false) {
            $mediaTypes[] = 'video';
        }
        if (strpos($rep_mimeType, 'audio') !== false) {
            $mediaTypes[] = 'audio';
        }
        if (strpos($rep_mimeType, 'application') !== false) {
            $mediaTypes[] = 'subtitle';
        }
    }
}

if ($subreps->length != 0) {
    for ($i = 0; $i < $subreps->length; $i++) {
        $subrep = $subreps->item($i);
        $subrep_mimeType = $subrep->getAttribute('mimeType');

        if (strpos($subrep_mimeType, 'video') !== false) {
            $mediaTypes[] = 'video';
        }
        if (strpos($subrep_mimeType, 'audio') !== false) {
            $mediaTypes[] = 'audio';
        }
        if (strpos($subrep_mimeType, 'application') !== false) {
            $mediaTypes[] = 'subtitle';
        }
    }
}

return array_unique($mediaTypes);
