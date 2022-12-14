<?php

if (empty($this->periodTimingInformation)) {
    $this->getDurationForAllPeriods();
}

return $this->periodTimingInformation[$period];
