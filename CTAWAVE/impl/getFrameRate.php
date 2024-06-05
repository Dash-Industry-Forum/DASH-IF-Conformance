<?php

$videSampleDes = $xml->getElementsByTagName("vide_sampledescription")->item(0);
$sdType = $videSampleDes->getAttribute("sdType");
if ($sdType == 'avc1' || $sdType == 'avc3') {
    $nal_unit = $xml->getElementsByTagName("NALUnit");
    if ($nal_unit->length == 0) {
        $framerate = 0;
    } else {
        for ($nal_count = 0; $nal_count < $nal_unit->length; $nal_count++) {
            if (hexdec($nal_unit->item($nal_count)->getAttribute("nal_type")) == 7) {
                $sps_unit = $nal_count;
                 break;
            }
        }

        $comment = $nal_unit->item($sps_unit)->getElementsByTagName("comment")->item(0);


        if (hexdec($comment->getAttribute("vui_parameters_present_flag")) == 1) {
            if (hexdec($comment->getAttribute("timing_info_present_flag")) == 1) {
                $num_units_in_tick = $comment->getAttribute("num_units_in_tick");
                $time_scale = $comment->getAttribute("time_scale");
                $framerate = $time_scale / (2 * $num_units_in_tick);
            }
        }
    }
} elseif ($sdType == 'hev1' || $sdType == 'hvc1') {
    $nal_unit = $xml->getElementsByTagName("NALUnit");
    if ($nal_unit->length == 0) {
        $framerate = 0;
    } else {
        for ($nal_count = 0; $nal_count < $nal_unit->length; $nal_count++) {
            if ($nal_unit->item($nal_count)->getAttribute("nal_unit_type") == "33") {
                $sps_unit = $nal_count;
                 break;
            }
        }

        $sps = $nal_unit->item($sps_unit);
        if ($sps->getAttribute("vui_parameters_present_flag") == "1") {
            if ($sps->getAttribute("vui_timing_info_present_flag") == "1") {
                $num_units_in_tick = $sps->getAttribute("vui_num_units_in_tick");
                $time_scale = $sps->getAttribute("vui_time_scale");
                $framerate = $time_scale / ($num_units_in_tick);
            }
        }
    }
}
return $framerate;
