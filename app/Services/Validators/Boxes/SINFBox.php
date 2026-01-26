<?php

namespace App\Services\Validators\Boxes;

use App\Services\Validators\Boxes\TENCBox;
use App\Services\Validators\Boxes\SCHMBox;

class SINFBox
{
    public string $originalFormat;
    public SCHMBox $scheme;
    public TENCBox $encryption;

    public function __construct()
    {
        $this->originalFormat = '';
        $this->scheme = new SCHMBox();
        $this->encryption = new TENCBox();
    }
}
