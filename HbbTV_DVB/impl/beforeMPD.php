<?php

global $logger, $mpdHandler;


if ($mpdHandler->getDom() && $this->DVBEnabled) {
    $mpd_bytes = strlen($mpdHanlder->getMPD());

    $logger->test(
        "DVB",
        "Section 4.5",
        "The MPD size before xlink resolution SHALL NOT exceed 256 Kbytes",
        $mpd_bytes <= 1024 * 256,
        "FAIL",
        "MPD size of " . $mpd_bytes . " bytes is within bounds",
        "MPD size of " . $mpd_bytes . " bytes is not within bounds",
    );

    $period_count = $mpdHandler->getDom()->getElementsByTagName('Period')->length;

    $logger->test(
        "DVB",
        "Section 4.5",
        "The MPD  has a maximum of 64 periods before xlink resolutionsize before xlink resolution",
        $period_count <= 64,
        "FAIL",
        $period_count . " period(s) found",
        $period_count . " periods found"
    );
}
