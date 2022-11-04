<?php

class AC4_TOC
{
    public $bitstream_version;
    public $fs_index;
    public $frame_rate_index;
    public $short_program_id;
    public $n_presentations;
};

$result = array();

$doms = $atomInfo->getElementsByTagName('ac4_toc');
foreach ($doms as $dom)
{

    $toc = new AC4_TOC();
    $toc->fs_index = $dom->getAttribute('fs_index');
    $toc->frame_rate_index = $dom->getAttribute('frame_rate_index');
    $toc->bitstream_version = $dom->getAttribute('bitstream_version');
    $toc->short_program_id = $dom->getAttribute('short_program_id');
    $toc->n_presentations = $dom->getAttribute('n_presentations');
    $result[] = $toc;
}

return $result;
