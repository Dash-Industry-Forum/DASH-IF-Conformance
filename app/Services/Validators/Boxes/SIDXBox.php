<?php

namespace App\Services\Validators\Boxes;

class SIDXBox
{
    public function __construct()
    {
        $this->references = [];
    }

    public string $referenceId;
    public int $timescale;
    public int $earliestPresentationTime;

    /**
     * @var array<SIDXReference> $references;
     **/
    public array $references;
}
