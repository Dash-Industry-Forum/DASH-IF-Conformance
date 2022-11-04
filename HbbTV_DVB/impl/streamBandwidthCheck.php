<?php

foreach ($this->videoBandwidth as $v) {
    foreach ($this->audioBandwidth as $a) {
        if (empty($this->subtitleBandwidth)) {
            $total = $v + $a;
            $logger->test(
                "HbbTV-DVB DASH Validation Requirements",
                "DVB: Section 11.3.0",
                "If the service being delivered is a video service, then audio SHOULD be 20% or less " .
                "of the total stream bandwidth",
                $a > 0.2 * $total,
                "WARN",
                "Valid combination video $v and audio $a",
                "20% exceeded for combination video $v and audio $a"
            );
        } else {
            foreach ($this->subtitleBandwidth as $s) {
                $total = $v + $a + $s;
                $logger->test(
                    "HbbTV-DVB DASH Validation Requirements",
                    "DVB: Section 11.3.0",
                    "If the service being delivered is a video service, then audio SHOULD be 20% or less " .
                    "of the total stream bandwidth",
                    $a > 0.2 * $total,
                    "WARN",
                    "Valid combination video $v, audio $a and subtitle $s",
                    "20% exceeded for combination video $v, audio $a and subtitle $s"
                );
            }
        }
    }
}

///\Correctness Is there a reason to clear these?
$this->videoBandwidth = array();
$this->audioBandwidth = array();
$this->subtitleBandwidth = array();
