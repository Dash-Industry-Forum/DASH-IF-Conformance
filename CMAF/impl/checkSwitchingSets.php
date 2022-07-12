<?php

global $session_dir, $mpd_features, $current_period, $current_adaptation_set, $string_info,
        $adaptation_set_template, $comparison_folder, $compinfo_file, $progress_xml, $progress_report;


$adaptation_set = $mpd_features['Period'][$current_period]['AdaptationSet'][$current_adaptation_set];
$curr_adapt_dir = $session_dir . '/Period' . $current_period . '/' .
                  str_replace('$AS$', $current_adaptation_set, $adaptation_set_template);

$filecount = 0;
$files = glob($curr_adapt_dir . "/*.xml");
if ($files) {
    $filecount = count($files);
}

$ind = 0;
for ($i = 0; $i < $filecount - 1; $i++) { //iterate over files
    for ($j = $i + 1; $j < $filecount; $j++) { //iterate over remaining files
        $fileName1 = $files[$i]; //load file
        $xml1 = get_DOM($fileName1, 'atomlist');
        $id1 = $adaptation_set['Representation'][$i]['id'];

        $fileName2 = $files[$j]; //load file to be compared
        $xml2 = get_DOM($fileName2, 'atomlist');
        $id2 = $adaptation_set['Representation'][$j]['id'];

        create_folder_in_session($curr_adapt_dir  . '/' . $comparison_folder);
        $namePart1 = explode('.', basename($fileName1))[0];
        $namePart2 = explode('.', basename($fileName2))[0];
        $path = $curr_adapt_dir  . '/' . $comparison_folder . $namePart1 . "_vs_" . $namePart2 . ".xml";

        if ($xml1 && $xml2) {
            $this->checkHeaders($xml1, $xml2, $id1, $id2, $curr_adapt_dir, $ind, $path); //start comparing
            $this->compareHevc($xml1, $xml2, $id1, $id2);
            $this->checkMediaProfiles($i, $j);
            $this->compareRest($xml1, $xml2, $id1, $id2);
        }

        $ind++;
    }
}
