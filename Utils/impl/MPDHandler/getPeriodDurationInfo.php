<?php
global $period_timing_info;

if (empty($this->periodTimingInformation)) {
    $this->getDurationForAllPeriods();
}


$period_timing_info = $this->periodTimingInformation[$period];

return $period_timing_info;
