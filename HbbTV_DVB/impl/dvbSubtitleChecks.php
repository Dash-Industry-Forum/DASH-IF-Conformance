<?php

    global $period_count, $subtitle_bw, $hoh_subtitle_lang;

    $str_codec_info = '';
    $adapt_mimeType = $adapt->getAttribute('mimeType');
    $adapt_codecs = $adapt->getAttribute('codecs');
    $adapt_type = $adapt->getAttribute('contentType');
    $contentComp = false;
    $contentComp_type = array();
    $subtitle = false;
    $supp_present = false;
    $supp_scheme = array();
    $supp_val = array();
    $supp_url = array();
    $supp_fontFam = array();
    $supp_mime = array();
    $ess_present = false;
    $ess_scheme = array();
    $ess_val = array();
    $ess_url = array();
    $ess_fontFam = array();
    $ess_mime = array();

if (strpos($adapt_codecs, 'stpp') !== false) {
    $str_codec_info .= 'y ';
}

    $ids = array();
    $hoh_acc = false;
    $hoh_role = false;
foreach ($adapt->childNodes as $ch) {
    if ($ch->nodeName == 'ContentComponent') {
        $contentComp = true;
        $contentComp_type[] = $ch->getAttribute('contentType');
        if ($ch->getAttribute('contentType') == 'text') {
            $ids[] = $ch->getAttribute('contentType');
        }
    }
    if ($ch->nodeName == 'SupplementalProperty') {
        $supp_present = true;
        $supp_scheme[] = $ch->getAttribute('schemeIdUri');
        $supp_val[] = $ch->getAttribute('value');
        $supp_url[] = ($ch->getAttribute('dvb:url') != '') ? $ch->getAttribute('dvb:url') : $ch->getAttribute('url');
        $supp_fontFam[] = ($ch->getAttribute('dvb:fontFamily') != '') ? $ch->getAttribute('dvb:fontFamily') : $ch->getAttribute('fontFamily');
        $supp_mime[] = ($ch->getAttribute('dvb:mimeType') != '') ? $ch->getAttribute('dvb:mimeType') : $ch->getAttribute('mimeType');
    }
    if ($ch->nodeName == 'EssentialProperty') {
        $ess_present = true;
        $ess_scheme[] = $ch->getAttribute('schemeIdUri');
        $ess_val[] = $ch->getAttribute('value');
        $ess_url[] = ($ch->getAttribute('dvb:url') != '') ? $ch->getAttribute('dvb:url') : $ch->getAttribute('url');
        $ess_fontFam[] = ($ch->getAttribute('dvb:fontFamily') != '') ? $ch->getAttribute('dvb:fontFamily') : $ch->getAttribute('fontFamily');
        $ess_mime[] = ($ch->getAttribute('dvb:mimeType') != '') ? $ch->getAttribute('dvb:mimeType') : $ch->getAttribute('mimeType');
    }
    if ($ch->nodeName == 'Accessibility') {
        if ($ch->getAttribute('schemeIdUri') == 'urn:tva:metadata:cs:AudioPurposeCS:2007' && $ch->getAttribute('value') == '2') {
            $hoh_acc = true;
        }
    }
    if ($ch->nodeName == 'Role') {
        if ($ch->getAttribute('schemeIdUri') == 'urn:mpeg:dash:role:2011' && $ch->getAttribute('value') == 'main') {
            $hoh_role = true;
        }
    }
}

if ($hoh_acc && $hoh_role) {
    if ($adapt->getAttribute('lang') != '') {
        $hoh_subtitle_lang[] = $adapt->getAttribute('lang');
    }
}

    $reps_len = $reps->length;
for ($j = 0; $j < $reps_len; $j++) {
    $rep = $reps->item($j);

    $rep_codecs = $rep->getAttribute('codecs');
    if (strpos($rep_codecs, 'stpp') !== false) {
        $str_codec_info .= 'y ';
    }

    $subrep_codecs = array();
    foreach ($rep->childNodes as $ch) {
        if ($ch->nodeName == 'SubRepresentation') {
            $subrep_codecs[] = $ch->getAttribute('codecs');
            if (strpos($ch->getAttribute('codecs'), 'stpp') !== false) {
                $str_codec_info .= 'y ';
            }

            ##Information from this part is for Section 11.3.0: audio stream bandwidth percentage
            if (in_array($ch->getAttribute('contentComponent'), $ids)) {
                $subtitle_bw[] = ($rep->getAttribute('bandwidth') != '') ? (float)($rep->getAttribute('bandwidth')) : (float)($ch->getAttribute('bandwidth'));
            }
            ##
        }
    }

    ## Information from this part is for Section 7.1: subtitle carriage
    if ($adapt_mimeType == 'application/mp4' || $rep->getAttribute('mimeType') == 'application/mp4') {
        if (strpos($adapt_codecs, 'stpp') !== false || strpos($rep_codecs, 'stpp') !== false || in_array('stpp', $subrep_codecs) !== false) {
            $subtitle = true;

            if (($adapt_type != '' && $adapt_type != 'text') && !in_array('text', $contentComp_type)) {
                fwrite($mpdreport, "###'DVB check violated: Section 7.1.1- The @contetnType attribute indicated for subtitles SHALL be \"text\"', found as " . $adapt->getAttribute('contentType') . " in Period $period_count Adaptation Set " . ($i + 1) . " Representation " . ($j + 1) . ".\n");
            }

            if ($adapt->getAttribute('lang') == '') {
                fwrite($mpdreport, "###'DVB check violated: Section 7.1.2- In oder to allow a Player to identify the primary purpose of a subtitle track, the language attribute SHALL be set on the Adaptation Set', not found on Adaptaion Set " . ($i + 1) . ".\n");
            }
        }

        // Check if subtitle codec attribute is set correctly
        if ($str_codec_info == '') {
            fwrite($mpdreport, "###'DVB check violated: Section 7.1.1- The @codecs attribute indicated for subtitles SHALL be \"stpp\"', not used for in Period $period_count Adaptation Set " . ($i + 1) . " Representation " . ($j + 1) . ".\n");
        }

        ##Information from this part is for Section 11.3.0: audio stream bandwidth percentage
        if (! $contentComp) {
            $subtitle_bw[] = (float)($rep->getAttribute('bandwidth'));
        }
        ##
    }
    ##
}

    ## Information from this part is for Section 7.2: downloadable fonts and descriptors needed for them
if ($subtitle) {
    if ($supp_present) {
        $x = 0;
        foreach ($supp_scheme as $supp_scheme_i) {
            if ($supp_scheme_i == 'urn:dvb:dash:fontdownload:2014') {
                if ($supp_val[$x] != '1') {
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- This descriptor (SupplementalProperty for downloadable fonts) SHALL use the values for @schemeIdUri and @value specified in clause 7.2.1.2', found as \"$supp_scheme_i\" and \"" . $supp_val[$x] . "\" in Period $period_count Adaptation Set " . ($i + 1) . ".\n");
                }
                if ($supp_url[$x] == '' || $supp_fontFam[$x] == '' || ($supp_mime[$x] != 'application/font-sfnt' && $supp_mime[$x] != 'application/font-woff')) {
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- The descriptor (SupplementalProperty for downloadable fonts) SHALL carry all the mandatory additional attributes defined in clause 7.2.1.3', not complete in Period $period_count Adaptation Set " . ($i + 1) . ".\n");
                }
            }
            $x++;
        }
    } elseif ($ess_present) {
        $x = 0;
        foreach ($ess_scheme as $ess_scheme_i) {
            if ($ess_scheme_i == 'urn:dvb:dash:fontdownload:2014') {
                if ($ess_val[$x] != '1') {
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- This descriptor (EssentialProperty for downloadable fonts) SHALL use the values for @schemeIdUri and @value specified in clause 7.2.1.2', found as \"$ess_scheme_i\" and \"" . $ess_val[$x] . "\" in Period $period_count Adaptation Set " . ($i + 1) . ".\n");
                }
                if ($ess_url[$x] == '' || $ess_fontFam[$x] == '' || ($ess_mime[$x] != 'application/font-sfnt' && $ess_mime[$x] != 'application/font-woff')) {
                    fwrite($mpdreport, "###'DVB check violated: Section 7.2.1.1- The descriptor (EssentialProperty for downloadable fonts) SHALL carry all the mandatory additional attributes defined in clause 7.2.1.3', not complete in Period $period_count Adaptation Set " . ($i + 1) . ".\n");
                }
            }
            $x++;
        }
    }
}
    ##
