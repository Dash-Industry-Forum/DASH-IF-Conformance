<?php

if (!$this->features) {
    return;
}
if (!array_key_exists("Period", $this->features)) {
    return;
}

$this->profiles = array();
$periods = $this->features['Period'];

foreach ($periods as $period) {
    $adapts = $period['AdaptationSet'];
    $adapt_profiles = array();
    foreach ($adapts as $adapt) {
        $reps = $adapt['Representation'];
        $rep_profiles = array();
        foreach ($reps as $rep) {
            $profiles = $this->features['profiles'];

            if (array_key_exists('profiles', $period) && $period['profiles']) {
                $profiles = $period['profiles'];
            }

            if (array_key_exists('profile', $adapt) && $adapt['profile']) {
                $profiles = $adapt['profiles'];
            }

            if (array_key_exists('profile', $rep) && $rep['profile']) {
                $profiles = $rep['profiles'];
            }

            $rep_profiles[] = $profiles;
        }
        $adapt_profiles[] = $rep_profiles;
    }
    $this->profiles[] = $adapt_profiles;
}
