<?php

global $session_dir, $current_period, $current_adaptation_set, $current_representation,
       $adaptation_set_template, $reprsentation_template, $reprsentation_error_log_template,
       $string_info, $progress_xml, $progress_report, $logger;

$adapt_dir = str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);
$rep_dir = str_replace(
    array('$AS$', '$R$'),
    array($current_adaptation_set, $current_representation),
    $reprsentation_template
);
$rep_xml = $session_dir . '/Period' . $current_period . '/' . $adapt_dir . '/' . $rep_dir . '.xml';

if (!file_exists($rep_xml)) {
    return;
}

$xml = get_DOM($rep_xml, 'atomlist');
if (!$xml) {
    return;
}

$this->validateSegmentCommon($xml);
$this->validateSegmentOnDemand($xml);
