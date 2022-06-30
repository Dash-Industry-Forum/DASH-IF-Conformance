<?php

$conform = true;
$audioSample = $xml->getElementsByTagName('soun_sampledescription');
if ($audioSample->length > 0) {
    $samplingRate = $audioSample->item(0)->getAttribute('sampleRate');
    if ((float)$samplingRate > 48000.0) {
        $conform = false;
    }
}
$audioDecoderInfo = $xml->getElementsByTagName('DecoderSpecificInfo');
$channelConfig = $audioDecoderInfo->item(0)->getAttribute('channelConfig');
if ($channelConfig != 0x1 && $channelConfig != 0x2) {
    $conform = false;
}

return  $conform;
