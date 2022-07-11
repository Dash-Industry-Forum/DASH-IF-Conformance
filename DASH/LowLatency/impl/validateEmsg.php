<?php

global $session_dir, $current_period, $adaptation_set_template, $reprsentation_template,
       $reprsentation_mdat_template, $logger;

$returnValue = true;

///\RefactorTodo Make this work with a separate logger instance?

//This entire function has only "PASS" checks, as validation is done based on the return value.
//Every check is in essence a "FAIL" check, but there are two valid options in the outer scope.
//Therefore, we store the combined result of all tests, and we return whether all succeeded.

$adapt_dir = str_replace('$AS$', $adaptationSetId, $adaptation_set_template);
$rep_xml_dir = str_replace(array('$AS$', '$R$'), array($adaptationSetId, $representationId), $reprsentation_template);
$rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_xml_dir . '.xml';

///Correctness this doesn't seem right....
if (!file_exists($rep_xml)) {
    return true;
}

xml = get_DOM($rep_xml, 'atomlist');
if (!$xml) {
    return true;
}

$isSegmentStarts = $infoFileAdapt[$representationId]['isSegmentStart'];

$segmentIndexes = array_keys($isSegmentStarts, '1');

$mdatFile = 'Period' . $current_period . '/' . str_replace(
    array('$AS$', '$R$'),
    array($adaptationSetId, $representationId),
    $reprsentation_mdat_template
);
$mdatInfo = explode("\n", file_get_contents($mdatFile));

$moofBoxes = $xml->getElementsByTagName('moof');
$emsgBoxes = $xml->getElementsByTagName('emsg');
foreach ($emsgBoxes as $emsgIndex => $emsgBox) {
    $emsgConformance = false;
    $emsgOffset = $emsgBox->getAttribute('offset');
    if ($emsgOffset < $moofBoxes->item(0)->getAttribute('offset')) {
        $emsgConformance = true;
    } else {
        $beforeMoofFound = false;
        for ($i = 0; $i < $moofBoxes->length; $i++) {
            $moofBox = $moofBoxes->item($i);
            $moofOffset = $moofBox->getAttribute('offset');
            $moofSize = $moofBox->getAttribute('size');

            $mdat = $mdatInfo[$i];
            $mdatOffset = explode(' ', $mdat)[0];
            $mdatSize = explode(' ', $mdat)[1];

            if ($emsgOffset < $moofOffset) {
                $beforeMoofFound = true;
                break;
            } elseif (($emsgOffset > $moofOffset + $moofSize) && ($emsgOffset < $mdatOffset)) {
                break;
            }
        }

        $segmentNumber = null;
        if ($beforeMoofFound) {
            foreach ($segmentIndexes as $segmentIndexId => $segmentIndex) {
                if ($segmentIndexId != sizeof($segmentIndexes) - 1) {
                    if ($i >= $segmentIndex && $i < $segmentIndexes[$segmentIndexId + 1]) {
                        $segmentNumber = $segmentIndexId;
                    }
                }
            }
        }

        $equivalentEmsgFound = false;
        if ($segmentNumber != null) {
            //[0] = offset, [1] = size
            $mdatLastCurrentSegment = explode(' ', $mdatInfo[$segmentIndexes[$segmentNumber + 1] - 1]);
            $firstMoofNextSegment = $moofBoxes->item($segmentIndexes[$segmentNumber + 1]);

            $firstMoofNextSegmentOffset = $firstMoofNextSegment->getAttribute('offset');

            for ($e = $emsgIndex + 1; $e < $emsgBoxes->length; $e++) {
                $eCompare = $emsgBoxes->item($e);
                $eCompareOffset = $eCompare->getAttribute('offset');

                if (
                    $e_offset >= $mdatLastCurrentSegment[0] + $mdatLastCurrentSegment[1] &&
                    $eCompareOffset < $firstMoofNextSegmentOffset
                ) {
                    if (nodes_equal($emsgBox, $eCompare)) {
                        $equivalentEmsgFound = true;
                        $emsgConformance = true;
                        break;
                    }
                }
            }
        }
        $logger->test(
            "DASH-IF IOP CR Low Latency Live",
            "Section 9.X.4.5",
            "Any emsg box if not placed before the moof box, an equivalent emsg with the same id valu SHALL be " .
            "present before the first moof box of the next Segment",
            $equivalentEmsgFound,
            "FAIL",
            "emsg box at index " . ($emsgIndex + 1) . " conforms",
            "emsg box at index " . ($emsgIndex + 1) . " does not conform"
        );
    }

    $returnValue = $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "Any emsg box MAY be placed in between any mdat and moof boxes or before the first moof box",
        $emsgConformance,
        "WARN",
        "emsg box at index " . ($emsgIndex + 1) . " conforms to either option",
        "emsg box at index " . ($emsgIndex + 1) . " does not conform to either option"
    ) && $returnValue;
}


return $returnValue;
