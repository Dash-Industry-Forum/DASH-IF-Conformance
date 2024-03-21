<?php

global $logger;

$boxTree = $representation->getBoxNameTree();
if ($boxTree == null) {
  //No boxes to analyse
    return;
}

$spec = "CTA-5005-A";
$section = "4.4.2 - Presentation Splicing";
$boundaryExplanation = "CMAF Fragment boundaries SHALL be created at all splice points.";
$durationExplanation = "CMAF Fragments of different media types NEED NOT have exactly equivalent durations but " .
  "SHALL be within one ISOBMFF sample duration.";


$moofBoxes = $boxTree->filterChildrenRecursive('moof');
if (count($moofBoxes) > 0) {
    foreach ($moofBoxes as $moofBoxes) {
        $trafBoxes = $moofBox->filterChildrenRecursive('traf');
        $logger->test(
            $spec,
            $section,
            $boundaryExplanation,
            count($trafBoxes) == 1,
            "FAIL",
            "Found a single track in fragment for " . $representation->getPrintable(),
            "Found more than one track in fragment for " . $representation->getPrintable()
        );
    }
}

$fragmentDurations = $representation->getFragmentDurations();
$fragmentCount = count($fragmentDurations);

$sampleDuration = $representation->getSampleDuration();

if (
    !$logger->test(
        $spec,
        $section,
        $durationExplanation,
        $sampleDuration != null,
        "WARN",
        "Sample duration found for " . $representation->getPrintable(),
        "Unable to detect sample duration for " . $representation->getPrintable()
    )
) {
    return;
}


//Altough not stated in the rule, we exclude the last fragment from duration considerations;
for ($i = 0; $i < $fragmentCount - 2; $i++) {
    $logger->test(
        $spec,
        $section,
        $durationExplanation,
        abs($fragmentDurations[$i] - $fragmentDurations[$i - 1]) <= $sampleDuration,
        "FAIL",
        "Duration comparison between fragment $i and " . ($i + 1) . " within sampleduration $sampleDuration for " .
          $representation->getPrintable(),
        "Duration comparison between fragment $i and " . ($i + 1) . " not within sampleduration $sampleDuration for " .
          $representation->getPrintable()
    );
}
