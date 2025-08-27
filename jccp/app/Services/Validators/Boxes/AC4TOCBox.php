<?php

namespace DASHIF\Boxes;

class AC4TOC
{
    public function __construct()
    {
        $this->bitstream_version = null;
        $this->fs_index = null;
        $this->frame_rate_index = null;
        $this->short_program_id = null;
        $this->n_presentations = null;
    }
    public ?string $bitstream_version;
    public ?string $fs_index;
    public ?string $frame_rate_index;
    public ?string $short_program_id;
    public ?string $n_presentations;
}
