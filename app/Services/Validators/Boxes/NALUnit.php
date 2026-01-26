<?php

namespace App\Services\Validators\Boxes;

class NALUnit
{
    public function __construct()
    {
        $this->size = 0;
        $this->code = 0;
        $this->type = '';
    }
    public int $size;
    public int $code;
    public string $type;
}
