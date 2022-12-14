<?php

namespace DASHIF;

class DAC4
{
    public $bitstream_version;
    public $fs_index;
    public $frame_rate_index;
    public $short_program_id;
    public $n_presentations;
}

$result = array();

$doms  = $atomInfo->getElementsByTagName('ac4_dsi_v1');
foreach ($doms as $dom) {
    $dac4 = new DAC4();
    $dac4->fs_index = $dom->getAttribute('fs_index');
    $dac4->frame_rate_index = $dom->getAttribute('frame_rate_index');
    $dac4->bitstream_version = $dom->getAttribute('bitstream_version');
    $dac4->short_program_id = $dom->getAttribute('short_program_id');
    $dac4->n_presentations = $dom->getAttribute('n_presentations');
    $result[] = $dac4;
}

return $result;
