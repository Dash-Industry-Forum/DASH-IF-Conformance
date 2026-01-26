<?php

namespace App\Services\Validators\Boxes;

class SIDXReference
{
    public string $referenceType = "";
    public int $size = 0;
    public int $duration = 0;
    public bool $startsWithSAP = false;
    public string $sapType = "";
    public int $sapDeltaTime = 0;
}
