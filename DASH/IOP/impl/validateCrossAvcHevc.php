<?php

global $mpd_features, $session, $current_period, $logger;

$period = $mpd_features['Period'][$current_period];
$representations = $adaptation_set['Representation'];
$bitstreamSwitching = ($adaptation_set['bitstreamSwitching']) ?
  $adaptation_set['bitstreamSwitching'] : $period['bitstreamSwitching'];
$mimeType = ($representations[0]['mimeType']) ? $representations[0]['mimeType'] : $adaptation_set['mimeType'];

if ($bitstreamSwitching != 'true' || strpos($mimeType, 'video') === false) {
    return;
}

$profiles = array();
$levels = array();
$elsts = array();
foreach ($representations as $representationId => $representation) {
    $rep_xml = $session->getRepresentationDir($current_period, $adaptationSetId, $representationId) . '/atomInfo.xml';

    if (!file_exists($rep_xml)) {
        return;
    }

    $xml = get_DOM($rep_xml, 'atomlist');
    if (!$xml) {
        return;
    }

    $codecs = ($representation['codecs']) ? $representation['codecs'] : $adaptation_set['codecs'];
    $isAvc = strpos($codecs, 'avc') !== false;
    $isHevc = strpos($codecs, 'hev') !== false || strpos($codecs, 'hvc') !== false;
    if ($isAvc) {
        $codec_box = $xml->getElementsByTagName('avcC');
        if ($codec_box->length > 0) {
            $codec = $codec_box->item(0);
            $profiles[] = $codec->getAttribute('profile');
            $levels[] = $codec->getElementsByTagName('Comment')->item(0)->getAttribute('level');
        }
    }
    if ($isHevc) {
        $codec_box = $xml->getElementsByTagName('hvcC');
        if ($codec_box->length > 0) {
            $codec = $codec_box->item(0);
            $profiles[] = $codec->getAttribute('profile_idc');
            $levels[] = $codec->getAttribute('level_idc');
        }
    }

    if ($isAvc || $isHevc) {
        $elst = $xml->getElementsByTagName('elst');
        if ($elst->length > 0) {
            $elsts[] = $elst->item(0);
        }
    }
}

$maxProfile = (int)max($profiles);
$maxLevel = (int)max($levels);

$adaptationSetCodecs = $adaptationSet['codecs'];
if ($adaptationSetCodecs != null) {
    $codecArray = explode(',', $adaptationSetCodecs);
    foreach ($codecArray as $codecEntry) {
        $entryIsAvc = strpos($codecEntry, 'avc') !== false;
        $entryIsHevc = strpos($codecEntry, 'hev') !== false || strpos($codecEntry, 'hvc') !== false;
        if ($entryIsAvc) {
            $logger->test(
                "DASH-IF IOP 4.3",
                "Section 6.2.5.2",
                "For AVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs " .
                "attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set",
                (int) (substr($codecEntry, 5, 2)) == dechex($maxProfile),
                "FAIL",
                "Profile is set to maximum profile for Period $current_period Adaptation Set $adaptationSetId",
                "Profile is not set to maximum profile for Period $current_period Adaptation Set $adaptationSetId"
            );
            $logger->test(
                "DASH-IF IOP 4.3",
                "Section 6.2.5.2",
                "For AVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs " .
                "attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set",
                (int) (substr($codecEntry, 9, 2)) == dechex($maxLevel),
                "FAIL",
                "Level is set to maximum level for Period $current_period Adaptation Set $adaptationSetId ",
                "Level is not set to maximum level for Period $current_period Adaptation Set $adaptationSetId"
            );
        }
        if ($entryIsHevc) {
            $entryParts = explode('.', $codecEntry);
            $logger->test(
                "DASH-IF IOP 4.3",
                "Section 6.2.5.2",
                "For HEVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs " .
                "attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set",
                $entryParts[1] == $maxProfile,
                "FAIL",
                "Profile is set to maximum profile for Period $current_period Adaptation Set $adaptationSetId",
                "Profile is not set to maximum profile for Period $current_period Adaptation Set $adaptationSetId"
            );
            $logger->test(
                "DASH-IF IOP 4.3",
                "Section 6.2.5.2",
                "For HEVC video data, if the @bitstreamswitching flag is set to true, the AdaptationSet@codecs " .
                "attribute SHALL equal to the maximum profile and level of any Representation in the Adaptation Set",
                $entryParts[3] == $maxLevel,
                "FAIL",
                "Level is set to maximum level for Period $current_period Adaptation Set $adaptationSetId ",
                "Level is not set to maximum level for Period $current_period Adaptation Set $adaptationSetId"
            );
        }
    }
}

$elstCount = sizeof($elsts);
if ($elstCount > 0) {
    $logger->test(
        "DASH-IF IOP 4.3",
        "Section 6.2.5.2",
        "For AVC/HEVC video data, if the @bitstreamswitching flag is set to true, the edit list, " .
        "if present in any Representation in the  Adaptation Set, SHALL be identical in all Representations",
        $elstCount == sizeof($representations),
        "FAIL",
        "Edit list found for all representations of Period $current_period Adaptation Set $adaptationSetId ",
        "Edit list missing in some representations of Period $current_period Adaptation Set $adaptationSetId"
    );
    if ($elstCount == sizeof($representations)) {
        for ($i = 0; $i < $elstCount; $i++) {
            for ($j = $i + 1; $j < $elstCount; $j++) {
                $logger->test(
                    "DASH-IF IOP 4.3",
                    "Section 6.2.5.2",
                    "For AVC/HEVC video data, if the @bitstreamswitching flag is set to true, the edit list, if " .
                    "present in any Representation in the  Adaptation Set, SHALL be identical in all Representations",
                    nodes_equal($elsts[$i], $elsts[$j]),
                    "FAIL",
                    "Edit list $i and $j equal in Period $current_period Adaptation Set $adaptationSetId ",
                    "Edit list $i and $j different in Period $current_period Adaptation Set $adaptationSetId "
                );
            }
        }
    }
}
