<?php

global $mpd_dom;
$periods = $mpd_dom->getElementsByTagName('Period');
$adaptations1 = $periods->item($periodId1);
$adaptations2 = $periods->item($periodId2);

for ($i = 0; $i < $adaptations1->length; $i++) {
    $adaptation1 = $adaptations1->item($i);
    $adaptationId1 = $adaptation1->getAttribute('id');

    for ($j = 0; $j < $adaptations2->length; $j++) {
        $adaptation2 = $adaptations2->item($j);
        $adaptationId2 = $adaptation2->getAttribute('id');

        if ($id1 == '' || $id1 != $id2) {
            continue;
        }

        $associative = true;

        $validateProperty = function (
            $adaptation1,
            $adaptation2,
            $testMessage,
            $property,
            $msgComponent,
            &$associative,
            &$exists
        ) {
            $val1 = $adaptation1->getAttribute($property);
            $val2 = $adaptation2->getAttribute($property);

            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 10.5.2.2",
                $testMessage,
                $val1 != '' && $val2 != '' && $val1 == $val2,
                "FAIL",
                "Property $property matches $msgComponent",
                "Property $property does not match $msgComponent",
            );
            if ($val1 != '' && $val1 != $val2) {
                $associative = false;
            }

            if (is_set($exists)) {
                $exists = true;
            }
        };
        $validateArrayProperty = function (
            $adaptation1,
            $adaptation2,
            $testMessage,
            $property,
            $msgComponent,
            &$associative,
            &$exists
        ) {
            $arr1 = $adaptation1->getElementsByTagName($property);
            $arr2 = $adaptation2->getElementsByTagName($property);

            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 10.5.2.2",
                $testMessage,
                $arr1->lenghh != $arr2->length,
                "FAIL",
                "Number of @$property elements matches $msgComponent",
                "Number of @$property elements does not match $msgComponent",
            );
            if ($arr1->length != $arr2->length) {
                $associative = false;
                return;
            }
            if (is_set($exists)) {
                $exists = true;
            }
            for ($i = 0; $i < $arr1->length; $i++) {
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 10.5.2.2",
                    $testMessage,
                    nodes_equal($arr1->item($i), $arr2->item($i)),
                    "FAIL",
                    "Entry $i of $property elements matches $msgComponent",
                    "Entry $i of $property elements does not match $msgComponent",
                );
                if (!nodes_equal($arr1->item($i), $arr2->item($i))) {
                    $associative = false;
                }
            }
        };
        $msgComponent = "Between period $periodId1 - adaptation $adaptationId1 and " .
                        "period $periodId2 - adaptation $adaptationId2";

        $validateProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then the language as decribed by the @lang " .
            "attribute SHALL be identical for the two Adaptation Sets",
            "language",
            $msgComponent,
            $associative
        );


        $validateProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then the media content component type " .
            "decribed by the @contentType attribute SHALL be identical for the two Adaptation Sets",
            "contentType",
            $msgComponent,
            $associative
        );

        $validateProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then the picture aspect ratio decribed by " .
            "the @par attribute SHALL be identical for the two Adaptation Sets",
            "par",
            $msgComponent,
            $associative
        );

        $validateArrayProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then any role properties as decribed by " .
            "the Role elements SHALL be identical for the two Adaptation Sets",
            "Role",
            $msgComponent,
            $associative
        );

        $validateArrayProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then any accessiblity properties as " .
            "decribed by the Accessibility elements SHALL be identical for the two Adaptation Sets",
            "Accessiblity",
            $msgComponent,
            $associative
        );

        $validateArrayProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then any viewpoint properties as decribed " .
            "by the Viewpoint elements SHALL be identical for the two Adaptation Sets",
            "Viewpoint",
            $msgComponent,
            $associative
        );

        $representations1 = $adaptation1->getElementsByTagName('Representation');
        $representations2 = $adaptation2->getElementsByTagName('Representation');

        $mimeType1 = $adaptation1->getAttribute('mimeType');
        $mimeType2 = $adaptation2->getAttribute('mimeType');
        $contentType1 = $adaptation1->getAttribute('contentType');
        $contentType2 = $adaptation2->getAttribute('contentType');

        $isaudio = ((strpos($mimeType1, 'audio') !== false) || $contentType1 == 'audio') &&
          ((strpos($mimeType2, 'audio') !== false) || $contentType2 == 'audio');

        if ($mimeType1 == '' || $mimeType2 == '') {
            if ($representations1->length == $representations2->length) {
                for ($r = 0; $r < $representations1->length; $r++) {
                    $representation1 = $representations1->item($r);
                    $representation2 = $representations2->item($r);
                    $isaudio |= ((strpos($representation1->getAttribute('mimeType'), 'audio') !== false) &&
                      (strpos($representation2->getAttribute('mimeType'), 'audio') !== false));
                }
            }
        }

        if (!$isAudio && $associative) {
            $this->associativity[] = "$periodId1 $i $periodId2 $j";
            continue;
        }

        // Adaptation Set level
        $mimeTypeExists = false;
        $codecsExsits = false;
        $audioSamplingRateExists = false;
        $audioChannelConfigurationExists = false;

         // @mimeType
        $validateProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values " .
            "and presence of all attributes and elements listed in Table 3 SHALL be identical for the two " .
            "Adaptation Sets",
            "mimeType",
            $msgComponent,
            $associative,
            $mimeTypeExists
        );


        // @codecs
        $validateProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values " .
            "and presence of all attributes and elements listed in Table 3 SHALL be identical for the two " .
            "Adaptation Sets",
            "codecs",
            $msgComponent,
            $associative,
            $codecsExists
        );

        // @audioSamplingRate
        $validateProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values " .
            "and presence of all attributes and elements listed in Table 3 SHALL be identical for the two " .
            "Adaptation Sets",
            "audioSamplingRate",
            $msgComponent,
            $associative,
            $audioSamplingRateExists
        );

        $validateArrayProperty(
            $adaptation1,
            $adaptation2,
            "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values " .
            "and presence of all attributes and elements listed in Table 3 SHALL be identical for the two " .
            "Adaptation Sets",
            "AudioChannelConfiguration",
            $msgComponent,
            $associative,
            $audioChannelConfigurationExsits
        );

        $allExist = $mimeTypeExists && $codecsExists && $audioSamplingRateExists && $audioChannelConfigurationExists;

        if ($allExist && $associative) {
            $this->associativity[] = "$periodId1 $i $periodId2 $j";
            continue;
        }

        $logger->test(
            "HbbTV-DVB DASH Validation Requirements",
            "DVB: Section 10.5.2.2",
            "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all values " .
            "and presence of all attributes and elements listed in Table 3 SHALL be identical for the two " .
            "Adaptation Sets",
            $representations1->length == $representations2->length,
            "FAIL",
            "Number of Representation elements matches $msgComponent",
            "Number of Representation elements does not match $msgComponent",
        );
        // Representation Set level
        if ($representations1->length != $representations2->length) {
            continue;
        }
        for ($r = 0; $r < $reps1->length; $r++) {
            $representation1 = $representations1->item($r);
            $representation2 = $representations2->item($r);
            $representationMsgComponent = "Between period $periodId1 - adaptation $adaptationId1 - representation $r " .
                                          "and period $periodId2 - adaptation $adaptationId2 - representation $r";

            // @mimeType
            if (!$mimeTypeExists) {
                $validateProperty(
                    $representation1,
                    $representation2,
                    "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all " .
                    "values and presence of all attributes and elements listed in Table 3 SHALL be identical for the " .
                    "two Adaptation Sets",
                    "mimeType",
                    $representationMsgComponent,
                    $associative,
                );
            }

            // @codecs
            if (!$codecsExists) {
                $validateProperty(
                    $representation1,
                    $representation2,
                    "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all " .
                    "values and presence of all attributes and elements listed in Table 3 SHALL be identical for the " .
                    "two Adaptation Sets",
                    "codecs",
                    $representationMsgComponent,
                    $associative,
                );
            }

            // @audioSamplingRate
            if (!$audioSamplingRateExists) {
                $validateProperty(
                    $representation1,
                    $representation2,
                    "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all " .
                    "values and presence of all attributes and elements listed in Table 3 SHALL be identical for the " .
                    "two Adaptation Sets",
                    "audioSamplingRate",
                    $representationMsgComponent,
                    $associative,
                );
            }

            // @AudioChannelConfiguration
            if (!$audioChannelConfigurationExists) {
                $validateArrayProperty(
                    $representation1,
                    $representation2,
                    "If Adaptation Sets in two different Periods are associated, then for audio Adaptation Sets all " .
                    "values and presence of all attributes and elements listed in Table 3 SHALL be identical for the " .
                    "two Adaptation Sets",
                    "AudioChannelConfiguration",
                    $representationMsgComponent,
                    $associative,
                );
            }
        }

        if ($associative) {
            $this->associativity[] = "$periodId1 $i $periodId2 $j";
        }
    }
}
