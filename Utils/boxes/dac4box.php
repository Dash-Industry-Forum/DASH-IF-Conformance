<?php

namespace DASHIF\Boxes;

class DAC4
{
    public function __construct()
    {
        $this->bitstream_version = null;
        $this->fs_index = null;
        $this->frame_rate_index = null;
        $this->short_program_id = null;
        $this->n_presentations = null;
    }
    public $bitstream_version;
    public $fs_index;
    public $frame_rate_index;
    public $short_program_id;
    public $n_presentations;
}