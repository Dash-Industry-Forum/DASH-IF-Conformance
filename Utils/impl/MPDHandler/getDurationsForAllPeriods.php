<?php

$periods = $this->features['Period'];

$mediapresentationduration = DASHIF\Utility\timeParsing($this->features['mediaPresentationDuration']);

$this->periodTimingInformation = array();

for ($i = 0; $i < sizeof($periods); $i++) {
    $period = $periods[$i];

    $periodStart = $period['start'];
    $periodDuration = $period['duration'];

    if ($periodStart != '') {
        $start = DASHIF\Utility\timeParsing($periodStart);
    } else {
        if ($i > 0) {
            $previous =  $this->periodTimingInformation[$i - 1];
            if ($previous['duration'] != '') {
                $start = (float)($previous['start'] + $previous['duration']);
            } else {
                $start = '';
                if ($this->features['type'] == 'dynamic') {
                  ///\todo handle early available period
                }
            }
        } else {
            if ($this->features['type'] == 'static') {
                $start = 0;
            } else {
                $start = '';
                if ($this->features['type'] == 'dynamic') {
                  ///\todo handle early available period
                }
            }
        }
    }

    if ($periodDuration != '' && $periodDuration != null) {
        $duration = DASHIF\Utility\timeParsing($periodDuration);
    } else {
        if ($i != sizeof($periods) - 1) {
            $duration = DASHIF\Utility\timeParsing($periods[$i + 1]['start']) - $start;
        } else {
            $duration = $mediapresentationduration - $start;
        }
    }

    $this->periodTimingInformation[] = array(
      'start' => $start,
      'duration' => min([$duration, 1800])
    );
}
