<?php

global $session;

foreach ($this->segmentUrls as $periodIdx => $periodUrls) {
    foreach ($periodUrls as $adaptationIdx => $adaptationUrls) {
        foreach ($adaptationUrls as $representationIdx => $representationUrls) {
            $dir = $session->getRepresentationDir($periodIdx, $adaptationIdx, $representationIdx);

            if (array_key_exists('init', $representationUrls)) {
                $this->downloadSegment($dir . "/init.mp4", $representationUrls['init']);
            }

            foreach ($representationUrls['segments'] as $i => $url) {
                $segmentPadded = sprintf('%02d', $i);
                $this->downloadSegment($dir . "/seg${segmentPadded}.mp4", $url);
            }
        }
    }
}
