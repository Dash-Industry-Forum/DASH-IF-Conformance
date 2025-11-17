<?php

global $mpdHandler;

// Bullet 2
$dashSegCmafFrag = true;

$cmafDash = $this->validateDASHProfileCMAF(
    $adaptationSet,
    $adaptationSetId,
    $segmentAccessInfo,
    $infoFileAdapt,
    $logger
);

$logger->test(
    "DASH-IF IOP CR Low Latency Live",
    "Section 9.X.4.5",
    "Each Segment SHALL conform to a CMAF Fragment",
    sizeof(array_unique($cmafDash)) == 1 && $cmafDash[0] == true,
    "FAIL",
    "All segments conform in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation ' . ($adaptationSetId + 1),
    "Not all segments conform in Period " . ($mpdHandler->getSelectedPeriod() + 1) .
    ' Adaptation ' . ($adaptationSetId + 1)
);

if (!(sizeof(array_unique($cmafDash)) == 1 && $cmafDash[0] == true)) {
    $dashSegCmafFrag = false;
}

$representations = $adaptationSet['Representation'];
foreach ($representations as $representationId => $representation) {
    $chunkedAdaptationPoints[$representationId] = 3;

    $chunkOverlapWithinRep = $this->validateTimingsWithinRepresentation(
        $adaptationSet,
        $adaptationSetId,
        $representationId,
        $infoFileAdapt,
        $logger
    );

    // Bullet 8 info collect
    $resyncs = ($representation['Resync'] != null) ? $representation['Resync'] : $adaptationSet['Resync'];
    $this->secondOption['bandwidth'][$representationId] = $representation['bandwidth'];
    $this->secondOption['Resync'][$representationId] = $resyncs;
    $this->secondOption['timescale'][$representationId] = $segmentAccessInfo[$representationId][0]['timescale'];
    $this->secondOption['target'][$representationId] = $target;
    $this->secondOption['qualityRanking'][$representationId] = $representation['qualityRanking'];
    $this->secondOption['chunkOverlapWithinRep'][$representationId] = $chunkOverlapWithinRep;
}

    // Bullet 8
if (sizeof($representations) > 1) {
    $extendedChecks = $this->validate9X45Extended($adaptationSet, $adaptationSetId, $logger);
    $logger->test(
        "DASH-IF IOP CR Low Latency Live",
        "Section 9.X.4.5",
        "For any Adaptation Set that contains more than one Representation, one of two options " .
        "listed in Bullet 8 in this clause SHOULD be applied",
        $extendedChecks[0] || $extendedChecks[1],
        "WARN",
        "At least one of the options applied in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1),
        "Neither of the options applied in Period " . ($mpdHandler->getSelectedPeriod() + 1) . ' Adaptation ' .
        ($adaptationSetId + 1)
    );
}
