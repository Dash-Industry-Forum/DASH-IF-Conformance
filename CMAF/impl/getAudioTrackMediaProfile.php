<?php

global $logger;

if ($mpParameters['codec'] != "AAC") {
    return "unknown";
}

$validEncoding = $logger->test(
    "CMAF",
    "Section 10.4",
    "Each AAC elementary stream SHALL be encoded using MPEG-4 AAC LC, HE-AAC Level 2, or HE-AACv2 Level 2",
    in_array($mpParameters['profile'], array(2, 5, 29)),
    "FAIL",
    "Valid profile found",
    "Nonvalid profile found"
);

if (!$validEncoding) {
    return "unknown";
}

$validLevel = $logger->test(
    "CMAF",
    "Section 10.4",
    "Each AAC elementary stream SHALL be encoded using MPEG-4 AAC LC, HE-AAC Level 2, or HE-AACv2 Level 2",
    $mpParameters['level'] === "" || strpos($mpParameters['level'], "AAC@L2") !== false,
    "FAIL",
    "Valid level information found",
    "Nonvalid level information found"
);

if (!$validLevel) {
    return "unknown";
}

$validChannelCount = $logger->test(
    "CMAF",
    "Section 10.4",
    "AAC Core CMAF tracks SHALL not exceed two audio channels",
    $mpParameters['channels'] == 1 || $mpParameters['channels'] == 2,
    "FAIL",
    "Valid channel count found",
    "Nonvalid channel count found"
);

if (!$validChannelCount) {
    return "unknown";
}

$validSamplingRate = $logger->test(
    "CMAF",
    "Section 10.4",
    "AAC Core elementary streams SHALL not exceed 48kHz sampling rate",
    $mpParameters['sampleRate'] <= 48000,
    "FAIL",
    "Valid sampling rate count found",
    "Nonvalid sampling rate count found"
);

if (!$validSamplingRate) {
    return "unknown";
}

if ($mpParameters['brand'] == 'caaa') {
    return "AAC Adaptive";
}
if ($mpParameters['brand'] == 'caac') {
    return "AAC Core";
}
if ($mpParameters['brand'] == '') {
    return "AAC Core";
}

//At this point, we have not found a valid aac codec, so this test will always fail
$validSamplingRate = $logger->test(
    "CMAF",
    "Section 10.4",
    "AAC Core/Adaptive Audio FileTypeBox compatibility brand SHALL be 'caac'/'caaa', respectively",
    false,
    "FAIL",
    "",
    "Nonvalid compatiblity brand found"
);

return "unknown";
