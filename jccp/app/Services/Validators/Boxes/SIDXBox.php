<?php

namespace App\Services\Validators\Boxes;

class SIDXBox
{
    public function __construct()
    {
        $this->references = [];
    }

    /**
     * @var array<SIDXReference> $references;
     **/
    public array $references;
}
