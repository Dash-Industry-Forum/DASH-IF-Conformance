<?php

global $logger;

$tocArray = $representation->getAC4TOCBoxes();
$dac4Array = $representation->getDAC4Boxes();

if (!$dac4Array || !$tocArray) {
    // handle missing data
    return;
}

$dac4 = $dac4Array[0];

$tocIndex = 0;

foreach ($tocArray as $toc) {
    $logger->test(
        "Dolby",
        "ETSI_TS_103_190-2_V1.2.1 E.6.3 [14197]",
        "Bitstream version must match between TOC and DAC4",
        $dac4->bitstream_version == $toc->bitstream_version,
        "FAIL",
        "Values match for toc $tocIndex",
        "Values don't match for toc $tocIndex",
    );

    $logger->test(
        "Dolby",
        "ETSI_TS_103_190-2_V1.2.1 E.6.4 [14203]",
        "FS index must match between TOC and DAC4",
        $dac4->fs_index == $toc->fs_index,
        "FAIL",
        "Values match for toc $tocIndex",
        "Values don't match for toc $tocIndex",
    );

    $logger->test(
        "Dolby",
        "ETSI_TS_103_190-2_V1.2.1 E.6.5 [14209]",
        "Frame rate index must match between TOC and DAC4",
        $dac4->frame_rate_index == $toc->frame_rate_index,
        "FAIL",
        "Values match for toc $tocIndex",
        "Values don't match for toc $tocIndex",
    );

    $logger->test(
        "Dolby",
        "ETSI_TS_103_190-2_V1.2.1 E.6.6 [14215]",
        "Number of presentations must match between TOC and DAC4",
        $dac4->n_presentations == $toc->n_presentations,
        "FAIL",
        "Values match for toc $tocIndex",
        "Values don't match for toc $tocIndex",
    );

    $logger->test(
        "Dolby",
        "ETSI_TS_103_190-2_V1.2.1 E.6.7 [14221]",
        "Short program ID must match between TOC and DAC4",
        $dac4->short_program_id == $toc->short_program_id,
        "FAIL",
        "Values match for toc $tocIndex",
        "Values don't match for toc $tocIndex",
    );

    $tocIndex++;
}
