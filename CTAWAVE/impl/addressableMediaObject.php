<?php

global $logger;

$boxOrder = $representation->getTopLevelBoxNames();

if (!count($boxOrder)) {
    return;
}

$spec = "CTA-5005-A";
$section = "4.1.2 - Constraints on CMAF Authoring for Manifest Interoperability";
$sidxExplanation = "CMAF Track Files used as a CMAF Addressable Media Object (i.e., those that are delivered to " .
  "the client) SHALL contain a single SegmentIndexBox (‘sidx’) following the CMAF Header and preceding any CMAF " .
  "Fragments.";

$sidxIndices = array_keys($boxOrder, 'sidx');
$moofIndices = array_keys($boxOrder, 'moof');
$mdatIndices = array_keys($boxOrder, 'mdat');

$sidxCount = count($sidxIndices);
$moofCount = count($moofIndices);
$mdatCount = count($mdatIndices);

if (!$moofCount || !$mdatCount) {
  //Something is completely wrong here...
    return;
}

$equalCount = $mdatCount == $sidxCount;
$possibleValid = $sidxCount && $mdatCount > $sidxCount;


$logger->test(
    $spec,
    $section,
    $sidxExplanation,
    $equalCount,
    $possibleValid ? "WARN" : "FAIL",
    "An identical amount of 'sidx' and 'mdat' boxes found for " . $representation->getPrintable(),
    "Non-identical amount of 'sidx' (" . $sidxCount . ") and 'mdat' (" . $mdatCount . ")boxes found for " .
    $representation->getPrintable()
);

if (!$sidxCount) {
    return;
}

$sidxBeforeFirst = ($sidxIndices[0] < $moofIndices[0] && $sidxIndices[0] < $mdatIndices[0]);

$logger->test(
    $spec,
    $section,
    $sidxExplanation,
    $sidxBeforeFirst,
    "FAIL",
    "sidx box found before first moof/mdat for " . $representation->getPrintable(),
    "No sidx box found before first moof/mdat for " . $representation->getPrintable()
);


$validOrder = true;
for ($sidxIdx = 0; $sidxIdx < $sidxCount - 1; $sidxIdx++) {
    $moofBetween = false;
    foreach ($moofIndices as $moofIdx) {
        if ($moofIdx > $sidxIndices[$sidxIdx] && $moofIdx < $sidxIndices[$sidxIdx + 1]) {
            $moofBetween = true;
            break;
        }
    }
    $mdatBetween = false;
    foreach ($mdatIndices as $mdatIdx) {
        if ($mdatIdx > $sidxIndices[$sidxIdx] && $mdatIdx < $sidxIndices[$sidxIdx + 1]) {
            $mdatBetween = true;
            break;
        }
    }
    if (!$moofBetween || !$mdatBetween) {
        $validOrder = false;
        break;
    }
}

if (
    $sidxIndices[$sidxCount - 1] > $moofIndices[$moofCount - 1] ||
    $sidxIndices[$sidxCount - 1] > $mdatIndices[$mdatCount - 1]
) {
    $validOrder = false;
}

$logger->test(
    $spec,
    $section,
    $sidxExplanation,
    $validOrder,
    "FAIL",
    "Valid positions of sidx boxes for " . $representation->getPrintable(),
    "Invalid poisitions of sidx boxes for " . $representation->getPrintable()
);
