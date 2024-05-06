<?php

global $session, $limit;

foreach ($this->segmentUrls as $periodIdx => $periodUrls) {
    foreach ($periodUrls as $adaptationIdx => $adaptationUrls) {
        foreach ($adaptationUrls as $representationIdx => $representationUrls) {
            $dir = $session->getRepresentationDir($periodIdx, $adaptationIdx, $representationIdx);
            $assembly = ($assemble ? fopen("$dir/assembled.mp4", 'a+') : null);
            $sizeFile = ($assemble ? fopen("$dir/assemblerInfo.txt", 'a+') : null);
            $index = 0;

            if (array_key_exists('init', $representationUrls)) {
                $this->downloadSegment("$dir/init.mp4", $representationUrls['init']);

                $this->assembleSingle("$dir/init.mp4", $assembly, $sizeFile, $index);
                $index++;
            }

            foreach ($representationUrls['segments'] as $i => $url) {
                $segmentPadded = sprintf('%02d', $i);
                $this->downloadSegment("$dir/seg${segmentPadded}.mp4", $url);
                $this->assembleSingle("$dir/seg${segmentPadded}.mp4", $assembly, $sizeFile, $index);
                $index++;

                if ($limit != 0 && $index >= $limit){
                  break;
                }
            }


            if ($assembly) {
                fclose($assembly);
            }

            if ($sizeFile) {
                fclose($sizeFile);
            }
        }
    }
}
