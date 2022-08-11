<?php

$mediaProfileNames = ["HD", "HHD10","UHD10","HLG10", "HDR10",
                      "AAC_Core", "Adaptive_AAC_Core", "AAC_Multichannel", "Enhanced_AC-3", "AC-4_SingleStream",
                      "MPEG-H_SingleStream", "TTML_IMSC1_Text", "TTML_IMSC1_Image", "unknown"];
$fourCC = ["cfhd", "chh1", "cud1", "clg1", "chd1",
           "caac", "caaa", "camc", "ceac", "ca4s",
           "cmhs", "im1t", "im1i", "unknown"];
$key = array_search($mediaProfiles, $mediaProfileNames);
return $fourCC[$key];
