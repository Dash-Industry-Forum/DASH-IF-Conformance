<?php

global $session_dir, $mpd_features, $associativity, $adaptation_set_template, $reprsentation_template;

global $logger;

$periods = $mpd_features['Period'];
$periodCount = sizeof($periods);

for ($i = 0; $i < $periodCount; $i++) {
    for ($j = $i + 1; $j < $periodCount; $j++) {
        $period1 = $periods[$i];
        $adaptationSet1 = $period1['AdaptationSet'];
        $period2 = $periods[$j];
        $adaptationSet2 = $period2['AdaptationSet'];

        for ($a1 = 0; $a1 < sizeof($adaptationSet1); $a1++) {
            for ($a2 = 0; $a2 < sizeof($adaptationSet2); $a2++) {
                $adaptation1 = $adaptationSet1[$a1];
                $adaptation2 = $adaptationSet2[$a2];

                $adaptationId1 = $adaptation1['id'];
                $adaptationId2 = $adaptation2['id'];
                if ($adaptationId1 != $adaptationId2) {
                    continue;
                }
                $supplementalProperties2 = $adaptation2['SupplementalProperty'];
                foreach ($supplementalProperties2 as $supplementalProperty2) {
                    if (
                        $supplementalProperty2['schemeIdUri'] != 'urn:dvb:dash:period_continuity:2014' ||
                        !in_array($period1['id'], explode(',', $supplementalProperty2['value']))
                    ) {
                        continue;
                    }

                    ## Period continuous adapation sets are signalled.
                    ## Start checking for conformity according to Section 10.5.2.3
                    // Check associativity
                    $logger->test(
                        "HbbTV-DVB DASH Validation Requirements",
                        "DVB: Section 10.5.2.3",
                        "If Adaptation Sets in two different Periods are period continuous, then Adaptation Sets " .
                        "with the value of their @id attribute set to AID in the first and subsequent Periods SHALL " .
                        "be associated as defined in clause 10.5.2.3",
                        in_array("$i $a1 $j $a2", $associativity),
                        "FAIL",
                        "Associated values found for Adaptation $a1 period $i, and Adapation $a2 period $j",
                        "Associated values not found for Adaptation $a1 period $i, and Adapation $a2 period $j"
                    );
                    // EPT1 comparisons within the Adaptation Sets
                    if ($i != 0) {
                        continue;
                    }
                    $EPT1 = array();
                    $representations1 = $adaptation1['Representation'];
                    for ($thisRep = 0; $thisRep < sizeof($representations1); $thisRep++) {
                        $adaptationDirectory = str_replace('$AS$', $a1, $adaptation_set_template);
                        $representationDirectory = str_replace(
                            array('$AS$', '$R$'),
                            array($a1, $thisRep),
                            $reprsentation_template
                        );

                        $xmlRepresentation = get_DOM($session_dir . '/Period' . $i . '/' . $adaptationDirectory .
                                                     '/' . $representationDirectory . '.xml', 'atomlist');
                        if ($xmlRepresentation) {
                            ///\todo Fix
                            //$EPT1[] = segment_timing_info($xmlRepresentation);
                        }
                    }
                    for ($thisRep = 0; $thisRep < sizeof($representations1); $thisRep++) {
                        for ($nextRep = $thisRep + 1; $nextRep < sizeof($representations1); $nextRep++) {
                            ///\TODO fix that this doesn't crash if some xml reps weren't loaded
                            $logger->test(
                                "HbbTV-DVB DASH Validation Requirements",
                                "DVB: Section 10.5.2.2",
                                "If Adaptation Sets in two different Periods are period continuous, then all the " .
                                "Representations in the Adaptation Set in the first Period SHALL share the same " .
                                "value EPT1 for the earliest presentation time",
                                $EPT1[$thisRep] === $EPT1[$nextRep],
                                "FAIL",
                                "Identical EPT1 found for representation $thisRep and $nextRep, " .
                                "adaptation set $a1, period $i",
                                "Different EPT1 found for representation $thisRep and $nextRep, " .
                                "adaptation set $a1, period $i"
                            );
                        }
                    }
                }
            }
        }
    }
}
